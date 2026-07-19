<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SapSyncRun extends Model
{
    protected $fillable = [
        'entity_type',
        'status',
        'created_count',
        'updated_count',
        'failed_count',
        'error_summary',
        'metadata',
        'started_at',
        'finished_at',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
