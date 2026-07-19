<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = FixedAsset::query()
            ->with(['equipment:id,unit_code', 'assetClass:id,code,name'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($assets);
    }

    public function show(FixedAsset $fixedAsset)
    {
        $fixedAsset->load([
            'equipment:id,unit_code,description',
            'assetClass:id,code,name',
            'depreciationEntries' => fn ($q) => $q->orderByDesc('period_date')->limit(24),
        ]);

        return response()->json(['data' => $fixedAsset]);
    }
}
