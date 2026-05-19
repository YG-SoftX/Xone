<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — YG Mail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: #f1f5f9;
            color: #dc2626;
        }
        .sidebar-link.active {
            background: #fef2f2;
            color: #dc2626;
            font-weight: 600;
        }
        .stat-card {
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.04);
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-60 bg-white border-r border-gray-200 flex-shrink-0">
            <div class="p-5 border-b border-gray-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <span class="text-base font-bold text-gray-900">YG</span>
                        <span class="text-red-600 font-bold">Mail</span>
                        <span class="text-[10px] text-gray-400 block -mt-0.5">Admin Panel</span>
                    </div>
                </a>
            </div>
            <nav class="p-3 space-y-0.5">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.config') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ request()->routeIs('admin.config') ? 'active' : '' }}">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span>Configuration</span>
                </a>
                <a href="{{ route('admin.queue') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 {{ request()->routeIs('admin.queue.*') ? 'active' : '' }}">
                    <i class="fas fa-tasks w-5 text-center"></i>
                    <span>Queue</span>
                </a>
            </nav>

            <div class="absolute bottom-0 w-60 p-4 border-t border-gray-100 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-700 rounded-xl flex items-center justify-center text-white font-bold text-xs shadow-sm">
                        A
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">Admin</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
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
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
                <h1 class="text-xl font-bold text-gray-900">@yield('title')</h1>
                <div class="flex items-center gap-3">
                    <a href="{{ route('mail.inbox') }}" class="text-sm text-gray-500 hover:text-red-600 transition-colors flex items-center gap-1.5">
                        <i class="fas fa-external-link-alt text-xs"></i> View Mail
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i> {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i> {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
