<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AssetClass;
use App\Models\Equipment;
use App\Models\FixedAsset;
use App\Services\Depreciation\AssetDisposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FixedAssetController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $assets = FixedAsset::query()
            ->with(['equipment:id,unit_code,description', 'assetClass:id,code,name'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('equipment', fn ($q) => $q
                    ->where('unit_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance/FixedAssets/Index', [
            'assets' => $assets,
            'assetClasses' => AssetClass::query()->where('is_active', true)->orderBy('name')->get(),
            'equipmentOptions' => Equipment::query()
                ->where('is_active', true)
                ->whereDoesntHave('fixedAsset')
                ->orderBy('unit_code')
                ->get(['id', 'unit_code', 'acquisition_cost', 'acquisition_date', 'in_service_date', 'salvage_value', 'useful_life_months']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id', 'unique:fixed_assets,equipment_id'],
            'asset_class_id' => ['required', 'exists:asset_classes,id'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'acquisition_date' => ['nullable', 'date'],
            'in_service_date' => ['required', 'date'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'book_method' => ['nullable', 'string', 'max:30'],
            'book_useful_life_months' => ['nullable', 'integer', 'min:1'],
            'tax_method' => ['nullable', 'string', 'max:30'],
            'tax_useful_life_months' => ['nullable', 'integer', 'min:1'],
            'total_units' => ['nullable', 'integer', 'min:1'],
        ]);

        FixedAsset::create($validated);

        return back()->with('success', 'Fixed asset capitalized.');
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'asset_class_id' => ['required', 'exists:asset_classes,id'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'acquisition_date' => ['nullable', 'date'],
            'in_service_date' => ['required', 'date'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'book_method' => ['nullable', 'string', 'max:30'],
            'book_useful_life_months' => ['nullable', 'integer', 'min:1'],
            'tax_method' => ['nullable', 'string', 'max:30'],
            'tax_useful_life_months' => ['nullable', 'integer', 'min:1'],
            'total_units' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:active,fully_depreciated'],
        ]);

        $fixedAsset->update($validated);

        return back()->with('success', 'Fixed asset updated.');
    }

    public function schedule(Request $request, FixedAsset $fixedAsset): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $fixedAsset->load(['equipment:id,unit_code', 'assetClass:id,name,code']);

        return Inertia::render('Finance/FixedAssets/Schedule', [
            'asset' => $fixedAsset,
            'entries' => $fixedAsset->depreciationEntries()
                ->orderBy('period_date')
                ->orderBy('book_type')
                ->get(),
        ]);
    }

    public function dispose(Request $request, FixedAsset $fixedAsset, AssetDisposalService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_type' => ['required', 'in:sale,scrap,writeoff'],
            'proceeds' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->dispose($fixedAsset, $validated, $request->user()->id);

        return back()->with('success', 'Asset disposed.');
    }
}
