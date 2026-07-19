<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\AI\NlqQueryService;
use App\Services\AI\OpenRouterClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NlqController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->can('view'), 403);

        return Inertia::render('Reports/Nlq', [
            'catalog' => app(NlqQueryService::class)->catalog(),
            'openRouterConfigured' => app(OpenRouterClient::class)->isConfigured(),
            'nlqEnabled' => (bool) config('nlq.enabled'),
        ]);
    }

    public function ask(Request $request, NlqQueryService $service)
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        try {
            $result = $service->ask($validated['question']);

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return back()->with('nlqResult', $result);
        } catch (\Throwable $exception) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }
}
