<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\SapBusinessPartner;
use App\Services\Sap\SapBusinessPartnerSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessPartnerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $partners = SapBusinessPartner::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('card_code', 'like', "%{$search}%")
                        ->orWhere('card_name', 'like', "%{$search}%");
                });
            })
            ->when($request->card_type, fn ($query, $type) => $query->where('card_type', strtoupper($type)))
            ->orderBy('card_code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Masters/BusinessPartners/Index', [
            'partners' => $partners,
            'filters' => $request->only('search', 'card_type'),
        ]);
    }

    public function sync(Request $request, SapBusinessPartnerSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->can('sync'), 403);

        $result = $syncService->sync($request->user()->id, [
            'card_type' => $request->input('card_type'),
            'active_only' => true,
        ]);

        return back()->with(
            'success',
            "Business partner sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.",
        );
    }
}
