<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Expiring Documents</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Expiring Documents (next {{ $days }} days)</h1>
    <table>
        <thead>
            <tr>
                <th>Unit No</th>
                <th>Document Type</th>
                <th>Document No</th>
                <th>Issued</th>
                <th>Expiry</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell ?? '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
