<?php

namespace App\Support;

class SapPostingGate
{
    public static function uatSignedOff(): bool
    {
        return (bool) config('sap.posting.uat_signed_off');
    }

    public static function loanPostingEnabled(): bool
    {
        return self::uatSignedOff() && (bool) config('loans.sap_posting_enabled');
    }

    public static function depreciationPostingEnabled(): bool
    {
        return self::uatSignedOff() && (bool) config('depreciation.sap_posting_enabled');
    }

    public static function status(): array
    {
        return [
            'uat_signed_off' => self::uatSignedOff(),
            'loan_posting' => [
                'env_flag' => (bool) config('loans.sap_posting_enabled'),
                'enabled' => self::loanPostingEnabled(),
            ],
            'depreciation_posting' => [
                'env_flag' => (bool) config('depreciation.sap_posting_enabled'),
                'enabled' => self::depreciationPostingEnabled(),
            ],
        ];
    }
}
