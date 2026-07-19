<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'document_type_id',
        'supplier_id',
        'document_number',
        'issued_date',
        'expiry_date',
        'due_date',
        'amount',
        'extend_count',
        'file_path',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'extend_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString());
    }
}
