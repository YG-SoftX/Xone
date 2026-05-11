<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { width: 80px; margin-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .subtitle { font-size: 14px; color: #666; }
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-grid td { padding: 8px; border: 1px solid #ddd; }
        .label { font-weight: bold; background: #f9f9f9; width: 30%; }
        .section-title { background: #eee; padding: 10px; font-weight: bold; margin-bottom: 15px; border-left: 5px solid #3b82f6; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .data-label { font-weight: bold; color: #555; width: 40%; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .qr-placeholder { float: right; width: 100px; height: 100px; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="logo">
        <div class="title">Official Response Report</div>
        <div class="subtitle">YG Collect Sovereign Data Platform</div>
    </div>

    <div class="section-title">Submission Information</div>
    <table class="info-grid">
        <tr>
            <td class="label">Reference ID</td>
            <td>#SUB-{{ str_pad($submission->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="label">Form Title</td>
            <td>{{ $submission->form->title }}</td>
        </tr>
        <tr>
            <td class="label">Project</td>
            <td>{{ $submission->form->project->name ?? 'Unassigned' }}</td>
        </tr>
        <tr>
            <td class="label">Date Submitted</td>
            <td>{{ $submission->created_at->format('M d, Y H:i:s') }}</td>
        </tr>
        <tr>
            <td class="label">IP Address</td>
            <td>{{ $submission->metadata['ip'] ?? 'Unknown' }}</td>
        </tr>
    </table>

    <div class="section-title">Collected Data</div>
    <table class="data-table">
        @foreach($submission->data as $key => $value)
            <tr>
                <td class="data-label">
                    {{ ucwords(str_replace('_', ' ', $key)) }}
                </td>
                <td>
                    @if(is_array($value))
                        {{ implode(', ', $value) }}
                    @else
                        {{ $value }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <div class="footer">
        This is an electronically generated official document from YG Collect. 
        Verification ID: {{ md5($submission->id . $submission->created_at) }}
    </div>
</body>
</html>
