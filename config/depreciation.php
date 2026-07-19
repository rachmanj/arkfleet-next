<?php

return [
    'sap_posting_enabled' => env('SAP_DEPRECIATION_POSTING_ENABLED', false),

    'journal' => [
        'memo_prefix' => env('SAP_DEPRECIATION_MEMO_PREFIX', 'ARKFleet Depreciation'),
        'default_depreciation_gl' => env('SAP_DEPRECIATION_EXPENSE_GL', '71201001'),
        'default_accumulated_gl' => env('SAP_DEPRECIATION_ACCUMULATED_GL', '19201001'),
    ],
];
