<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Sovereign Ledger') — YGXONE</title>
    
    <!-- Fonts: High-Fidelity Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons: FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Core Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --yg-bg: #020617;
            --yg-glass: rgba(255, 255, 255, 0.03);
            --yg-border: rgba(255, 255, 255, 0.08);
            --yg-accent-blue: #3b82f6;
            --yg-accent-green: #10b981;
        }

        body {
            background-color: var(--yg-bg) !important;
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .premium-glass {
            background: var(--yg-glass);
            border: 1px solid var(--yg-border);
            backdrop-filter: blur(16px);
        }

        .sidebar-item-active {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid var(--yg-accent-green);
            color: white !important;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--yg-bg); }
        ::-webkit-scrollbar-thumb { background: var(--yg-border); border-radius: 10px; }
        
        .xcel-cell { border: 1px solid var(--yg-border); }
        .xcel-cell:focus-within { border-color: var(--yg-accent-green); background: rgba(16, 185, 129, 0.05); }

        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body class="antialiased selection:bg-green-500/30">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Universal Sidebar: The Backbone of the Empire -->
        <aside class="hidden md:flex flex-col w-72 premium-glass border-r border-white/5 z-50">
            <div class="p-8">
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-table text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase">{{ __("YGXONE") }}</span>
                </a>
            </div>

            <!-- New Spreadsheet Button -->
            <div class="px-6 mb-6">
                <button class="w-full py-4 bg-gradient-to-r from-green-600 to-blue-600 text-white font-black rounded-2xl hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-green-600/20 uppercase tracking-widest text-xs">
                    <i class="fas fa-plus mr-2"></i> {{ __("New Ledger") }}
                </button>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">{{ __("Calculation Nodes") }}</p>
                
                <a href="#" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item sidebar-item-active">
                    <i class="fas fa-file-excel w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("My Spreadsheets") }}</span>
                </a>

                <a href="#" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                    <i class="fas fa-file-invoice-dollar w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("Financial Models") }}</span>
                </a>

                <div class="pt-8">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">{{ __("Ecosystem Bridge") }}</p>
                    <a href="https://account.ygxone.com/dashboard" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                        <i class="fas fa-th-large w-8 text-lg text-blue-400"></i>
                        <span class="text-sm tracking-tight">{{ __("Command Center") }}</span>
                    </a>
                    <a href="https://pay.ygxone.com" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                        <i class="fas fa-wallet w-8 text-lg text-purple-400"></i>
                        <span class="text-sm tracking-tight">{{ __("YG Pay Authority") }}</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-6 border-t border-white/5 bg-black/20 mt-auto">
                <div class="flex items-center space-x-4">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-green-500 to-blue-600 flex items-center justify-center text-white font-black border border-white/10 shadow-lg">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-green-400 font-bold uppercase tracking-widest">{{ __("Sovereign Analyst") }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            @php $service_key = 'xcel'; @endphp

            <!-- Universal Header -->
            <header class="h-20 premium-glass border-b border-white/5 flex items-center justify-between px-8 z-40">
                <div class="flex items-center flex-1 space-x-8">
                    <div class="relative w-full max-w-xl">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" placeholder="Search data archives..." class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-6 text-sm text-white placeholder-gray-600 focus:border-green-500/50 outline-none transition-all">
                    </div>

                    <nav class="hidden lg:flex items-center space-x-6">
                        @if(class_exists('\App\Models\UniversalNavigationItem'))
                            @php $headerItems = \App\Models\UniversalNavigationItem::forService($service_key)->header()->get(); @endphp
                            @foreach($headerItems as $item)
                                <a href="{{ $item->url }}" class="text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-[0.2em] transition-colors">
                                    {{ $item->label }}
                                </a>
                            @endforeach
                        @else
                            <a href="https://account.ygxone.com" class="text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-[0.2em] transition-colors">ACCOUNT</a>
                            <a href="https://pay.ygxone.com" class="text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-[0.2em] transition-colors">YG PAY</a>
                        @endif
                    </nav>
                </div>

                <div class="flex items-center space-x-6 ml-8">
                    <div class="h-10 px-4 bg-white/5 border border-white/10 rounded-xl flex items-center gap-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sovereign Engine Active</span>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-hidden relative flex flex-col">
                <div class="flex-1 overflow-y-auto">
                    @yield('content')
                </div>

                <!-- Footer -->
                <footer class="p-8 border-t border-white/5 bg-black/20">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center space-x-6">
                            @if(class_exists('\App\Models\UniversalNavigationItem'))
                                @php $footerItems = \App\Models\UniversalNavigationItem::forService($service_key)->footer()->get(); @endphp
                                @foreach($footerItems as $item)
                                    <a href="{{ $item->url }}" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">
                                        {{ $item->label }}
                                    </a>
                                @endforeach
                            @else
                                <a href="https://account.ygxone.com/dashboard" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">ACCOUNT</a>
                                <a href="https://ygxone.com" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">HOME</a>
                            @endif
                        </div>
                        <p class="text-[10px] font-black text-gray-700 uppercase tracking-[0.3em]">
                            &copy; {{ date('Y') }} {{ __("YGXONE EMPIRE. ALL AUTHORITY RESERVED.") }}
                        </p>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
