<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'unit_code',
        'description',
        'serial_no',
        'chasis_no',
        'engine_model',
        'machine_no',
        'nomor_polisi',
        'bahan_bakar',
        'warna',
        'capacity',
        'remarks',
        'unit_model_id',
        'manufacture_id',
        'plant_type_id',
        'plant_group_id',
        'asset_category_id',
        'unitstatus_id',
        'supplier_id',
        'department_id',
        'project_code',
        'acquisition_cost',
        'acquisition_date',
        'in_service_date',
        'salvage_value',
        'useful_life_months',
        'is_active',
        'is_rfu',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'capacity' => 'decimal:2',
            'acquisition_date' => 'date',
            'in_service_date' => 'date',
            'is_active' => 'boolean',
            'is_rfu' => 'boolean',
        ];
    }

    public function unitModel(): BelongsTo
    {
        return $this->belongsTo(UnitModel::class);
    }

    public function manufacture(): BelongsTo
    {
        return $this->belongsTo(Manufacture::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function plantGroup(): BelongsTo
    {
        return $this->belongsTo(PlantGroup::class);
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function unitstatus(): BelongsTo
    {
        return $this->belongsTo(Unitstatus::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_code', 'code');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EquipmentDocument::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class);
    }

    public function unitNoHistories(): HasMany
    {
        return $this->hasMany(UnitNoHistory::class);
    }

    public function fixedAsset(): HasOne
    {
        return $this->hasOne(FixedAsset::class);
    }

    public function hmKmReadings(): HasMany
    {
        return $this->hasMany(EquipmentHmKmReading::class);
    }

    public function latestHmReading(): HasOne
    {
        return $this->hasOne(EquipmentHmKmReading::class)
            ->where('reading_type', 'hm')
            ->latestOfMany('reading_date');
    }

    public function latestKmReading(): HasOne
    {
        return $this->hasOne(EquipmentHmKmReading::class)
            ->where('reading_type', 'km')
            ->latestOfMany('reading_date');
    }
}
