<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\IpaTransfer;
use App\Models\Project;
use App\Services\Operations\IpaTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MovingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $filters = $request->only([
            'search',
            'ipa_no',
            'date_from',
            'date_to',
            'from_project_code',
            'to_project_code',
            'status',
            'unit_code',
        ]);

        $transfers = IpaTransfer::query()
            ->with(['user:id,name', 'fromProject:code,name', 'toProject:code,name'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('ipa_no', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('from_project_code', 'like', "%{$search}%")
                        ->orWhere('to_project_code', 'like', "%{$search}%")
                        ->orWhereHas('lines', fn ($line) => $line->where('unit_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->ipa_no, fn ($query, $value) => $query->where('ipa_no', 'like', "%{$value}%"))
            ->when($request->date_from, fn ($query, $value) => $query->whereDate('ipa_date', '>=', $value))
            ->when($request->date_to, fn ($query, $value) => $query->whereDate('ipa_date', '<=', $value))
            ->when($request->from_project_code, fn ($query, $value) => $query->where('from_project_code', $value))
            ->when($request->to_project_code, fn ($query, $value) => $query->where('to_project_code', $value))
            ->when($request->status, fn ($query, $value) => $query->whereIn('status', (array) $value))
            ->when($request->unit_code, fn ($query, $value) => $query->whereHas(
                'lines',
                fn ($line) => $line->where('unit_code', 'like', "%{$value}%")
            ))
            ->orderByDesc('ipa_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Operations/Movings/Index', [
            'transfers' => $transfers,
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, IpaTransferService $service): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        return Inertia::render('Operations/Movings/Create', [
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'suggestedIpaNo' => $service->generateTransferNumber(),
        ]);
    }

    public function store(Request $request, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $this->validateIpaHeader($request);

        $moving = $service->createIpa($request->user()->id, $validated);

        return redirect()
            ->route('movings.equipment', $moving)
            ->with('success', 'IPA draft created. Add equipment to continue.');
    }

    public function edit(Request $request, IpaTransfer $moving): Response
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        return Inertia::render('Operations/Movings/Edit', [
            'moving' => $moving,
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
        ]);
    }

    public function update(Request $request, IpaTransfer $moving, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        $validated = $this->validateIpaHeader($request, $moving);

        $service->updateIpa($moving, $validated);

        return back()->with('success', 'IPA updated.');
    }

    public function destroy(Request $request, IpaTransfer $moving): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        $moving->delete();

        return redirect()
            ->route('movings.index')
            ->with('success', 'Draft IPA deleted.');
    }

    public function addEquipment(Request $request, IpaTransfer $moving): Response
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        $moving->load(['fromDepartment', 'toDepartment', 'fromProject', 'toProject']);

        return Inertia::render('Operations/Movings/AddEquipment', [
            'moving' => $moving,
            'cartItems' => CartItem::query()
                ->with(['equipment.unitModel', 'equipment.department', 'toDepartment'])
                ->where('ipa_transfer_id', $moving->id)
                ->latest()
                ->get(),
            'availableEquipment' => Equipment::query()
                ->with(['unitModel', 'department'])
                ->where('is_active', true)
                ->when($moving->from_project_code, fn ($query) => $query->where('project_code', $moving->from_project_code))
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('unit_code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->orderBy('unit_code')
                ->paginate(15)
                ->withQueryString(),
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function addToCart(Request $request, IpaTransfer $moving, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        $validated = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'to_project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service->addEquipment($moving, $request->user()->id, $validated);

        return back()->with('success', 'Equipment added to cart.');
    }

    public function removeFromCart(Request $request, IpaTransfer $moving, CartItem $cartItem, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);
        abort_unless($cartItem->ipa_transfer_id === $moving->id, 403);

        $service->removeEquipment($cartItem);

        return back()->with('success', 'Item removed from cart.');
    }

    public function submit(Request $request, IpaTransfer $moving, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        $this->ensureDraft($moving);

        $transfer = $service->submitIpa($moving);

        return redirect()
            ->route('movings.show', $transfer)
            ->with('success', "IPA {$transfer->ipa_no} submitted.");
    }

    public function approve(Request $request, IpaTransfer $moving, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $service->approveIpa($moving, $request->user()->id);

        return back()->with('success', 'IPA approved.');
    }

    public function show(Request $request, IpaTransfer $moving): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $moving->load([
            'lines.equipment',
            'lines.fromDepartment',
            'lines.toDepartment',
            'fromProject',
            'toProject',
            'approvedBy',
            'user',
            'fromDepartment',
            'toDepartment',
        ]);

        return Inertia::render('Operations/Movings/Show', [
            'moving' => $moving,
        ]);
    }

    public function pdf(Request $request, IpaTransfer $moving, IpaTransferService $service): HttpResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        return $service->transferPdf($moving)->download("{$moving->ipa_no}.pdf");
    }

    private function validateIpaHeader(Request $request, ?IpaTransfer $moving = null): array
    {
        return $request->validate([
            'ipa_no' => [
                'required',
                'string',
                'max:30',
                Rule::unique('ipa_transfers', 'ipa_no')->ignore($moving?->id),
            ],
            'ipa_date' => ['required', 'date'],
            'from_project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'to_project_code' => ['required', 'string', 'max:20', 'exists:projects,code'],
            'from_department_id' => ['nullable', 'exists:departments,id'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'tujuan_row_1' => ['required', 'string', 'max:255'],
            'tujuan_row_2' => ['nullable', 'string', 'max:255'],
            'cc_row_1' => ['required', 'string', 'max:255'],
            'cc_row_2' => ['nullable', 'string', 'max:255'],
            'cc_row_3' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function ensureDraft(IpaTransfer $moving): void
    {
        if (! $moving->isDraft()) {
            throw new HttpException(403, 'Only draft IPAs can be modified.');
        }
    }
}
