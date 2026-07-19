<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentHmKmReading extends Model
{
    use SoftDeletes;

    protected $table = 'equipment_hm_km_readings';

    protected $fillable = [
        'equipment_id',
        'reading_date',
        'reading_type',
        'reading_value',
        'source',
        'uploaded_by',
        'upload_batch_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reading_date' => 'date',
            'reading_value' => 'decimal:2',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(EquipmentHmKmUploadBatch::class, 'upload_batch_id', 'batch_id');
    }

    public function scopeHm(Builder $query): Builder
    {
        return $query->where('reading_type', 'hm');
    }

    public function scopeKm(Builder $query): Builder
    {
        return $query->where('reading_type', 'km');
    }

    public function scopeForUnit(Builder $query, string $unitCode): Builder
    {
        return $query->whereHas('equipment', fn (Builder $equipmentQuery) => $equipmentQuery->where('unit_code', $unitCode));
    }
}
