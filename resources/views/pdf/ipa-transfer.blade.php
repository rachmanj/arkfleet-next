<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>IPA {{ $transfer->ipa_no }}</title>
    <style>
        @page { margin: 24px 28px 40px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.4; }
        h1, h2, h3 { margin: 0; padding: 0; }
        .company-name { font-size: 16px; font-weight: bold; text-align: center; margin-top: 4px; }
        .doc-title { font-size: 13px; font-weight: bold; text-align: center; margin-top: 8px; }
        .doc-no { font-size: 12px; text-align: center; margin-top: 4px; }
        hr { border: none; border-top: 1px solid #333; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        .borderless td { border: none; padding: 2px 8px 2px 0; vertical-align: top; }
        .direction th, .direction td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        .direction th { background: #f0f0f0; font-weight: bold; }
        .equipment th, .equipment td { border: 1px solid #333; padding: 5px 6px; text-align: left; font-size: 10px; }
        .equipment th { background: #f0f0f0; font-weight: bold; }
        .equipment tr:nth-child(even) td { background: #fafafa; }
        .intro { margin: 10px 0; }
        .signature { width: 35%; margin-left: auto; margin-top: 24px; text-align: center; }
        .signature .space { height: 70px; }
        .footer { margin-top: 36px; font-size: 9px; color: #666; }
        .footer td { width: 33%; text-align: center; border: none; padding: 0; }
        .logo-cell { width: 120px; vertical-align: middle; }
        .logo { max-width: 100px; max-height: 60px; }
        .header-center { text-align: center; }
    </style>
</head>
<body>
@php
    $formatProject = function ($project, $code) {
        if (! $project) {
            return $code ?? '—';
        }

        $parts = [$project->code];

        if ($project->bowheer || $project->location) {
            $detail = trim(implode(', ', array_filter([$project->bowheer, $project->location])));
            if ($detail !== '') {
                $parts[] = $detail;
            }
        } else {
            $parts[] = $project->name;
        }

        return implode(' - ', array_filter($parts));
    };

    $ipaDate = $transfer->ipa_date->locale('id')->translatedFormat('d F Y');
@endphp

<table class="borderless" style="margin-bottom: 8px;">
    <tr>
        <td class="logo-cell">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" class="logo">
            @endif
        </td>
        <td class="header-center">
            <div class="company-name">PT ARKANANTA APTA PRATISTA</div>
            <div class="doc-title">INSTRUKSI PEMINDAHAN ALAT (IPA)</div>
            <div class="doc-no">No. {{ $transfer->ipa_no }}</div>
        </td>
        <td style="width: 120px;"></td>
    </tr>
</table>

<hr>

<table class="borderless">
    <tr>
        <td style="width: 40%;">
            <strong>Kepada Yth.</strong><br>
            - {{ $transfer->tujuan_row_1 }}<br>
            @if($transfer->tujuan_row_2)
                - {{ $transfer->tujuan_row_2 }}<br>
            @endif
        </td>
        <td style="width: 60%;">
            <strong>CC</strong><br>
            - {{ $transfer->cc_row_1 }}<br>
            @if($transfer->cc_row_2)
                - {{ $transfer->cc_row_2 }}<br>
            @endif
            @if($transfer->cc_row_3)
                - {{ $transfer->cc_row_3 }}<br>
            @endif
        </td>
    </tr>
</table>

<hr>

<div class="intro">
    <p>Dengan hormat,</p>
    <p>Sesuai dengan kebutuhan Operasional Perusahaan, mohon segera dilakukan pemindahan alat sbb.:</p>
</div>

<table class="direction" style="margin-top: 8px;">
    <tr>
        <th style="width: 50%;">Dari</th>
        <th style="width: 50%;">Tujuan</th>
    </tr>
    <tr>
        <td>{{ $formatProject($transfer->fromProject, $transfer->from_project_code) }}</td>
        <td>{{ $formatProject($transfer->toProject, $transfer->to_project_code) }}</td>
    </tr>
</table>

<hr>

<table class="equipment" style="margin-top: 8px;">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 12%;">Unit No</th>
            <th style="width: 28%;">Description</th>
            <th style="width: 16%;">S/N</th>
            <th style="width: 20%;">Engine Model</th>
            <th style="width: 20%;">Engine No.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transfer->lines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $line->equipment?->unit_code ?? $line->unit_code ?? '—' }}</td>
                <td>{{ $line->equipment?->description ?? '—' }}</td>
                <td>{{ $line->equipment?->serial_no ?? '—' }}</td>
                <td>{{ $line->equipment?->engine_model ?? '—' }}</td>
                <td>{{ $line->equipment?->machine_no ?? '—' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="6"><strong>Remarks:</strong> {{ $transfer->notes ?? '—' }}</td>
        </tr>
    </tbody>
</table>

<table class="borderless signature">
    <tr>
        <td>
            Balikpapan, {{ $ipaDate }}<br>
            Disetujui oleh
            <div class="space"></div>
            <div class="space"></div>
            <div class="space"></div>
            <div class="space"></div>
            <div class="space"></div>
            ({{ $transfer->approvedBy?->name ?? 'Christina W.' }})<br>
            Asset &amp; Insurance Sec. Head
        </td>
    </tr>
</table>

<table class="footer borderless">
    <tr>
        <td>sheet 1 : HO Jakarta</td>
        <td>sheet 2 : Pengirim Unit</td>
        <td>sheet 3 : Penerima Unit</td>
    </tr>
</table>
</body>
</html>
