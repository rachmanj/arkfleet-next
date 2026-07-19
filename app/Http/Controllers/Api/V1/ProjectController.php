<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->when($request->boolean('selectable_only'), fn ($q) => $q->selectable())
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->when($request->search, fn ($q, $s) => $q->where(function ($inner) use ($s) {
                $inner->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%");
            }))
            ->orderBy('code')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return response()->json($projects);
    }

    public function show(string $code)
    {
        $project = Project::query()->where('code', $code)->firstOrFail();

        return response()->json(['data' => $project]);
    }
}
