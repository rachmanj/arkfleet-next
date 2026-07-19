<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SapBusinessPartner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'card_code',
        'card_name',
        'card_type',
        'is_active',
        'federal_tax_id',
        'currency',
        'credit_limit',
        'balance',
        'metadata',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'metadata' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $cardType)
    {
        return $query->where('card_type', strtoupper($cardType));
    }
}
