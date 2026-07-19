<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetClass extends Model
{
    protected $fillable = [
        'code',
        'name',
        'book_method',
        'book_useful_life_months',
        'book_residual_rate',
        'tax_group',
        'tax_method',
        'tax_useful_life_months',
        'tax_rate',
        'sap_depreciation_gl',
        'sap_accumulated_gl',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'book_useful_life_months' => 'integer',
            'book_residual_rate' => 'decimal:4',
            'tax_useful_life_months' => 'integer',
            'tax_rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }
}
