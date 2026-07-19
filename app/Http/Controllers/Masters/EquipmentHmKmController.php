<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentHmKmReading;
use App\Models\EquipmentHmKmUploadBatch;
use App\Services\EquipmentHmKmImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentHmKmController extends Controller
{
    public function uploadForm(Request $request): Response
    {
        abort_unless($request->user()?->can('hm-km.upload'), 403);

        return Inertia::render('Masters/Equipment/HmKm/Upload');
    }

    public function upload(Request $request, EquipmentHmKmImportService $importService): RedirectResponse
    {
        abort_unless($request->user()?->can('hm-km.upload'), 403);

        $fileHasDates = $request->boolean('file_has_dates');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'reading_date' => [
                $fileHasDates ? 'nullable' : 'required',
                'date',
                'before_or_equal:today',
            ],
            'file_has_dates' => ['nullable', 'boolean'],
        ]);

        $fallbackDate = $fileHasDates ? null : ($validated['reading_date'] ?? null);
        $batchId = (string) Str::uuid();
        $file = $request->file('file');
        $storedPath = $file->store('hm-km-uploads');

        $result = $importService->import(
            storage_path('app/'.$storedPath),
            $fallbackDate,
            $fileHasDates,
            $request->user()->id,
            $batchId,
        );

        EquipmentHmKmUploadBatch::query()->create([
            'batch_id' => $batchId,
            'original_filename' => $file->getClientOriginalName(),
            'rows_total' => $result['stats']['rows_total'],
            'rows_imported' => $result['stats']['rows_imported'],
            'rows_skipped' => $result['stats']['rows_skipped'],
            'rows_errored' => $result['stats']['rows_errored'],
            'errors' => $result['errors'],
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $imported = $result['stats']['rows_imported'];
        $skipped = $result['stats']['rows_skipped'];
        $errored = $result['stats']['rows_errored'];

        return redirect()
            ->route('equipment.hm-km.upload-form')
            ->with('success', "Imported {$imported} readings. {$skipped} skipped. {$errored} error(s).");
    }

    public function index(Request $request, Equipment $equipment): Response|JsonResponse
    {
        abort_unless($request->user()?->can('hm-km.view'), 403);

        $readings = EquipmentHmKmReading::query()
            ->where('equipment_id', $equipment->id)
            ->with('uploader:id,name')
            ->when($request->filled('reading_type'), fn ($query) => $query->where('reading_type', $request->string('reading_type')))
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($readings);
        }

        return Inertia::render('Masters/Equipment/HmKm/HistoryTab', [
            'equipment' => $equipment->only(['id', 'unit_code', 'description']),
            'readings' => $readings,
        ]);
    }

    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        abort_unless($request->user()?->can('hm-km.manual'), 403);

        $validated = $request->validate([
            'reading_type' => ['required', 'in:hm,km'],
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $duplicate = EquipmentHmKmReading::query()
            ->where('equipment_id', $equipment->id)
            ->where('reading_type', $validated['reading_type'])
            ->whereDate('reading_date', $validated['reading_date'])
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'A reading for this type and date already exists.');
        }

        EquipmentHmKmReading::query()->create([
            'equipment_id' => $equipment->id,
            'reading_type' => $validated['reading_type'],
            'reading_value' => $validated['reading_value'],
            'reading_date' => $validated['reading_date'],
            'source' => 'manual',
            'uploaded_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Reading added.');
    }

    public function destroy(Request $request, EquipmentHmKmReading $reading): RedirectResponse
    {
        abort_unless($request->user()?->can('hm-km.delete'), 403);

        $reading->delete();

        return back()->with('success', 'Reading deleted.');
    }

    public function batches(Request $request): Response
    {
        abort_unless($request->user()?->can('hm-km.view'), 403);

        $batches = EquipmentHmKmUploadBatch::query()
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Masters/Equipment/HmKm/Batches', [
            'batches' => $batches,
        ]);
    }

    public function batchDetail(Request $request, string $batch): Response
    {
        abort_unless($request->user()?->can('hm-km.view'), 403);

        $batchRecord = EquipmentHmKmUploadBatch::query()
            ->with('uploader:id,name')
            ->where('batch_id', $batch)
            ->firstOrFail();

        return Inertia::render('Masters/Equipment/HmKm/BatchDetail', [
            'batch' => $batchRecord,
        ]);
    }
}
