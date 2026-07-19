<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentPhoto;
use App\Models\IpaTransferLine;
use App\Models\Project;
use App\Models\UnitNoHistory;
use App\Models\Unitstatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $activeStatusId = Unitstatus::query()->where('name', 'Active')->value('id');

        $equipment = Equipment::query()
            ->with(['unitModel:id,name', 'manufacture:id,name', 'unitstatus:id,name'])
            ->when($request->filled('unit_code'), fn ($query) => $query->where('unit_code', 'like', '%'.$request->string('unit_code').'%'))
            ->when($request->filled('description'), fn ($query) => $query->where('description', 'like', '%'.$request->string('description').'%'))
            ->when($request->filled('project_code'), fn ($query) => $query->where('project_code', $request->string('project_code')))
            ->when($request->filled('acquisition_cost_min'), fn ($query) => $query->where('acquisition_cost', '>=', $request->input('acquisition_cost_min')))
            ->when($request->filled('acquisition_cost_max'), fn ($query) => $query->where('acquisition_cost', '<=', $request->input('acquisition_cost_max')))
            ->when(
                $request->has('is_active') && $request->input('is_active') !== '',
                fn ($query) => $query->where('is_active', $request->boolean('is_active')),
            )
            ->when(
                $request->filled('status') && $activeStatusId,
                function ($query) use ($request, $activeStatusId) {
                    if ($request->string('status') === 'rfu') {
                        $query->where('unitstatus_id', $activeStatusId)->where('is_rfu', true);
                    } elseif ($request->string('status') === 'bd') {
                        $query->where('unitstatus_id', $activeStatusId)->where(function ($inner) {
                            $inner->where('is_rfu', false)->orWhereNull('is_rfu');
                        });
                    }
                },
            )
            ->orderBy('unit_code')
            ->paginate(20)
            ->withQueryString();

        $candidateQuery = Equipment::query()
            ->select(['id', 'unit_code', 'description', 'is_rfu'])
            ->when($activeStatusId, fn ($query) => $query->where('unitstatus_id', $activeStatusId))
            ->orderBy('unit_code');

        return Inertia::render('Masters/Equipment/Index', [
            'equipment' => $equipment,
            'filters' => $request->only([
                'unit_code',
                'description',
                'project_code',
                'acquisition_cost_min',
                'acquisition_cost_max',
                'is_active',
                'status',
            ]),
            'projects' => Project::selectable()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'rfuCandidates' => (clone $candidateQuery)
                ->where(function ($query) {
                    $query->where('is_rfu', false)->orWhereNull('is_rfu');
                })
                ->get()
                ->map(fn (Equipment $item) => [
                    'value' => $item->id,
                    'label' => trim($item->unit_code.' — '.($item->description ?? '')),
                ])
                ->values(),
            'bdCandidates' => (clone $candidateQuery)
                ->where('is_rfu', true)
                ->get()
                ->map(fn (Equipment $item) => [
                    'value' => $item->id,
                    'label' => trim($item->unit_code.' — '.($item->description ?? '')),
                ])
                ->values(),
        ]);
    }

    public function show(Request $request, Equipment $equipment): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $equipment->load([
            'unitModel',
            'manufacture',
            'plantType',
            'plantGroup',
            'assetCategory',
            'unitstatus',
            'supplier',
            'department',
            'project',
            'documents' => fn ($query) => $query->with(['documentType', 'supplier'])->orderByDesc('issued_date'),
            'photos.uploader',
            'unitNoHistories.creator',
        ]);

        if ($request->user()?->can('hm-km.view')) {
            $equipment->load([
                'hmKmReadings' => fn ($query) => $query
                    ->with('uploader:id,name')
                    ->orderByDesc('reading_date')
                    ->orderByDesc('created_at')
                    ->limit(50),
                'latestHmReading',
                'latestKmReading',
            ]);
        }

        $movingLines = IpaTransferLine::query()
            ->with([
                'transfer:id,transfer_number,transferred_at',
                'fromDepartment:id,department_name',
                'toDepartment:id,department_name',
            ])
            ->where('equipment_id', $equipment->id)
            ->latest('id')
            ->get();

        return Inertia::render('Masters/Equipment/Show', [
            'equipment' => $equipment,
            'movingLines' => $movingLines,
            'latestHmReading' => $equipment->latestHmReading,
            'latestKmReading' => $equipment->latestKmReading,
            'projects' => Project::selectable()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'payreqEnabled' => filled(config('services.payreq.api_url')),
        ]);
    }

    public function payreqSummary(Request $request, Equipment $equipment): JsonResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $apiUrl = config('services.payreq.api_url');

        if (! filled($apiUrl)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payreq is not configured. Set PAYREQ_API_URL in .env.',
            ], 503);
        }

        try {
            $response = Http::timeout(15)
                ->get(rtrim($apiUrl, '/').'/api/realization-details/sum-by-unit', [
                    'unit_no' => $equipment->unit_code,
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payreq service returned HTTP '.$response->status(),
                ], 502);
            }

            $payload = $response->json();

            if (($payload['status'] ?? null) !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payreq service returned an error',
                ], 502);
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not connect to Payreq service',
            ], 502);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate($this->equipmentRules());

        Equipment::create($validated);

        return back()->with('success', 'Equipment created.');
    }

    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate($this->equipmentRules($equipment));

        $equipment->update($validated);

        return back()->with('success', 'Equipment updated.');
    }

    public function updateRfu(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'equipments' => ['required', 'array', 'min:1'],
            'equipments.*' => ['integer', 'exists:equipment,id'],
        ]);

        $activeStatusId = Unitstatus::query()->where('name', 'Active')->value('id');

        if (! $activeStatusId) {
            return back()->with('error', 'Active unit status is not configured.');
        }

        $updated = Equipment::query()
            ->whereIn('id', $validated['equipments'])
            ->where('unitstatus_id', $activeStatusId)
            ->update(['is_rfu' => true]);

        return back()->with('success', "{$updated} equipment(s) updated to RFU.");
    }

    public function updateBd(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'equipments' => ['required', 'array', 'min:1'],
            'equipments.*' => ['integer', 'exists:equipment,id'],
        ]);

        $activeStatusId = Unitstatus::query()->where('name', 'Active')->value('id');

        if (! $activeStatusId) {
            return back()->with('error', 'Active unit status is not configured.');
        }

        $updated = Equipment::query()
            ->whereIn('id', $validated['equipments'])
            ->where('unitstatus_id', $activeStatusId)
            ->update(['is_rfu' => false]);

        return back()->with('success', "{$updated} equipment(s) updated to B/D.");
    }

    public function storePhoto(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'file' => ['required', 'image', 'max:5120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('file')->store('equipment-photos', 'public');

        $equipment->photos()->create([
            'file_path' => $path,
            'description' => $validated['description'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Photo uploaded.');
    }

    public function destroyPhoto(Request $request, EquipmentPhoto $photo): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        if ($photo->file_path) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }

    public function storeUnitNoHistory(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'new_unit_code' => ['required', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        UnitNoHistory::create([
            'equipment_id' => $equipment->id,
            'date' => $validated['date'],
            'old_unit_code' => $equipment->unit_code,
            'new_unit_code' => $validated['new_unit_code'],
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Unit code change recorded.');
    }

    protected function equipmentRules(?Equipment $equipment = null): array
    {
        $uniqueUnitCode = $equipment
            ? 'unique:equipment,unit_code,'.$equipment->id
            : 'unique:equipment,unit_code';

        return [
            'unit_code' => ['required', 'string', 'max:50', $uniqueUnitCode],
            'description' => ['nullable', 'string', 'max:255'],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'chasis_no' => ['nullable', 'string', 'max:255'],
            'engine_model' => ['nullable', 'string', 'max:255'],
            'machine_no' => ['nullable', 'string', 'max:255'],
            'nomor_polisi' => ['nullable', 'string', 'max:255'],
            'bahan_bakar' => ['nullable', 'string', 'max:255'],
            'warna' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'acquisition_date' => ['nullable', 'date'],
            'in_service_date' => ['nullable', 'date'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_rfu' => ['boolean'],
        ];
    }
}
