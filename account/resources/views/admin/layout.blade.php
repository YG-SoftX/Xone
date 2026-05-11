<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YG Admin - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-6 border-b border-gray-800">
                <h1 class="text-2xl font-bold">YG Admin</h1>
                <p class="text-xs text-gray-400 mt-1">Platform Management</p>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <p class="px-6 text-xs text-gray-500 uppercase mb-2">Main</p>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    Dashboard
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Users</p>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    Users
                </a>
                <a href="{{ route('admin.kyc.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.kyc.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    KYC Review
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.devices.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                    Devices
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Platform</p>
                <a href="{{ route('admin.platform.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.platform.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    Statistics
                </a>
                <a href="{{ route('admin.features.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.features.*') || request()->routeIs('admin.services.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Features & Services
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Finance</p>
                <a href="{{ route('admin.invoices.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.invoices.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Invoices
                </a>
                <a href="{{ route('admin.nfc.tokens') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.nfc.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0">
                        </path>
                    </svg>
                    NFC Payments
                </a>
                <a href="{{ route('admin.platform.wallets') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.platform.wallets') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                    Wallets
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Email</p>
                <a href="{{ route('admin.smtp.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.smtp.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    SMTP Accounts
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">System</p>
                <a href="{{ route('admin.system.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.system.*') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                        </path>
                    </svg>
                    System
                </a>
            </nav>
            <div class="p-6 border-t border-gray-800">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded text-sm">Logout</button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="bg-white shadow px-8 py-4">
                <h2 class="text-xl font-semibold text-gray-800">@yield('title')</h2>
            </div>
            <main class="p-8">
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>