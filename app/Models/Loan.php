<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_card_code',
        'contract_number',
        'principal_amount',
        'interest_rate',
        'term_months',
        'currency',
        'principal_gl',
        'interest_gl',
        'tax_code',
        'department_id',
        'project_code',
        'status',
        'schedule_locked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'term_months' => 'integer',
            'schedule_locked_at' => 'datetime',
        ];
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('installment_no');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(SapBusinessPartner::class, 'vendor_card_code', 'card_code');
    }

    public function isScheduleLocked(): bool
    {
        return $this->schedule_locked_at !== null || $this->status === 'locked';
    }

    public function resolvedPrincipalGl(): string
    {
        return $this->principal_gl ?: config('loans.defaults.principal_gl');
    }

    public function resolvedInterestGl(): string
    {
        return $this->interest_gl ?: config('loans.defaults.interest_gl');
    }

    public function resolvedTaxCode(): string
    {
        return $this->tax_code ?: config('loans.defaults.tax_code');
    }
}
