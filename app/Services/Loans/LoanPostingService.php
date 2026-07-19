<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\Sap\PostingService;
use App\Services\Sap\SapService;
use RuntimeException;

class LoanPostingService
{
    public function __construct(
        protected PostingService $postingService,
    ) {}

    public function isEnabled(): bool
    {
        return \App\Support\SapPostingGate::loanPostingEnabled();
    }

    public function buildApInvoicePreview(LoanInstallment $installment): array
    {
        $installment->load('loan');
        $loan = $installment->loan;

        $docDate = ($installment->document_date ?? $installment->posting_date ?? $installment->due_date)->format('Y-m-d');
        $principalGl = $installment->resolvedPrincipalGl($loan);
        $interestGl = $installment->resolvedInterestGl($loan);
        $taxCode = $installment->resolvedTaxCode($loan);

        $lines = [];

        if ((float) $installment->principal_amount > 0) {
            $lines[] = [
                'ItemDescription' => "{$installment->installmentLabel()} ( Principal )",
                'AccountCode' => $principalGl,
                'LineTotal' => (float) $installment->principal_amount,
                'TaxCode' => $taxCode,
            ];
        }

        if ((float) $installment->interest_amount > 0) {
            $lines[] = [
                'ItemDescription' => "Installment {$installment->installmentLabel()} ( Interest )",
                'AccountCode' => $interestGl,
                'LineTotal' => (float) $installment->interest_amount,
                'TaxCode' => $taxCode,
            ];
        }

        return [
            'CardCode' => $loan->vendor_card_code,
            'DocDate' => $docDate,
            'DocDueDate' => $installment->due_date->format('Y-m-d'),
            'TaxDate' => $docDate,
            'Comments' => "Contract {$loan->contract_number} — Installment {$installment->installmentLabel()}",
            'DocumentLines' => $lines,
            'total' => (float) $installment->total_amount,
        ];
    }

    public function postApInvoice(LoanInstallment $installment, ?int $userId = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('SAP loan posting is disabled. Requires SAP_POSTING_UAT_SIGNED_OFF=true and SAP_LOAN_POSTING_ENABLED=true.');
        }

        if ($installment->status !== 'confirmed') {
            throw new RuntimeException('Installment must be confirmed before posting to SAP.');
        }

        if ($installment->sap_doc_entry) {
            throw new RuntimeException('Installment already posted to SAP.');
        }

        $preview = $this->buildApInvoicePreview($installment);
        $idempotencyKey = "loan-installment-{$installment->id}-ap-invoice";

        $log = $this->postingService->postWithIdempotency(
            documentType: 'ap_invoice',
            idempotencyKey: $idempotencyKey,
            postCallback: function (SapService $sap) use ($preview) {
                $sap->ensureSession();

                return $sap->post('PurchaseInvoices', $preview);
            },
            userId: $userId,
        );

        if ($log->status === 'success') {
            $installment->update([
                'status' => 'posted',
                'sap_doc_entry' => $log->doc_entry,
                'sap_doc_num' => $log->doc_num,
            ]);

            $loan = $installment->loan;
            if (! $loan->isScheduleLocked()) {
                $loan->update([
                    'status' => 'locked',
                    'schedule_locked_at' => now(),
                ]);
            }
        }

        return ['log' => $log, 'preview' => $preview];
    }

    public function buildOutgoingPaymentPreview(LoanInstallment $installment): array
    {
        if (! $installment->sap_doc_entry) {
            throw new RuntimeException('AP Invoice must be posted before outgoing payment.');
        }

        $installment->load('loan');

        return [
            'CardCode' => $installment->loan->vendor_card_code,
            'DocDate' => now()->format('Y-m-d'),
            'PaymentInvoices' => [
                [
                    'DocEntry' => $installment->sap_doc_entry,
                    'SumApplied' => (float) $installment->total_amount,
                ],
            ],
            'total' => (float) $installment->total_amount,
        ];
    }

    public function postOutgoingPayment(LoanInstallment $installment, ?int $userId = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('SAP loan posting is disabled. Requires SAP_POSTING_UAT_SIGNED_OFF=true and SAP_LOAN_POSTING_ENABLED=true.');
        }

        if ($installment->status !== 'posted') {
            throw new RuntimeException('Installment must be posted before payment.');
        }

        if ($installment->sap_payment_doc_entry) {
            throw new RuntimeException('Outgoing payment already recorded.');
        }

        $preview = $this->buildOutgoingPaymentPreview($installment);
        $idempotencyKey = "loan-installment-{$installment->id}-outgoing-payment";

        $log = $this->postingService->postWithIdempotency(
            documentType: 'outgoing_payment',
            idempotencyKey: $idempotencyKey,
            postCallback: function (SapService $sap) use ($preview) {
                $sap->ensureSession();

                return $sap->post('VendorPayments', $preview);
            },
            userId: $userId,
        );

        if ($log->status === 'success') {
            $installment->update([
                'status' => 'paid',
                'sap_payment_doc_entry' => $log->doc_entry,
                'sap_payment_doc_num' => $log->doc_num,
            ]);
        }

        return ['log' => $log, 'preview' => $preview];
    }
}
