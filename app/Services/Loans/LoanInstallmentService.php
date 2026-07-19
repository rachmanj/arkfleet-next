<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanInstallmentService
{
    public function createDraftInstallments(Loan $loan, array $rows): int
    {
        if ($loan->isScheduleLocked()) {
            throw ValidationException::withMessages([
                'loan' => 'Schedule is locked after first SAP post.',
            ]);
        }

        $created = 0;

        DB::transaction(function () use ($loan, $rows, &$created) {
            foreach ($rows as $row) {
                $installmentNo = (int) ($row['installment_no'] ?? 0);
                if ($installmentNo <= 0) {
                    continue;
                }

                $principal = (float) ($row['principal_amount'] ?? 0);
                $interest = (float) ($row['interest_amount'] ?? 0);

                LoanInstallment::query()->updateOrCreate(
                    [
                        'loan_id' => $loan->id,
                        'installment_no' => $installmentNo,
                    ],
                    [
                        'total_installments' => (int) ($row['total_installments'] ?? $loan->term_months),
                        'due_date' => $row['due_date'] ?? now()->addMonths($installmentNo)->toDateString(),
                        'posting_date' => $row['posting_date'] ?? $row['due_date'] ?? null,
                        'document_date' => $row['document_date'] ?? $row['due_date'] ?? null,
                        'principal_amount' => $principal,
                        'interest_amount' => $interest,
                        'total_amount' => (float) ($row['total_amount'] ?? $principal + $interest),
                        'principal_gl' => $row['principal_gl'] ?? null,
                        'interest_gl' => $row['interest_gl'] ?? null,
                        'tax_code' => $row['tax_code'] ?? null,
                        'department_id' => $row['department_id'] ?? $loan->department_id,
                        'project_code' => $row['project_code'] ?? $loan->project_code,
                        'vendor_ref_no' => $row['vendor_ref_no'] ?? null,
                        'status' => 'draft',
                    ],
                );

                $created++;
            }

            if ($created > 0 && $loan->status === 'draft') {
                $loan->update(['status' => 'active']);
            }
        });

        return $created;
    }

    public function confirmInstallment(LoanInstallment $installment): LoanInstallment
    {
        if ($installment->status !== 'draft') {
            throw ValidationException::withMessages([
                'installment' => 'Only draft installments can be confirmed.',
            ]);
        }

        $installment->update(['status' => 'confirmed']);

        return $installment->fresh();
    }

    public function updateInstallment(LoanInstallment $installment, array $data): LoanInstallment
    {
        if ($installment->loan->isScheduleLocked() && in_array($installment->status, ['posted', 'paid'], true)) {
            throw ValidationException::withMessages([
                'installment' => 'Posted installments cannot be edited.',
            ]);
        }

        $installment->update($data);

        if (isset($data['principal_amount']) || isset($data['interest_amount'])) {
            $installment->update([
                'total_amount' => (float) $installment->principal_amount + (float) $installment->interest_amount,
            ]);
        }

        return $installment->fresh();
    }
}
