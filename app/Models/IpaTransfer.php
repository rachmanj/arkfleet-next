<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpaTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'ipa_no',
        'ipa_date',
        'user_id',
        'from_project_code',
        'to_project_code',
        'from_department_id',
        'to_department_id',
        'tujuan_row_1',
        'tujuan_row_2',
        'cc_row_1',
        'cc_row_2',
        'cc_row_3',
        'status',
        'approved_by',
        'approved_at',
        'transferred_at',
        'notes',
        'line_count',
    ];

    protected function casts(): array
    {
        return [
            'ipa_date' => 'date',
            'approved_at' => 'datetime',
            'transferred_at' => 'datetime',
            'line_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_code', 'code');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_code', 'code');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(IpaTransferLine::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'SUBMITTED';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }
}
