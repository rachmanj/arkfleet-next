<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Sap\SapProjectSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeView();

        $projects = Project::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('sap_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Masters/Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only('search'),
        ]);
    }

    public function sync(Request $request, SapProjectSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->can('sync'), 403);

        $result = $syncService->sync($request->user()->id);

        return back()->with(
            'success',
            "Project sync complete: {$result['created']} created, {$result['updated']} updated, {$result['failed']} failed.",
        );
    }

    public function toggleVisibility(Project $project): RedirectResponse
    {
        abort_unless(request()->user()->can('manage-visibility'), 403);

        $project->update(['is_selectable' => ! $project->is_selectable]);

        return back()->with('success', 'Project visibility updated.');
    }

    protected function authorizeView(): void
    {
        abort_unless(request()->user()?->can('view'), 403);
    }
}
