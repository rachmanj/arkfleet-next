<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipment = Equipment::query()
            ->with(['unitModel:id,name', 'department:id,department_name', 'plantType:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('unit_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('project_code'), fn ($q) => $q->where('project_code', $request->project_code))
            ->when($request->filled('plant_type'), function ($query, $value) {
                $query->whereHas('plantType', function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%");
                });
            })
            ->orderBy('unit_code')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($equipment);
    }

    public function show(Equipment $equipment)
    {
        $equipment->load(['unitModel:id,name', 'department:id,department_name', 'fixedAsset:id,equipment_id,status']);

        return response()->json(['data' => $equipment]);
    }

    public function hmKmReadings(Equipment $equipment, Request $request)
    {
        $query = $equipment->hmKmReadings();

        // Filter by reading_type (hm or km)
        if ($request->filled('reading_type')) {
            $query->where('reading_type', $request->reading_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('reading_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('reading_date', '<=', $request->date_to);
        }

        // Order and paginate
        $readings = $query->orderBy('reading_date', 'desc')
            ->orderBy('reading_type')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return response()->json($readings);
    }
}
