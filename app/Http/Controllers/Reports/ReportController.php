<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Models\IpaTransfer;
use App\Models\Unitstatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->can('view'), 403);

        return Inertia::render('Reports/Index');
    }

    public function expiringDocuments(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $days = (int) $request->input('days', 30);

        $documents = EquipmentDocument::query()
            ->with(['equipment:id,unit_code', 'documentType:id,name'])
            ->expiringWithin($days)
            ->orderBy('expiry_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Reports/ExpiringDocuments', [
            'documents' => $documents,
            'days' => $days,
        ]);
    }

    public function ipaSummary(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $transfers = IpaTransfer::query()
            ->with(['user:id,name', 'toDepartment:id,department_name'])
            ->when($request->from, fn ($q, $from) => $q->whereDate('transferred_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('transferred_at', '<=', $to))
            ->latest('transferred_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Reports/IpaSummary', [
            'transfers' => $transfers,
            'filters' => $request->only('from', 'to'),
        ]);
    }

    public function activeEquipmentStatus(Request $request): Response
    {
        abort_unless($request->user()?->can('view'), 403);

        $equipment = Equipment::query()
            ->with(['unitModel', 'department', 'unitstatus'])
            ->where('is_active', true)
            ->when($request->project_code, fn ($q, $code) => $q->where('project_code', $code))
            ->when($request->unitstatus_id, fn ($q, $id) => $q->where('unitstatus_id', $id))
            ->orderBy('unit_code')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Reports/ActiveEquipmentStatus', [
            'equipment' => $equipment,
            'unitstatuses' => Unitstatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('project_code', 'unitstatus_id'),
        ]);
    }

    public function exportExpiringDocuments(Request $request, string $format): HttpResponse|BinaryFileResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $days = (int) $request->input('days', 30);

        $rows = EquipmentDocument::query()
            ->with(['equipment', 'documentType'])
            ->expiringWithin($days)
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($doc) => [
                $doc->equipment?->unit_code,
                $doc->documentType?->name,
                $doc->document_number,
                $doc->issued_date?->format('Y-m-d'),
                $doc->expiry_date?->format('Y-m-d'),
            ]);

        return $this->exportResponse(
            $format,
            'expiring-documents',
            ['Unit Code', 'Document Type', 'Document No', 'Issued', 'Expiry'],
            $rows,
            'reports.pdf.expiring-documents',
            compact('rows', 'days'),
        );
    }

    public function exportIpaSummary(Request $request, string $format): HttpResponse|BinaryFileResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $rows = IpaTransfer::query()
            ->with(['user', 'toDepartment'])
            ->when($request->from, fn ($q, $from) => $q->whereDate('transferred_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('transferred_at', '<=', $to))
            ->latest('transferred_at')
            ->get()
            ->map(fn ($t) => [
                $t->transfer_number,
                $t->transferred_at->format('Y-m-d H:i'),
                $t->user?->name,
                $t->from_project_code,
                $t->to_project_code,
                $t->toDepartment?->department_name,
                $t->line_count,
            ]);

        return $this->exportResponse(
            $format,
            'ipa-summary',
            ['Transfer No', 'Date', 'User', 'From Project', 'To Project', 'To Department', 'Lines'],
            $rows,
            'reports.pdf.ipa-summary',
            compact('rows'),
        );
    }

    public function exportActiveEquipment(Request $request, string $format): HttpResponse|BinaryFileResponse
    {
        abort_unless($request->user()?->can('view'), 403);

        $rows = Equipment::query()
            ->with(['unitModel', 'department', 'unitstatus'])
            ->where('is_active', true)
            ->when($request->project_code, fn ($q, $code) => $q->where('project_code', $code))
            ->orderBy('unit_code')
            ->get()
            ->map(fn ($e) => [
                $e->unit_code,
                $e->unitModel?->name,
                $e->project_code,
                $e->department?->department_name,
                $e->unitstatus?->name,
            ]);

        return $this->exportResponse(
            $format,
            'active-equipment',
            ['Unit Code', 'Model', 'Project', 'Department', 'Status'],
            $rows,
            'reports.pdf.table',
            ['title' => 'Active Equipment Status', 'headings' => ['Unit Code', 'Model', 'Project', 'Department', 'Status'], 'rows' => $rows],
        );
    }

    protected function exportResponse(
        string $format,
        string $basename,
        array $headings,
        $rows,
        string $pdfView,
        array $pdfData,
    ): HttpResponse|BinaryFileResponse {
        if ($format === 'excel') {
            return Excel::download(new ArrayExport(collect($rows), $headings), "{$basename}.xlsx");
        }

        return Pdf::loadView($pdfView, $pdfData)->download("{$basename}.pdf");
    }
}
