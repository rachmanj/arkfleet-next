<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'alert_days_before',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'alert_days_before' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EquipmentDocument::class);
    }
}
