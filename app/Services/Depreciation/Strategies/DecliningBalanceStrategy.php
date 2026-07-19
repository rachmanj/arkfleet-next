<?php

namespace App\Services\Depreciation\Strategies;

use App\Models\FixedAsset;
use App\Services\Depreciation\Contracts\DepreciationStrategy;
use Carbon\Carbon;

class DecliningBalanceStrategy implements DepreciationStrategy
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
        $usefulLifeMonths = $bookType === 'tax'
            ? $asset->resolvedTaxUsefulLifeMonths()
            : $asset->resolvedBookUsefulLifeMonths();

        if ($usefulLifeMonths <= 0 || $openingNbv <= $salvage) {
            return 0;
        }

        $lifeYears = max(1, $usefulLifeMonths / 12);
        $annualRate = 2 / $lifeYears;
        $monthly = round($openingNbv * ($annualRate / 12), 2);

        return min($monthly, max(0, $openingNbv - $salvage));
    }
}
