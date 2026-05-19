@extends('admin.layout')

@section('title', 'Configuration')

<div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-sm flex items-center gap-2">
    <i class="fas fa-info-circle text-blue-500"></i>
    Mail configuration is managed via the <code class="mx-1 px-1.5 py-0.5 bg-blue-100 rounded font-mono text-xs">.env</code> file.
    After making changes, run: <code class="mx-1 px-1.5 py-0.5 bg-blue-100 rounded font-mono text-xs">php artisan config:cache</code>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- SMTP Settings -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-paper-plane text-blue-500"></i> Outgoing Mail (SMTP)
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Host</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.mailers.smtp.host', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Port</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.mailers.smtp.port', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Encryption</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.mailers.smtp.encryption', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Username</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.mailers.smtp.username', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-sm text-gray-500">From Address</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.from.address', 'Not set') }}</span>
            </div>
        </div>
    </div>

    <!-- IMAP Settings -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-inbox text-green-500"></i> Incoming Mail (IMAP)
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Host</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.incoming.host', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Port</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.incoming.port', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Encryption</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.incoming.encryption', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Username</span>
                <span class="text-sm font-medium text-gray-900">{{ config('mail.incoming.username', 'Not set') }}</span>
            </div>
            <div class="flex justify-between py-2 items-center">
                <span class="text-sm text-gray-500">IMAP Extension</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $imapLoaded ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    <i class="fas {{ $imapLoaded ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                    {{ $imapLoaded ? 'Loaded' : 'Not Loaded' }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.config.test-imap') }}" class="mt-4">
            @csrf
            <button type="submit" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-vial text-gray-400"></i> Test IMAP Connection
            </button>
        </form>
    </div>

    <!-- SSO Settings -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-link text-purple-500"></i> SSO Integration
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between py-2">
                <span class="text-sm text-gray-500">YG Account URL</span>
                <span class="text-sm font-medium text-gray-900">{{ config('services.yg_account.url', 'Not set') }}</span>
            </div>
        </div>
    </div>

    <!-- Queue Settings -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-tasks text-yellow-500"></i> Queue
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-500">Connection</span>
                <span class="text-sm font-medium text-gray-900">{{ config('queue.default', 'sync') }}</span>
            </div>
            <div class="flex justify-between py-2 items-center">
                <span class="text-sm text-gray-500">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ config('queue.default') === 'sync' ? 'bg-yellow-50 text-yellow-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ config('queue.default') === 'sync' ? 'Sync - set to database for production' : config('queue.default') }}
                </span>
            </div>
        </div>
    </div>
</div>
