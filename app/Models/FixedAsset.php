<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'asset_class_id',
        'acquisition_cost',
        'acquisition_date',
        'in_service_date',
        'salvage_value',
        'status',
        'book_method',
        'book_useful_life_months',
        'book_residual_rate',
        'tax_method',
        'tax_useful_life_months',
        'tax_rate',
        'total_units',
        'units_produced_to_date',
        'disposed_at',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_cost' => 'decimal:2',
            'acquisition_date' => 'date',
            'in_service_date' => 'date',
            'salvage_value' => 'decimal:2',
            'book_residual_rate' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'total_units' => 'integer',
            'units_produced_to_date' => 'integer',
            'disposed_at' => 'datetime',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assetClass(): BelongsTo
    {
        return $this->belongsTo(AssetClass::class);
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    public function isDepreciable(): bool
    {
        return $this->status === 'active';
    }

    public function resolvedBookMethod(): string
    {
        return $this->book_method ?? $this->assetClass->book_method;
    }

    public function resolvedTaxMethod(): string
    {
        return $this->tax_method ?? $this->assetClass->tax_method;
    }

    public function resolvedBookUsefulLifeMonths(): int
    {
        return (int) ($this->book_useful_life_months ?? $this->assetClass->book_useful_life_months);
    }

    public function resolvedTaxUsefulLifeMonths(): int
    {
        return (int) ($this->tax_useful_life_months ?? $this->assetClass->tax_useful_life_months);
    }

    public function resolvedSalvageValue(): float
    {
        if ($this->salvage_value > 0) {
            return (float) $this->salvage_value;
        }

        $rate = $this->book_residual_rate ?? $this->assetClass->book_residual_rate;

        return round((float) $this->acquisition_cost * (float) $rate, 2);
    }

    public function resolvedTaxRate(): float
    {
        return (float) ($this->tax_rate ?? $this->assetClass->tax_rate);
    }

    public function sapDepreciationGl(): ?string
    {
        return $this->assetClass->sap_depreciation_gl;
    }

    public function sapAccumulatedGl(): ?string
    {
        return $this->assetClass->sap_accumulated_gl;
    }
}
