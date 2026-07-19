<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Sap\SapDepartmentSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeView();

        $departments = Department::query()
            ->with('parent:id,department_name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('department_name', 'like', "%{$search}%")
                        ->orWhere('sap_code', 'like', "%{$search}%")
                        ->orWhere('akronim', 'like', "%{$search}%");
                });
            })
            ->orderBy('department_name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Masters/Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only('search'),
        ]);
    }

    public function sync(Request $request, SapDepartmentSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->can('sync'), 403);

        $result = $syncService->sync($request->user()->id);

        return back()->with(
            'success',
            "Department sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.",
        );
    }

    public function toggleVisibility(Department $department): RedirectResponse
    {
        abort_unless(request()->user()->can('manage-visibility'), 403);

        $department->update(['is_selectable' => ! $department->is_selectable]);

        return back()->with('success', 'Department visibility updated.');
    }

    protected function authorizeView(): void
    {
        abort_unless(request()->user()?->can('view'), 403);
    }
}
