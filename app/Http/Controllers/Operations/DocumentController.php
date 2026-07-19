<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $documents = EquipmentDocument::query()
            ->with(['equipment:id,unit_code', 'documentType:id,name,code'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('document_number', 'like', "%{$search}%")
                        ->orWhereHas('equipment', fn ($q) => $q->where('unit_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->status === 'expiring', fn ($q) => $q->expiringWithin(30))
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->orderBy('expiry_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Operations/Documents/Index', [
            'documents' => $documents,
            'documentTypes' => DocumentType::query()->where('is_active', true)->orderBy('name')->get(),
            'equipmentOptions' => Equipment::query()->where('is_active', true)->orderBy('unit_code')->get(['id', 'unit_code']),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('documents', 'public');
        }

        unset($validated['file']);
        EquipmentDocument::create($validated);

        return back()->with('success', 'Document recorded.');
    }

    public function update(Request $request, EquipmentDocument $document): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('documents', 'public');
        }

        unset($validated['file']);
        $document->update($validated);

        return back()->with('success', 'Document updated.');
    }

    public function extend(Request $request, EquipmentDocument $document): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'extend_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'new_expiry_date' => ['nullable', 'date'],
        ]);

        $newExpiry = $validated['new_expiry_date']
            ?? $document->expiry_date?->copy()->addDays($validated['extend_days'])
            ?? now()->addDays($validated['extend_days']);

        $document->update([
            'expiry_date' => $newExpiry,
            'extend_count' => $document->extend_count + 1,
        ]);

        return back()->with('success', 'Document expiry extended.');
    }

    public function destroy(EquipmentDocument $document): RedirectResponse
    {
        abort_unless(request()->user()?->can('view'), 403);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
