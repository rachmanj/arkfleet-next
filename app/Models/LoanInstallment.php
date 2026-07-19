<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    protected $fillable = [
        'loan_id',
        'installment_no',
        'total_installments',
        'due_date',
        'posting_date',
        'document_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'principal_gl',
        'interest_gl',
        'tax_code',
        'department_id',
        'project_code',
        'faktur_pajak_no',
        'faktur_pajak_date',
        'vendor_ref_no',
        'sap_doc_entry',
        'sap_doc_num',
        'sap_payment_doc_entry',
        'sap_payment_doc_num',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'installment_no' => 'integer',
            'total_installments' => 'integer',
            'due_date' => 'date',
            'posting_date' => 'date',
            'document_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'faktur_pajak_date' => 'date',
            'sap_doc_entry' => 'integer',
            'sap_doc_num' => 'integer',
            'sap_payment_doc_entry' => 'integer',
            'sap_payment_doc_num' => 'integer',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function resolvedPrincipalGl(Loan $loan): string
    {
        return $this->principal_gl ?: $loan->resolvedPrincipalGl();
    }

    public function resolvedInterestGl(Loan $loan): string
    {
        return $this->interest_gl ?: $loan->resolvedInterestGl();
    }

    public function resolvedTaxCode(Loan $loan): string
    {
        return $this->tax_code ?: $loan->resolvedTaxCode();
    }

    public function installmentLabel(): string
    {
        return "{$this->installment_no} of {$this->total_installments}";
    }
}
