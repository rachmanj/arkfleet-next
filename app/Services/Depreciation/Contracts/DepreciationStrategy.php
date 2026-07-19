<?php

namespace App\Services\Depreciation\Contracts;

use App\Models\FixedAsset;
use Carbon\Carbon;

interface DepreciationStrategy
{
    public function calculateMonthly(
        FixedAsset $asset,
        string $bookType,
        float $openingNbv,
        float $accumulatedDepreciation,
        Carbon $periodEnd,
        int $periodIndex,
    ): float;
}
