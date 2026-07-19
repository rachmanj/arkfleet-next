<?php

namespace App\Services\Depreciation;

use App\Models\FixedAsset;
use App\Services\Depreciation\Contracts\DepreciationStrategy;
use App\Services\Depreciation\Strategies\DecliningBalanceStrategy;
use App\Services\Depreciation\Strategies\StraightLineStrategy;
use App\Services\Depreciation\Strategies\SumOfYearsDigitsStrategy;
use App\Services\Depreciation\Strategies\UnitsOfProductionStrategy;
use Carbon\Carbon;
use InvalidArgumentException;

class DepreciationCalculator
{
    public function strategyFor(string $method): DepreciationStrategy
    {
        return match ($method) {
            'straight_line' => new StraightLineStrategy,
            'declining_balance' => new DecliningBalanceStrategy,
            'sum_of_years_digits' => new SumOfYearsDigitsStrategy,
            'units_of_production' => new UnitsOfProductionStrategy,
            default => throw new InvalidArgumentException("Unknown depreciation method: {$method}"),
        };
    }

    public function methodFor(FixedAsset $asset, string $bookType): string
    {
        return $bookType === 'tax'
            ? $asset->resolvedTaxMethod()
            : $asset->resolvedBookMethod();
    }

    public function periodIndex(FixedAsset $asset, Carbon $periodEnd): int
    {
        $start = $asset->in_service_date->copy()->startOfMonth();
        $end = $periodEnd->copy()->endOfMonth();

        if ($end->lt($start)) {
            return 0;
        }

        return $start->diffInMonths($end) + 1;
    }

    public function calculateMonthly(
        FixedAsset $asset,
        string $bookType,
        float $openingNbv,
        float $accumulatedDepreciation,
        Carbon $periodEnd,
    ): float {
        $method = $this->methodFor($asset, $bookType);
        $periodIndex = $this->periodIndex($asset, $periodEnd);

        return $this->strategyFor($method)->calculateMonthly(
            $asset,
            $bookType,
            $openingNbv,
            $accumulatedDepreciation,
            $periodEnd,
            $periodIndex,
        );
    }
}
