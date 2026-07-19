<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepreciationRun extends Model
{
    protected $fillable = [
        'period_year',
        'period_month',
        'book_scope',
        'status',
        'total_book_depreciation',
        'total_tax_depreciation',
        'entry_count',
        'run_by',
        'confirmed_at',
        'posted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'total_book_depreciation' => 'decimal:2',
            'total_tax_depreciation' => 'decimal:2',
            'entry_count' => 'integer',
            'confirmed_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->period_year, $this->period_month);
    }
}
