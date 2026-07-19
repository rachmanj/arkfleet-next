<?php

return [
    'sap_posting_enabled' => env('SAP_LOAN_POSTING_ENABLED', false),

    'defaults' => [
        'principal_gl' => env('SAP_LOAN_PRINCIPAL_GL', '22201017'),
        'interest_gl' => env('SAP_LOAN_INTEREST_GL', '71201004'),
        'tax_code' => env('SAP_LOAN_TAX_CODE', 'B100'),
        'currency' => 'IDR',
    ],
];
