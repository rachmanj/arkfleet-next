<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DepreciationEntry;
use App\Models\DepreciationRun;
use Illuminate\Http\Request;

class DepreciationController extends Controller
{
    public function runs(Request $request)
    {
        $runs = DepreciationRun::query()
            ->with('runner:id,name')
            ->when($request->year, fn ($q, $year) => $q->where('period_year', $year))
            ->when($request->month, fn ($q, $month) => $q->where('period_month', $month))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($runs);
    }

    public function showRun(DepreciationRun $run)
    {
        $run->load(['entries.fixedAsset.equipment:id,unit_code', 'runner:id,name']);

        return response()->json(['data' => $run]);
    }

    public function entries(Request $request)
    {
        $entries = DepreciationEntry::query()
            ->with(['fixedAsset.equipment:id,unit_code'])
            ->when($request->book_type, fn ($q, $type) => $q->where('book_type', $type))
            ->when($request->fixed_asset_id, fn ($q, $id) => $q->where('fixed_asset_id', $id))
            ->when($request->period_from, fn ($q, $date) => $q->whereDate('period_date', '>=', $date))
            ->when($request->period_to, fn ($q, $date) => $q->whereDate('period_date', '<=', $date))
            ->orderByDesc('period_date')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return response()->json($entries);
    }
}
