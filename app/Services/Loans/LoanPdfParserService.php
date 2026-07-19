<?php

namespace App\Services\Loans;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class LoanPdfParserService
{
    public function parse(string $filePath): array
    {
        $fullPath = Storage::disk('public')->path($filePath);

        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'File not found.',
                'installments' => [],
            ];
        }

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Could not read PDF: '.$exception->getMessage(),
                'installments' => [],
            ];
        }

        if (trim($text) === '') {
            return [
                'success' => false,
                'message' => 'No extractable text (image-only PDF). Enter installments manually.',
                'installments' => [],
                'raw_text' => '',
            ];
        }

        $installments = $this->extractInstallments($text);

        return [
            'success' => count($installments) > 0,
            'message' => count($installments) > 0
                ? 'Parsed '.count($installments).' installment(s). Review before confirming.'
                : 'No installment rows detected. Enter manually.',
            'installments' => $installments,
            'raw_text' => mb_substr($text, 0, 5000),
        ];
    }

    protected function extractInstallments(string $text): array
    {
        $installments = [];
        $normalized = preg_replace('/\s+/', ' ', $text);

        preg_match_all(
            '/(?:installment|angsuran|ke[- ]?)\s*(\d+)\D+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})?\D*([\d.,]+)\D+([\d.,]+)/i',
            $normalized,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $principal = $this->parseAmount($match[3] ?? '0');
            $interest = $this->parseAmount($match[4] ?? '0');

            if ($principal <= 0 && $interest <= 0) {
                continue;
            }

            $installments[] = [
                'installment_no' => (int) $match[1],
                'due_date' => $this->parseDate($match[2] ?? null),
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => round($principal + $interest, 2),
            ];
        }

        if (count($installments) === 0) {
            preg_match_all(
                '/(\d+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)/',
                $normalized,
                $rows,
                PREG_SET_ORDER,
            );

            foreach ($rows as $row) {
                $principal = $this->parseAmount($row[2]);
                $interest = $this->parseAmount($row[3]);

                if ($principal <= 0 && $interest <= 0) {
                    continue;
                }

                $installments[] = [
                    'installment_no' => (int) $row[1],
                    'due_date' => null,
                    'principal_amount' => $principal,
                    'interest_amount' => $interest,
                    'total_amount' => $this->parseAmount($row[4]) ?: round($principal + $interest, 2),
                ];
            }
        }

        usort($installments, fn ($a, $b) => $a['installment_no'] <=> $b['installment_no']);

        return $installments;
    }

    protected function parseAmount(string $value): float
    {
        $clean = str_replace(['.', ','], ['', '.'], preg_replace('/[^\d.,]/', '', $value));

        return round((float) $clean, 2);
    }

    protected function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }
}
