<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YG Xone Admin - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col overflow-y-auto fixed h-full">
            <div class="p-6 border-b border-gray-800">
                <h1 class="text-2xl font-bold text-white">YG Xone</h1>
                <p class="text-xs text-gray-400 mt-1">Sovereign Platform Manager</p>
            </div>
            <nav class="flex-1 py-4">
                <p class="px-6 text-xs text-gray-500 uppercase mb-2">Overview</p>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    Dashboard
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Services</p>
                <a href="{{ route('admin.services.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.services.*') ? 'bg-gray-800 border-l-4 border-green-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    Services & Health
                </a>
                <a href="{{ route('admin.features.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.features.*') ? 'bg-gray-800 border-l-4 border-purple-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Features & Toggles
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
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.kyc.*') ? 'bg-gray-800 border-l-4 border-yellow-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    KYC Review
                </a>
                <a href="{{ route('admin.users.index') }}" onclick="event.preventDefault(); window.location='/admin/users?tab=devices'"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.devices.*') ? 'bg-gray-800 border-l-4 border-sky-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                    Devices
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Finance</p>
                <a href="{{ route('admin.platform.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.platform.*') ? 'bg-gray-800 border-l-4 border-green-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    Wallets & Subs
                </a>
                <a href="{{ route('admin.invoices.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.invoices.*') ? 'bg-gray-800 border-l-4 border-indigo-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Invoices
                </a>
                <a href="{{ route('admin.nfc.tokens') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.nfc.*') ? 'bg-gray-800 border-l-4 border-cyan-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0">
                        </path>
                    </svg>
                    NFC Payments
                </a>
                <a href="{{ route('admin.smtp.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.smtp.*') ? 'bg-gray-800 border-l-4 border-orange-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    SMTP Accounts
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Content</p>
                <a href="{{ route('admin.emails.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.emails.*') ? 'bg-gray-800 border-l-4 border-red-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    YG Mail
                </a>
                <a href="{{ route('admin.documents.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.documents.*') ? 'bg-gray-800 border-l-4 border-emerald-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    YG DocX
                </a>
                <a href="{{ route('admin.spreadsheets.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.spreadsheets.*') ? 'bg-gray-800 border-l-4 border-green-600' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    YG Xcel
                </a>
                <a href="{{ route('admin.templates.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.templates.*') ? 'bg-gray-800 border-l-4 border-pink-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                        </path>
                    </svg>
                    Templates
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">CMS & Content</p>
                <a href="{{ route('admin.theme.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.theme.*') ? 'bg-gray-800 border-l-4 border-indigo-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                        </path>
                    </svg>
                    Theme & Branding
                </a>
                <a href="{{ route('admin.content.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.content.*') ? 'bg-gray-800 border-l-4 border-teal-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Pages & Content
                </a>
                <a href="{{ route('admin.docs.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.docs.*') ? 'bg-gray-800 border-l-4 border-cyan-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                    Documentation
                </a>
                <a href="{{ route('admin.articles.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.articles.*') ? 'bg-gray-800 border-l-4 border-amber-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                        </path>
                    </svg>
                    Articles & Blog
                </a>
                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Monetization</p>
                <a href="{{ route('admin.monetization.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.monetization.index') ? 'bg-gray-800 border-l-4 border-green-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    YG Ads Manager
                </a>
                <a href="{{ route('admin.monetization.moderation') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.monetization.moderation') ? 'bg-gray-800 border-l-4 border-amber-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    AdSense Moderation
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Support & Tickets</p>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">Integrations</p>
                <a href="{{ route('admin.third-party-apps.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.third-party-apps.*') ? 'bg-gray-800 border-l-4 border-violet-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                        </path>
                    </svg>
                    Third-Party Apps
                </a>

                <p class="px-6 text-xs text-gray-500 uppercase mt-6 mb-2">System</p>
                <a href="{{ route('admin.system.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.system.*') ? 'bg-gray-800 border-l-4 border-slate-400' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                        </path>
                    </svg>
                    System
                </a>
                <a href="{{ route('admin.environment.index') }}"
                    class="flex items-center px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.environment.*') ? 'bg-gray-800 border-l-4 border-orange-500' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Environment Config
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
        <div class="flex-1 ml-64">
            <div class="bg-white shadow px-8 py-4">
                <h2 class="text-xl font-semibold text-gray-800">@yield('title')</h2>
            </div>
            <main class="p-8">
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')

{{-- Device Fingerprinting Script - Captures detailed device info for security --}}
<script>
(function() {
    'use strict';
    
    // Only run for authenticated users
    if (!document.querySelector('meta[name="user-authenticated"]')) {
        return;
    }

    /**
     * Generate canvas fingerprint
     */
    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 50;
            
            // Draw text with various styles
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(0, 0, 50, 50);
            ctx.fillStyle = '#069';
            ctx.fillText('YG Device Print', 2, 2);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('YG Device Print', 4, 4);
            
            return canvas.toDataURL().slice(-32); // Return hash-like string
        } catch (e) {
            return null;
        }
    }

    /**
     * Get WebGL fingerprint
     */
    function getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return null;
            
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return null;
            
            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            
            return btoa(vendor + '|' + renderer).substring(0, 32);
        } catch (e) {
            return null;
        }
    }

    /**
     * Get installed fonts hash (simplified)
     */
    function getFontsHash() {
        const baseFonts = ['monospace', 'sans-serif', 'serif'];
        const testString = 'mmmmmmmmmmlli';
        const testSize = '72px';
        const h = document.createElement('span');
        h.style.fontSize = testSize;
        
        let detected = [];
        const fontList = [
            'Arial', 'Verdana', 'Courier New', 'Times New Roman', 
            'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS',
            'Trebuchet MS', 'Impact', 'Lucida Sans', 'Tahoma'
        ];
        
        fontList.forEach(font => {
            h.style.fontFamily = font;
            document.body.appendChild(h);
            const width = h.offsetWidth;
            h.style.fontFamily = font + ', ' + baseFonts[0];
            const newWidth = h.offsetWidth;
            
            if (width !== newWidth) {
                detected.push(font);
            }
            document.body.removeChild(h);
        });
        
        return detected.length > 0 ? btoa(detected.join(',')).substring(0, 32) : null;
    }

    /**
     * Collect all device information
     */
    function collectDeviceData() {
        const data = {
            // Screen info
            screen_resolution: `${screen.width}x${screen.height}`,
            color_depth: screen.colorDepth,
            pixel_ratio: window.devicePixelRatio || 1,
            
            // System info
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language || navigator.userLanguage,
            hardware_concurrency: navigator.hardwareConcurrency || null,
            device_memory: navigator.deviceMemory || null,
            touch_support: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
            
            // Fingerprints
            canvas: getCanvasFingerprint(),
            webgl: getWebGLFingerprint(),
            fonts: getFontsHash(),
        };

        // Try to get mobile device identifiers (requires native app bridge or permissions)
        if (window.Android || window.webkit) {
            // Android/iOS native bridge would provide IMEI, Android ID, IDFA here
            // This is placeholder for future mobile app integration
        }

        // Try to get geolocation (requires user permission)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    data.latitude = position.coords.latitude;
                    data.longitude = position.coords.longitude;
                    data.accuracy = position.coords.accuracy;
                    
                    sendToDeviceServer(data);
                },
                (error) => {
                    // Geolocation denied or unavailable - send without location
                    sendToDeviceServer(data);
                },
                { timeout: 5000, maximumAge: 60000 }
            );
        } else {
            sendToDeviceServer(data);
        }
    }

    /**
     * Send device data to server via AJAX
     */
    function sendToDeviceServer(data) {
        fetch('/api/device/fingerprint', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Device-Fingerprint': JSON.stringify(data)
            },
            body: JSON.stringify(data)
        }).catch(err => {
            console.warn('Device fingerprint submission failed:', err);
        });
    }

    // Collect device data on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', collectDeviceData);
    } else {
        collectDeviceData();
    }

    // Also update on significant events
    window.addEventListener('resize', () => {
        setTimeout(collectDeviceData, 1000); // Debounce
    });

})();
</script>

</body>

</html>