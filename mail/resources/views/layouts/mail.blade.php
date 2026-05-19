<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Inbox') — YGXONE Mail</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --yg-primary: #dc2626;
            --yg-primary-hover: #b91c1c;
            --yg-primary-light: #fef2f2;
            --yg-primary-bg: #fee2e2;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #111827;
        }

        .email-row:hover .hover-actions {
            display: flex !important;
        }

        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: #f3f4f6;
            color: #111827 !important;
        }
        .sidebar-link.active {
            background: var(--yg-primary-light);
            color: var(--yg-primary) !important;
            font-weight: 600;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        [x-cloak] { display: none !important; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-red { background: #fef2f2; color: #dc2626; }
        .badge-green { background: #f0fdf4; color: #16a34a; }
        .badge-yellow { background: #fefce8; color: #ca8a04; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .badge-blue { background: #eff6ff; color: #2563eb; }
    </style>
    @stack('head')
</head>
<body class="antialiased">
    <div class="flex h-screen overflow-hidden" x-data="mailLayout()">

        <!-- Sidebar -->
        <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-200 z-50 flex-shrink-0" x-data="{ mobileOpen: false }">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-100">
                <a href="{{ route('mail.inbox') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-gray-900 tracking-tight">YGXONE</span>
                        <span class="text-red-600 font-bold">Mail</span>
                    </div>
                </a>
            </div>

            <!-- Compose Button -->
            <div class="px-4 py-4">
                <a href="{{ route('mail.inbox') }}?compose=1"
                   class="flex items-center justify-center gap-2 w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-sm hover:shadow-md transition-all text-sm">
                    <i class="fas fa-pen"></i> Compose
                </a>
            </div>

            <!-- Folders -->
            <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Folders</p>

                <a href="{{ route('mail.inbox', ['folder' => 'inbox']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ $currentFolder === 'inbox' ? 'active' : '' }}">
                    <i class="fas fa-inbox w-5 text-center text-sm"></i>
                    <span class="flex-1">Inbox</span>
                    @if(($unreadCounts['inbox'] ?? 0) > 0)
                        <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadCounts['inbox'] }}</span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'sent']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ $currentFolder === 'sent' ? 'active' : '' }}">
                    <i class="fas fa-paper-plane w-5 text-center text-sm"></i>
                    <span>Sent</span>
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'starred']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ $currentFolder === 'starred' ? 'active' : '' }}">
                    <i class="fas fa-star w-5 text-center text-sm"></i>
                    <span>Starred</span>
                    @if(($unreadCounts['starred'] ?? 0) > 0)
                        <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadCounts['starred'] }}</span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'spam']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ $currentFolder === 'spam' ? 'active' : '' }}">
                    <i class="fas fa-shield-halved w-5 text-center text-sm"></i>
                    <span>Spam</span>
                    @if(($unreadCounts['spam'] ?? 0) > 0)
                        <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadCounts['spam'] }}</span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'trash']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ $currentFolder === 'trash' ? 'active' : '' }}">
                    <i class="fas fa-trash-can w-5 text-center text-sm"></i>
                    <span>Trash</span>
                    @if(($unreadCounts['trash'] ?? 0) > 0)
                        <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unreadCounts['trash'] }}</span>
                    @endif
                </a>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-red-500 to-red-700 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-red-600 transition-colors" title="Sign out">
                            <i class="fas fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-6 z-40 flex-shrink-0">
                <div class="flex items-center flex-1 gap-4">
                    <!-- Mobile menu toggle -->
                    <button class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg" @click="mobileOpen = !mobileOpen">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Search -->
                    <div class="relative flex-1 max-w-lg" x-data="{ search: '{{ $searchQuery ?? '' }}' }">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <form action="{{ route('mail.search') }}" method="GET">
                            <input type="text" name="q" x-model="search"
                                   placeholder="Search emails..."
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-red-300 focus:ring-1 focus:ring-red-200 outline-none transition-all">
                        </form>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if(isset($quota) && $quota)
                        <span class="hidden md:flex items-center gap-1.5 text-xs text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                            <i class="fas fa-chart-bar text-gray-400"></i>
                            {{ $quota->daily_sent }}/{{ $quota->daily_limit }}
                        </span>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hidden md:block text-xs font-medium text-gray-500 hover:text-red-600 transition-colors">
                            Sign Out
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-hidden relative flex flex-col bg-gray-50">
                @if(session('success'))
                    <div class="mx-4 md:mx-6 mt-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mx-4 md:mx-6 mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto">
                    @yield('mail-content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
    function mailLayout() {
        return {
            mobileOpen: false,
        };
    }
    </script>
</body>
</html>
