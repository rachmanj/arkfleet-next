<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDocument extends Model
{
    protected $fillable = [
        'loan_id',
        'file_path',
        'original_filename',
        'parsed_data',
        'is_confirmed',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'is_confirmed' => 'boolean',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
