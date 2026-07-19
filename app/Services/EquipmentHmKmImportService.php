<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentHmKmReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EquipmentHmKmImportService
{
    protected array $equipmentCache = [];

    protected array $headerMap = [
        'unit_code' => ['unit_code', 'unit code', 'unit', 'unit no', 'unit_no', 'kode unit'],
        'hm' => ['hm', 'hours meter', 'hours_meter', 'hour meter', 'hm reading'],
        'km' => ['km', 'kilometer', 'kilometers', 'odometer', 'km reading', 'jarak'],
        'date' => ['date', 'reading_date', 'reading date', 'tanggal', 'tgl'],
    ];

    /**
     * @return array{readings: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, stats: array<string, int>}
     */
    public function import(
        string $filePath,
        ?string $fallbackDate,
        bool $fileHasDates,
        int $uploadedBy,
        string $batchId,
    ): array {
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
        {
            public function array(array $array): array
            {
                return $array;
            }
        }, $filePath);

        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return [
                'readings' => [],
                'errors' => [['row' => 0, 'message' => 'The file is empty or has no data rows.']],
                'stats' => [
                    'rows_total' => 0,
                    'rows_imported' => 0,
                    'rows_skipped' => 0,
                    'rows_errored' => 1,
                ],
            ];
        }

        $headerRow = array_map(fn ($cell) => is_string($cell) ? trim($cell) : (string) $cell, $rows[0]);
        $columnIndexes = $this->mapHeaders($headerRow);

        if (! isset($columnIndexes['unit_code'])) {
            return [
                'readings' => [],
                'errors' => [['row' => 1, 'message' => 'Missing required column: unit_code.']],
                'stats' => [
                    'rows_total' => 0,
                    'rows_imported' => 0,
                    'rows_skipped' => 0,
                    'rows_errored' => 1,
                ],
            ];
        }

        $readings = [];
        $errors = [];
        $stats = [
            'rows_total' => 0,
            'rows_imported' => 0,
            'rows_skipped' => 0,
            'rows_errored' => 0,
        ];

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            $rowNumber = $i + 1;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $stats['rows_total']++;

            $mappedRow = $this->extractMappedRow($row, $columnIndexes);

            if (! $fileHasDates && $fallbackDate) {
                $mappedRow['date'] = $fallbackDate;
            }

            $rowReadings = [];
            $rowErrors = [];

            if (filled($mappedRow['hm'] ?? null)) {
                $validation = $this->validateRow($mappedRow, $rowNumber, 'hm', (float) $mappedRow['hm']);

                if ($validation['valid']) {
                    $rowReadings[] = $validation['reading'];
                } else {
                    $rowErrors = array_merge($rowErrors, $validation['errors']);
                }
            }

            if (filled($mappedRow['km'] ?? null)) {
                $validation = $this->validateRow($mappedRow, $rowNumber, 'km', (float) $mappedRow['km']);

                if ($validation['valid']) {
                    $rowReadings[] = $validation['reading'];
                } else {
                    $rowErrors = array_merge($rowErrors, $validation['errors']);
                }
            }

            if (empty($mappedRow['hm']) && empty($mappedRow['km'])) {
                $rowErrors[] = [
                    'row' => $rowNumber,
                    'message' => 'At least one of HM or KM must be present.',
                ];
            }

            if ($rowErrors !== []) {
                $errors = array_merge($errors, $rowErrors);
                $stats['rows_errored']++;
                continue;
            }

            $importedForRow = 0;
            $skippedForRow = 0;

            foreach ($rowReadings as $readingData) {
                if ($this->shouldSkipDuplicate(
                    $readingData['equipment_id'],
                    $readingData['reading_type'],
                    $readingData['reading_date'],
                )) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => "Duplicate reading: {$mappedRow['unit_code']} already has a {$readingData['reading_type']} reading on {$readingData['reading_date']}.",
                    ];
                    $skippedForRow++;
                    continue;
                }

                $regression = $this->checkRegression(
                    $readingData['equipment_id'],
                    $readingData['reading_type'],
                    (float) $readingData['reading_value'],
                    $readingData['reading_date'],
                );

                if ($regression) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => $regression,
                        'level' => 'warning',
                    ];
                }

                $readings[] = array_merge($readingData, [
                    'source' => 'upload',
                    'uploaded_by' => $uploadedBy,
                    'upload_batch_id' => $batchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $importedForRow++;
            }

            if ($importedForRow > 0) {
                $stats['rows_imported'] += $importedForRow;
            }

            if ($skippedForRow > 0) {
                $stats['rows_skipped'] += $skippedForRow;
            }
        }

        if ($readings !== []) {
            DB::table('equipment_hm_km_readings')->insert($readings);
        }

        return [
            'readings' => $readings,
            'errors' => $errors,
            'stats' => $stats,
        ];
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<string, int>
     */
    public function mapHeaders(array $headerRow): array
    {
        $indexes = [];

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim($header));

            foreach ($this->headerMap as $canonical => $variants) {
                if (in_array($normalized, $variants, true)) {
                    $indexes[$canonical] = $index;
                }
            }
        }

        return $indexes;
    }

    /**
     * @param  array<string, mixed>  $mappedRow
     * @return array{valid: bool, reading?: array<string, mixed>, errors: array<int, array<string, mixed>>}
     */
    protected function validateRow(array $mappedRow, int $rowIndex, string $readingType, float $readingValue): array
    {
        $errors = [];
        $unitCode = trim((string) ($mappedRow['unit_code'] ?? ''));

        if ($unitCode === '') {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => 'Unit code is required.']],
            ];
        }

        $equipment = $this->resolveEquipment($unitCode);

        if (! $equipment) {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => "Unknown unit code: {$unitCode}."]],
            ];
        }

        if (! in_array($readingType, ['hm', 'km'], true)) {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => "Invalid reading type: {$readingType}."]],
            ];
        }

        if ($readingValue <= 0) {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => "Reading value must be greater than zero for {$unitCode} ({$readingType})."]],
            ];
        }

        $readingDate = $this->parseDate($mappedRow['date'] ?? null);

        if (! $readingDate) {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => 'Reading date is required.']],
            ];
        }

        if ($readingDate->isFuture()) {
            return [
                'valid' => false,
                'errors' => [['row' => $rowIndex, 'message' => "Reading date {$readingDate->toDateString()} is in the future."]],
            ];
        }

        return [
            'valid' => true,
            'reading' => [
                'equipment_id' => $equipment->id,
                'reading_date' => $readingDate->toDateString(),
                'reading_type' => $readingType,
                'reading_value' => $readingValue,
            ],
            'errors' => [],
        ];
    }

    protected function shouldSkipDuplicate(int $equipmentId, string $type, string $date): bool
    {
        return EquipmentHmKmReading::query()
            ->where('equipment_id', $equipmentId)
            ->where('reading_type', $type)
            ->whereDate('reading_date', $date)
            ->exists();
    }

    protected function checkRegression(int $equipmentId, string $type, float $value, string $date): ?string
    {
        $previous = EquipmentHmKmReading::query()
            ->where('equipment_id', $equipmentId)
            ->where('reading_type', $type)
            ->where('reading_date', '<', $date)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if (! $previous || (float) $previous->reading_value <= $value) {
            return null;
        }

        $equipment = Equipment::query()->find($equipmentId);
        $unitCode = $equipment?->unit_code ?? (string) $equipmentId;

        return "Reading {$value} for {$unitCode} ({$type}) is lower than previous reading {$previous->reading_value} on {$previous->reading_date->toDateString()}.";
    }

  /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columnIndexes
     * @return array<string, mixed>
     */
    protected function extractMappedRow(array $row, array $columnIndexes): array
    {
        $mapped = [];

        foreach ($columnIndexes as $canonical => $index) {
            $value = $row[$index] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($canonical, ['hm', 'km'], true)) {
                $mapped[$canonical] = is_numeric($value) ? (float) $value : null;
                continue;
            }

            if ($canonical === 'date') {
                $parsed = $this->parseDate($value);
                $mapped[$canonical] = $parsed?->toDateString();
                continue;
            }

            $mapped[$canonical] = is_string($value) ? trim($value) : $value;
        }

        return $mapped;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveEquipment(string $unitCode): ?Equipment
    {
        if (array_key_exists($unitCode, $this->equipmentCache)) {
            return $this->equipmentCache[$unitCode];
        }

        $equipment = Equipment::query()->where('unit_code', $unitCode)->first();
        $this->equipmentCache[$unitCode] = $equipment;

        return $equipment;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
