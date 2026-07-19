<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\DepreciationRun;
use App\Services\Depreciation\DepreciationPostingService;
use App\Services\Depreciation\DepreciationRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepreciationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        return Inertia::render('Finance/Depreciation/Index', [
            'runs' => DepreciationRun::query()
                ->with('runner:id,name')
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->paginate(15)
                ->withQueryString(),
            'sapPostingEnabled' => app(DepreciationPostingService::class)->isEnabled(),
        ]);
    }

    public function run(Request $request, DepreciationRunService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'book_scope' => ['nullable', 'in:all,book,tax'],
        ]);

        $run = $service->runPeriod(
            $validated['period_year'],
            $validated['period_month'],
            $validated['book_scope'] ?? 'all',
            $request->user()->id,
        );

        return redirect()
            ->route('depreciation.show', $run)
            ->with('success', "Depreciation run {$run->periodLabel()} completed ({$run->entry_count} entries).");
    }

    public function show(DepreciationRun $run): Response
    {
        abort_unless(request()->user()?->can('view'), 403);

        $run->load(['entries.fixedAsset.equipment', 'runner:id,name']);

        return Inertia::render('Finance/Depreciation/Show', [
            'run' => $run,
            'journalPreview' => app(DepreciationPostingService::class)->buildJournalPreview($run),
            'sapPostingEnabled' => app(DepreciationPostingService::class)->isEnabled(),
        ]);
    }

    public function confirm(DepreciationRun $run, DepreciationRunService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('sap.post'), 403);

        $service->confirmRun($run);

        return back()->with('success', 'Depreciation run confirmed for SAP posting.');
    }

    public function postToSap(DepreciationRun $run, DepreciationPostingService $postingService, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('sap.post'), 403);

        $result = $postingService->postToSap($run, $request->user()->id);

        if ($result['log']->status === 'success') {
            return back()->with('success', "Journal posted to SAP (DocNum: {$result['log']->doc_num}).");
        }

        return back()->with('error', 'SAP posting failed. Check posting logs.');
    }

    public function deferredTax(): Response
    {
        abort_unless(request()->user()?->can('view'), 403);

        $rows = app(DepreciationRunService::class)->deferredTaxReport();
        $totalDeferred = collect($rows)->sum('deferred_tax');

        return Inertia::render('Finance/Depreciation/DeferredTax', [
            'rows' => $rows,
            'totalDeferredTax' => round($totalDeferred, 2),
        ]);
    }
}
