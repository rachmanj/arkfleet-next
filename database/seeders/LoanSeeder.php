<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\SapBusinessPartner;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = SapBusinessPartner::query()->updateOrCreate(
            ['card_code' => 'V-LOAN-DEMO'],
            [
                'card_name' => 'PT Demo Lender',
                'card_type' => 'S',
                'is_active' => true,
                'currency' => 'IDR',
            ],
        );

        $loan = Loan::query()->updateOrCreate(
            ['contract_number' => 'LN-2024-001'],
            [
                'vendor_card_code' => $vendor->card_code,
                'principal_amount' => 500_000_000,
                'interest_rate' => 0.12,
                'term_months' => 12,
                'currency' => 'IDR',
                'principal_gl' => config('loans.defaults.principal_gl'),
                'interest_gl' => config('loans.defaults.interest_gl'),
                'tax_code' => config('loans.defaults.tax_code'),
                'status' => 'active',
            ],
        );

        $principalPerMonth = round(500_000_000 / 12, 2);
        $interestPerMonth = round(500_000_000 * 0.12 / 12, 2);

        for ($i = 1; $i <= 3; $i++) {
            LoanInstallment::query()->updateOrCreate(
                [
                    'loan_id' => $loan->id,
                    'installment_no' => $i,
                ],
                [
                    'total_installments' => 12,
                    'due_date' => now()->addMonths($i)->startOfMonth(),
                    'posting_date' => now()->addMonths($i)->startOfMonth(),
                    'document_date' => now()->addMonths($i)->startOfMonth(),
                    'principal_amount' => $principalPerMonth,
                    'interest_amount' => $interestPerMonth,
                    'total_amount' => $principalPerMonth + $interestPerMonth,
                    'status' => 'draft',
                ],
            );
        }
    }
}
