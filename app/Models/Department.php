<?php

namespace App\Models;

use App\Models\Concerns\HasSelectableScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasSelectableScope, SoftDeletes;

    protected $fillable = [
        'department_name',
        'akronim',
        'sap_code',
        'description',
        'is_active',
        'is_selectable',
        'parent_id',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_selectable' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
