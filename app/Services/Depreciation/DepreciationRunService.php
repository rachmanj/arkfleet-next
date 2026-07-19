<?php

namespace App\Services\Depreciation;

use App\Models\DepreciationEntry;
use App\Models\DepreciationRun;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepreciationRunService
{
    public function __construct(
        protected DepreciationCalculator $calculator,
    ) {}

    public function runPeriod(int $year, int $month, string $bookScope = 'all', ?int $userId = null): DepreciationRun
    {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $periodDate = $periodEnd->toDateString();

        return DB::transaction(function () use ($year, $month, $bookScope, $userId, $periodEnd, $periodDate) {
            $run = DepreciationRun::query()->firstOrCreate(
                [
                    'period_year' => $year,
                    'period_month' => $month,
                    'book_scope' => $bookScope,
                ],
                [
                    'status' => 'draft',
                    'run_by' => $userId,
                ],
            );

            if ($run->status === 'posted') {
                return $run->load('entries.fixedAsset.equipment');
            }

            $bookTypes = match ($bookScope) {
                'book' => ['book'],
                'tax' => ['tax'],
                default => ['book', 'tax'],
            };

            $assets = FixedAsset::query()
                ->with('assetClass')
                ->where('status', 'active')
                ->whereDate('in_service_date', '<=', $periodEnd)
                ->get();

            foreach ($assets as $asset) {
                foreach ($bookTypes as $bookType) {
                    if (DepreciationEntry::query()
                        ->where('fixed_asset_id', $asset->id)
                        ->where('book_type', $bookType)
                        ->whereDate('period_date', $periodDate)
                        ->exists()) {
                        continue;
                    }

                    $prior = DepreciationEntry::query()
                        ->where('fixed_asset_id', $asset->id)
                        ->where('book_type', $bookType)
                        ->whereDate('period_date', '<', $periodDate)
                        ->orderByDesc('period_date')
                        ->first();

                    $openingNbv = $prior
                        ? (float) $prior->closing_nbv
                        : (float) $asset->acquisition_cost;

                    $accumulated = $prior
                        ? (float) $prior->accumulated_depreciation
                        : 0;

                    if ($openingNbv <= $asset->resolvedSalvageValue()) {
                        continue;
                    }

                    $amount = $this->calculator->calculateMonthly(
                        $asset,
                        $bookType,
                        $openingNbv,
                        $accumulated,
                        $periodEnd,
                    );

                    if ($amount <= 0) {
                        continue;
                    }

                    $closingNbv = round($openingNbv - $amount, 2);
                    $newAccumulated = round($accumulated + $amount, 2);

                    DepreciationEntry::create([
                        'depreciation_run_id' => $run->id,
                        'fixed_asset_id' => $asset->id,
                        'book_type' => $bookType,
                        'period_date' => $periodDate,
                        'opening_nbv' => $openingNbv,
                        'depreciation_amount' => $amount,
                        'accumulated_depreciation' => $newAccumulated,
                        'closing_nbv' => $closingNbv,
                    ]);

                    if ($closingNbv <= $asset->resolvedSalvageValue()) {
                        $fullyDepreciated = ! DepreciationEntry::query()
                            ->where('fixed_asset_id', $asset->id)
                            ->where('closing_nbv', '>', $asset->resolvedSalvageValue())
                            ->whereDate('period_date', '>', $periodDate)
                            ->exists();

                        if ($fullyDepreciated && $bookType === 'book') {
                            $asset->update(['status' => 'fully_depreciated']);
                        }
                    }
                }
            }

            $run->update([
                'total_book_depreciation' => round(
                    (float) DepreciationEntry::query()
                        ->where('depreciation_run_id', $run->id)
                        ->where('book_type', 'book')
                        ->sum('depreciation_amount'),
                    2,
                ),
                'total_tax_depreciation' => round(
                    (float) DepreciationEntry::query()
                        ->where('depreciation_run_id', $run->id)
                        ->where('book_type', 'tax')
                        ->sum('depreciation_amount'),
                    2,
                ),
                'entry_count' => DepreciationEntry::query()->where('depreciation_run_id', $run->id)->count(),
                'run_by' => $userId ?? $run->run_by,
                'status' => $run->status === 'posted' ? 'posted' : 'draft',
            ]);

            return $run->fresh()->load(['entries.fixedAsset.equipment', 'runner']);
        });
    }

    public function confirmRun(DepreciationRun $run): DepreciationRun
    {
        abort_if($run->status === 'posted', 422, 'Run already posted to SAP.');

        $run->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $run->fresh();
    }

    public function deferredTaxReport(): array
    {
        $assets = FixedAsset::query()
            ->with(['equipment:id,unit_code', 'assetClass:id,name,code'])
            ->whereIn('status', ['active', 'fully_depreciated'])
            ->get();

        $rows = [];

        foreach ($assets as $asset) {
            $bookAccum = (float) DepreciationEntry::query()
                ->where('fixed_asset_id', $asset->id)
                ->where('book_type', 'book')
                ->orderByDesc('period_date')
                ->value('accumulated_depreciation') ?? 0;

            $taxAccum = (float) DepreciationEntry::query()
                ->where('fixed_asset_id', $asset->id)
                ->where('book_type', 'tax')
                ->orderByDesc('period_date')
                ->value('accumulated_depreciation') ?? 0;

            $temporaryDifference = $taxAccum - $bookAccum;
            $deferredTax = round($temporaryDifference * $asset->resolvedTaxRate(), 2);

            $rows[] = [
                'fixed_asset_id' => $asset->id,
                'unit_code' => $asset->equipment?->unit_code,
                'asset_class' => $asset->assetClass?->name,
                'book_accumulated' => $bookAccum,
                'tax_accumulated' => $taxAccum,
                'temporary_difference' => $temporaryDifference,
                'tax_rate' => $asset->resolvedTaxRate(),
                'deferred_tax' => $deferredTax,
            ];
        }

        return $rows;
    }
}
