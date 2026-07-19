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
            ->with(['unitModel:id,name', 'department:id,department_name'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('unit_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('unit_code')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($equipment);
    }

    public function show(Equipment $equipment)
    {
        $equipment->load(['unitModel:id,name', 'department:id,department_name', 'fixedAsset:id,equipment_id,status']);

        return response()->json(['data' => $equipment]);
    }
}
