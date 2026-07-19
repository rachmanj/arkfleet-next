<?php

namespace App\Http\Controllers;

use App\Models\EquipmentDocument;
use App\Models\IpaTransfer;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(request()->user()?->can('view'), 403);

        return Inertia::render('Dashboard', [
            'stats' => [
                'expiring_documents' => EquipmentDocument::query()->expiringWithin(30)->count(),
                'expired_documents' => EquipmentDocument::query()->expired()->count(),
                'ipa_transfers_this_month' => IpaTransfer::query()
                    ->where('transferred_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'expiringAlerts' => EquipmentDocument::query()
                ->with(['equipment:id,unit_code', 'documentType:id,name'])
                ->expiringWithin(30)
                ->orderBy('expiry_date')
                ->limit(10)
                ->get(),
        ]);
    }
}
