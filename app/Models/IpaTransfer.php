<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpaTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'user_id',
        'from_project_code',
        'to_project_code',
        'from_department_id',
        'to_department_id',
        'transferred_at',
        'notes',
        'line_count',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
            'line_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(IpaTransferLine::class);
    }
}
