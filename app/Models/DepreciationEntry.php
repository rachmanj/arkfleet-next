<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    protected $fillable = [
        'depreciation_run_id',
        'fixed_asset_id',
        'book_type',
        'period_date',
        'opening_nbv',
        'depreciation_amount',
        'accumulated_depreciation',
        'closing_nbv',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'opening_nbv' => 'decimal:2',
            'depreciation_amount' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'closing_nbv' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DepreciationRun::class, 'depreciation_run_id');
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
