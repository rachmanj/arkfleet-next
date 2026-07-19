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
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MovingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $userId = $request->user()->id;

        return Inertia::render('Operations/Movings/Index', [
            'cartItems' => CartItem::query()
                ->with(['equipment.unitModel', 'equipment.department', 'toDepartment'])
                ->where('user_id', $userId)
                ->latest()
                ->get(),
            'availableEquipment' => Equipment::query()
                ->with(['unitModel', 'department'])
                ->where('is_active', true)
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('unit_code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->orderBy('unit_code')
                ->paginate(15)
                ->withQueryString(),
            'transfers' => IpaTransfer::query()
                ->with(['user:id,name', 'toDepartment:id,department_name'])
                ->latest('transferred_at')
                ->paginate(10, ['*'], 'transfers_page')
                ->withQueryString(),
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'to_project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        CartItem::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'equipment_id' => $validated['equipment_id'],
            ],
            [
                'to_project_code' => $validated['to_project_code'] ?? null,
                'to_department_id' => $validated['to_department_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return back()->with('success', 'Equipment added to transfer cart.');
    }

    public function removeFromCart(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    public function submit(Request $request, IpaTransferService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'from_project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'to_project_code' => ['required', 'string', 'max:20', 'exists:projects,code'],
            'from_department_id' => ['nullable', 'exists:departments,id'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $service->submitTransfer($request->user()->id, $validated);

        return redirect()
            ->route('movings.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} completed.");
    }

    public function show(Request $request, IpaTransfer $transfer): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $transfer->load(['lines.fromDepartment', 'lines.toDepartment', 'user', 'fromDepartment', 'toDepartment']);

        return Inertia::render('Operations/Movings/Show', [
            'transfer' => $transfer,
        ]);
    }

    public function pdf(IpaTransfer $transfer, IpaTransferService $service): HttpResponse
    {
        abort_unless(request()->user()?->can('view'), 403);

        return $service->transferPdf($transfer)->download("{$transfer->transfer_number}.pdf");
    }
}
