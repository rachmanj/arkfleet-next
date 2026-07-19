<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Loan;
use App\Models\LoanDocument;
use App\Models\LoanInstallment;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Services\Loans\LoanInstallmentService;
use App\Services\Loans\LoanPdfParserService;
use App\Services\Loans\LoanPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $loans = Loan::query()
            ->withCount('installments')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('contract_number', 'like', "%{$search}%")
                        ->orWhere('vendor_card_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance/Loans/Index', [
            'loans' => $loans,
            'filters' => $request->only('search'),
            'vendors' => SapBusinessPartner::query()->active()->ofType('S')->orderBy('card_name')->get(['card_code', 'card_name']),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
            'defaults' => config('loans.defaults'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'vendor_card_code' => ['required', 'string', 'max:20', 'exists:sap_business_partners,card_code'],
            'contract_number' => ['required', 'string', 'max:50', 'unique:loans,contract_number'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'term_months' => ['required', 'integer', 'min:1', 'max:600'],
            'currency' => ['nullable', 'string', 'max:10'],
            'principal_gl' => ['nullable', 'string', 'max:20'],
            'interest_gl' => ['nullable', 'string', 'max:20'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $loan = Loan::create(array_merge($validated, [
            'currency' => $validated['currency'] ?? config('loans.defaults.currency'),
            'principal_gl' => $validated['principal_gl'] ?? config('loans.defaults.principal_gl'),
            'interest_gl' => $validated['interest_gl'] ?? config('loans.defaults.interest_gl'),
            'tax_code' => $validated['tax_code'] ?? config('loans.defaults.tax_code'),
            'status' => 'draft',
        ]));

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan created.');
    }

    public function show(Request $request, Loan $loan): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $loan->load([
            'installments',
            'documents' => fn ($q) => $q->with('uploader:id,name')->latest(),
            'department:id,department_name',
        ]);

        return Inertia::render('Finance/Loans/Show', [
            'loan' => $loan,
            'sapPostingEnabled' => app(LoanPostingService::class)->isEnabled(),
            'departments' => Department::selectable()->active()->orderBy('department_name')->get(['id', 'department_name']),
            'projects' => Project::selectable()->active()->orderBy('code')->get(['code', 'name']),
        ]);
    }

    public function update(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        abort_if($loan->isScheduleLocked(), 422, 'Loan schedule is locked.');

        $validated = $request->validate([
            'principal_gl' => ['nullable', 'string', 'max:20'],
            'interest_gl' => ['nullable', 'string', 'max:20'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_code' => ['nullable', 'string', 'max:20', 'exists:projects,code'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $loan->update($validated);

        return back()->with('success', 'Loan updated.');
    }

    public function uploadDocument(Request $request, Loan $loan, LoanPdfParserService $parser): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('file')->store('loans', 'public');
        $parseResult = $parser->parse($path);

        LoanDocument::create([
            'loan_id' => $loan->id,
            'file_path' => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'parsed_data' => $parseResult,
            'uploaded_by' => $request->user()->id,
        ]);

        $message = $parseResult['success']
            ? $parseResult['message']
            : $parseResult['message'].' Upload saved — add installments manually or edit parsed rows.';

        return back()->with('success', $message);
    }

    public function confirmParsedSchedule(Request $request, Loan $loan, LoanInstallmentService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $validated = $request->validate([
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.installment_no' => ['required', 'integer', 'min:1'],
            'installments.*.due_date' => ['nullable', 'date'],
            'installments.*.principal_amount' => ['required', 'numeric', 'min:0'],
            'installments.*.interest_amount' => ['required', 'numeric', 'min:0'],
            'installments.*.total_installments' => ['nullable', 'integer', 'min:1'],
        ]);

        foreach ($validated['installments'] as &$row) {
            $row['total_installments'] = $row['total_installments'] ?? $loan->term_months;
        }

        $count = $service->createDraftInstallments($loan, $validated['installments']);

        $loan->documents()->latest()->first()?->update(['is_confirmed' => true]);

        return back()->with('success', "{$count} draft installment(s) created. Review and confirm each before SAP posting.");
    }

    public function updateInstallment(Request $request, Loan $loan, LoanInstallment $installment, LoanInstallmentService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('view'), 403);
        abort_unless($installment->loan_id === $loan->id, 404);

        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
            'posting_date' => ['nullable', 'date'],
            'document_date' => ['nullable', 'date'],
            'principal_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_amount' => ['nullable', 'numeric', 'min:0'],
            'principal_gl' => ['nullable', 'string', 'max:20'],
            'interest_gl' => ['nullable', 'string', 'max:20'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_code' => ['nullable', 'string', 'max:20'],
            'faktur_pajak_no' => ['nullable', 'string', 'max:50'],
            'faktur_pajak_date' => ['nullable', 'date'],
            'vendor_ref_no' => ['nullable', 'string', 'max:50'],
        ]);

        $service->updateInstallment($installment, $validated);

        return back()->with('success', 'Installment updated.');
    }

    public function confirmInstallment(Loan $loan, LoanInstallment $installment, LoanInstallmentService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('view'), 403);
        abort_unless($installment->loan_id === $loan->id, 404);

        $service->confirmInstallment($installment);

        return back()->with('success', "Installment {$installment->installmentLabel()} confirmed.");
    }

    public function postApInvoice(Request $request, Loan $loan, LoanInstallment $installment, LoanPostingService $posting): RedirectResponse
    {
        abort_unless($request->user()?->can('sap.post'), 403);
        abort_unless($installment->loan_id === $loan->id, 404);

        $result = $posting->postApInvoice($installment, $request->user()->id);

        if ($result['log']->status === 'success') {
            return back()->with('success', "AP Invoice posted (DocNum: {$result['log']->doc_num}). Schedule locked.");
        }

        return back()->with('error', 'SAP AP Invoice posting failed. Check posting logs.');
    }

    public function postPayment(Request $request, Loan $loan, LoanInstallment $installment, LoanPostingService $posting): RedirectResponse
    {
        abort_unless($request->user()?->can('sap.post'), 403);
        abort_unless($installment->loan_id === $loan->id, 404);

        $result = $posting->postOutgoingPayment($installment, $request->user()->id);

        if ($result['log']->status === 'success') {
            return back()->with('success', "Outgoing payment posted (DocNum: {$result['log']->doc_num}).");
        }

        return back()->with('error', 'SAP outgoing payment failed. Check posting logs.');
    }

    public function downloadDocument(Loan $loan, LoanDocument $document)
    {
        abort_unless(request()->user()?->can('view'), 403);
        abort_unless($document->loan_id === $loan->id, 404);

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }
}
