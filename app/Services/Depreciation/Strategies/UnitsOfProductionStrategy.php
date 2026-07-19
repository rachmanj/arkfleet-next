<?php

namespace App\Services\Depreciation\Strategies;

use App\Models\FixedAsset;
use App\Services\Depreciation\Contracts\DepreciationStrategy;
use Carbon\Carbon;

class UnitsOfProductionStrategy implements DepreciationStrategy
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
        $totalUnits = (int) ($asset->total_units ?? 0);

        if ($totalUnits <= 0 || $openingNbv <= $salvage) {
            return 0;
        }

        $depreciableBase = (float) $asset->acquisition_cost - $salvage;
        $unitsThisPeriod = 1;
        $monthly = round($depreciableBase * ($unitsThisPeriod / $totalUnits), 2);

        return min($monthly, max(0, $openingNbv - $salvage));
    }
}
