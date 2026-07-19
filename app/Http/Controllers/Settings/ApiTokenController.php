<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        return Inertia::render('Settings/ApiKeys/Index', [
            'tokens' => $request->user()->tokens()
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']),
            'apiDocs' => [
                'base_url' => url('/api/v1'),
                'auth' => 'Authorization: Bearer {token}',
                'endpoints' => [
                    'GET /equipment',
                    'GET /equipment/{id}',
                    'GET /projects',
                    'GET /projects/{code}',
                    'GET /fixed-assets',
                    'GET /fixed-assets/{id}',
                    'GET /depreciation/runs',
                    'GET /depreciation/runs/{id}',
                    'GET /depreciation/entries',
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $token = $request->user()->createToken($validated['name'], ['api:read']);

        return back()->with([
            'success' => 'API token created. Copy it now — it will not be shown again.',
            'newToken' => $token->plainTextToken,
        ]);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'API token revoked.');
    }
}
