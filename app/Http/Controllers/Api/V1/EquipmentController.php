<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $withReadings = $request->boolean('with_readings');

        $relations = ['unitModel:id,name', 'department:id,department_name', 'plantType:id,name'];
        if ($withReadings) {
            $relations[] = 'latestHmReading';
            $relations[] = 'latestKmReading';
        }

        $equipment = Equipment::query()
            ->with($relations)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('unit_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('project_code'), fn ($q) => $q->where('project_code', $request->project_code))
            ->when($request->filled('unitstatus_id'), fn ($q) => $q->where('unitstatus_id', $request->unitstatus_id))
            ->when($request->filled('plant_type'), function ($query, $value) {
                $query->whereHas('plantType', function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%");
                });
            })
            ->orderBy('unit_code')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        $items = collect($equipment->items());
        if ($withReadings) {
            $items = $items->map(fn (Equipment $item) => $this->appendLatestReadings($item));
        }

        return $this->paginatedResponse($equipment, $items->all());
    }

    public function show(Equipment $equipment): JsonResponse
    {
        $equipment->load([
            'unitModel:id,name',
            'department:id,department_name',
            'plantType:id,name',
            'unitstatus:id,name,color',
            'assetCategory:id,name,code',
            'fixedAsset:id,equipment_id,status',
        ]);

        return response()->json(['data' => $equipment]);
    }

    public function hmKmReadings(Equipment $equipment, Request $request): JsonResponse
    {
        $query = $equipment->hmKmReadings();

        if ($request->filled('reading_type')) {
            $query->where('reading_type', $request->reading_type);
        }

        if ($request->filled('date_from')) {
            $query->where('reading_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('reading_date', '<=', $request->date_to);
        }

        $readings = $query->orderBy('reading_date', 'desc')
            ->orderBy('reading_type')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return $this->paginatedResponse($readings);
    }

    public function stats(Request $request): JsonResponse
    {
        $baseQuery = Equipment::query()
            ->when($request->filled('project_code'), fn ($q) => $q->where('project_code', $request->project_code));

        $total = (clone $baseQuery)->count();
        $rfuCount = (clone $baseQuery)->where('is_rfu', true)->count();

        $byStatus = (clone $baseQuery)
            ->join('unitstatuses', 'equipment.unitstatus_id', '=', 'unitstatuses.id')
            ->select(
                'unitstatuses.id as status_id',
                'unitstatuses.name as status_name',
                'unitstatuses.color',
                DB::raw('count(*) as count'),
            )
            ->groupBy('unitstatuses.id', 'unitstatuses.name', 'unitstatuses.color')
            ->orderBy('unitstatuses.name')
            ->get()
            ->map(fn ($row) => [
                'status_id' => $row->status_id,
                'status_name' => $row->status_name,
                'count' => (int) $row->count,
                'color' => $row->color,
            ])
            ->values();

        $byPlantType = (clone $baseQuery)
            ->join('plant_types', 'equipment.plant_type_id', '=', 'plant_types.id')
            ->select(
                'plant_types.id as plant_type_id',
                'plant_types.name as plant_type_name',
                DB::raw('count(*) as count'),
            )
            ->groupBy('plant_types.id', 'plant_types.name')
            ->orderBy('plant_types.name')
            ->get()
            ->map(fn ($row) => [
                'plant_type_id' => $row->plant_type_id,
                'plant_type_name' => $row->plant_type_name,
                'count' => (int) $row->count,
            ])
            ->values();

        return response()->json([
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'by_plant_type' => $byPlantType,
                'rfu_count' => $rfuCount,
            ],
        ]);
    }

    private function paginatedResponse(LengthAwarePaginator $paginator, ?array $items = null): JsonResponse
    {
        return response()->json([
            'data' => $items ?? $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function appendLatestReadings(Equipment $equipment): array
    {
        $data = $equipment->toArray();
        $hmDate = $equipment->latestHmReading?->reading_date;
        $kmDate = $equipment->latestKmReading?->reading_date;

        $data['latest_hm'] = $equipment->latestHmReading?->reading_value;
        $data['latest_km'] = $equipment->latestKmReading?->reading_value;
        $data['latest_reading_date'] = collect([$hmDate, $kmDate])->filter()->max()?->format('Y-m-d');

        unset($data['latest_hm_reading'], $data['latest_km_reading']);

        return $data;
    }
}
