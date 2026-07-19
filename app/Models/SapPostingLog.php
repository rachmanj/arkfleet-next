<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SapPostingLog extends Model
{
    protected $fillable = [
        'document_type',
        'idempotency_key',
        'status',
        'doc_entry',
        'doc_num',
        'error_payload',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'error_payload' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
