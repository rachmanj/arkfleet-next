<?php

namespace App\Http\Controllers\Sap;

use App\Http\Controllers\Controller;
use App\Models\SapPostingLog;
use App\Models\SapSyncRun;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SapIntegrationController extends Controller
{
    public function syncRuns(Request $request): Response
    {
        abort_unless($request->user()?->can('sync'), 403);

        return Inertia::render('Sap/SyncRuns', [
            'runs' => SapSyncRun::query()
                ->with('triggeredBy:id,name')
                ->orderByDesc('started_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function postingLogs(Request $request): Response
    {
        abort_unless($request->user()?->can('sap.post'), 403);

        return Inertia::render('Sap/PostingLogs', [
            'logs' => SapPostingLog::query()
                ->with('postedBy:id,name')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
