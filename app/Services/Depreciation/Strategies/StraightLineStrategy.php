<?php

namespace App\Services\Depreciation\Strategies;

use App\Models\FixedAsset;
use App\Services\Depreciation\Contracts\DepreciationStrategy;
use Carbon\Carbon;

class StraightLineStrategy implements DepreciationStrategy
{
    public function calculateMonthly(
        FixedAsset $asset,
        string $bookType,
        float $openingNbv,
        float $accumulatedDepreciation,
        Carbon $periodEnd,
        int $periodIndex,
    ): float {
        $salvage = $asset->resolvedSalvageValue();
        $usefulLife = $bookType === 'tax'
            ? $asset->resolvedTaxUsefulLifeMonths()
            : $asset->resolvedBookUsefulLifeMonths();

        if ($usefulLife <= 0 || $openingNbv <= $salvage) {
            return 0;
        }

        $depreciableBase = (float) $asset->acquisition_cost - $salvage;
        $monthly = round($depreciableBase / $usefulLife, 2);

        return min($monthly, max(0, $openingNbv - $salvage));
    }
}
