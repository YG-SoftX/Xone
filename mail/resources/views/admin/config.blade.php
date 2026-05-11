@extends('admin.layout')

@section('title', 'Configuration')

<div class="alert alert-info">
    Mail configuration is managed via the <code>.env</code> file. After making changes, run: <code>php artisan config:cache</code>
</div>

<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;margin-bottom:24px;">
    <h3 style="font-size:16px;margin-bottom:16px;">Outgoing Mail (SMTP)</h3>
    <table>
        <tr><td style="color:#64748b">Host</td><td>{{ config('mail.mailers.smtp.host', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Port</td><td>{{ config('mail.mailers.smtp.port', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Encryption</td><td>{{ config('mail.mailers.smtp.encryption', 'Not set') ?: config('mail.mailers.smtp.scheme', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Username</td><td>{{ config('mail.mailers.smtp.username', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">From Address</td><td>{{ config('mail.from.address', 'Not set') }}</td></tr>
    </table>
</div>

<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;margin-bottom:24px;">
    <h3 style="font-size:16px;margin-bottom:16px;">Incoming Mail (IMAP)</h3>
    <table>
        <tr><td style="color:#64748b">Host</td><td>{{ config('mail.incoming.host', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Port</td><td>{{ config('mail.incoming.port', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Encryption</td><td>{{ config('mail.incoming.encryption', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">Username</td><td>{{ config('mail.incoming.username', 'Not set') }}</td></tr>
        <tr><td style="color:#64748b">IMAP Extension</td><td>
            @if($imapLoaded)
                <span class="badge badge-success">Loaded ✓</span>
            @else
                <span class="badge badge-danger">Not Loaded ✗</span>
            @endif
        </td></tr>
    </table>

    <form method="POST" action="{{ route('admin.config.test-imap') }}" style="margin-top:16px;">
        @csrf
        <button class="btn">Test IMAP Connection</button>
    </form>
</div>

<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;margin-bottom:24px;">
    <h3 style="font-size:16px;margin-bottom:16px;">SSO Integration</h3>
    <table>
        <tr><td style="color:#64748b">YG Account URL</td><td>{{ config('services.yg_account.url', 'Not set') }}</td></tr>
    </table>
</div>

<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;">
    <h3 style="font-size:16px;margin-bottom:16px;">Queue</h3>
    <table>
        <tr><td style="color:#64748b">Connection</td><td>{{ config('queue.default', 'sync') }}</td></tr>
        <tr><td style="color:#64748b">Status</td><td>
            @if(config('queue.default') === 'sync')
                <span class="badge badge-warning">Sync (emails sent immediately — change to database for production)</span>
            @else
                <span class="badge badge-success">{{ config('queue.default') }}</span>
            @endif
        </td></tr>
    </table>
</div>
