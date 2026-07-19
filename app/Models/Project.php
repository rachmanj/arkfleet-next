<?php

namespace App\Models;

use App\Models\Concerns\HasSelectableScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasSelectableScope, SoftDeletes;

    protected $fillable = [
        'code',
        'sap_code',
        'name',
        'description',
        'is_active',
        'is_selectable',
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
}
