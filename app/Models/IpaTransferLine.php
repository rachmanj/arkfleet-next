<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpaTransferLine extends Model
{
    protected $fillable = [
        'ipa_transfer_id',
        'equipment_id',
        'unit_code',
        'from_project_code',
        'to_project_code',
        'from_department_id',
        'to_department_id',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(IpaTransfer::class, 'ipa_transfer_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }
}
