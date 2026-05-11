<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'YGXONE Empire'))</title>
    
    <!-- Fonts: High-Fidelity Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons: FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Core Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --yg-bg: #020617;
            --yg-glass: rgba(255, 255, 255, 0.03);
            --yg-border: rgba(255, 255, 255, 0.08);
            --yg-accent-blue: #3b82f6;
            --yg-accent-purple: #a855f7;
            --yg-accent-teal: #14b8a6;
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
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid var(--yg-accent-blue);
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
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
    @stack('head')
</head>
<body class="antialiased selection:bg-blue-500/30">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Universal Sidebar: The Backbone of the Empire -->
        <aside class="hidden md:flex flex-col w-72 premium-glass border-r border-white/5 z-50">
            <div class="p-8">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-cube text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase">{{ __("YGXONE") }}</span>
                </a>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">{{ __("Core Services") }}</p>
                
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : '' }}">
                    <i class="fas fa-th-large w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("Command Center") }}</span>
                </a>

                <a href="/pay" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                    <i class="fas fa-wallet w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("YG Pay Authority") }}</span>
                </a>

                <a href="/mail" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                    <i class="fas fa-envelope w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("Secure Triage") }}</span>
                </a>

                <a href="{{ route('passwords.index') }}" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item {{ request()->routeIs('passwords.*') ? 'sidebar-item-active' : '' }}">
                    <i class="fas fa-vault w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("Sovereign Vault") }}</span>
                </a>

                <a href="/ai" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                    <i class="fas fa-brain w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">{{ __("Neural Intelligence") }}</span>
                </a>

                <div class="pt-8">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">{{ __("Identity Protection") }}</p>
                    <a href="{{ route('security.index') }}" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item {{ request()->routeIs('security.*') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-shield-halved w-8 text-lg"></i>
                        <span class="text-sm tracking-tight">{{ __("Security Hub") }}</span>
                    </a>
                    <a href="{{ route('settings.profile') }}" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item {{ request()->routeIs('settings.profile') ? 'sidebar-item-active' : '' }}">
                        <i class="fas fa-user-gear w-8 text-lg"></i>
                        <span class="text-sm tracking-tight">{{ __("Persona Settings") }}</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer: Status -->
            <div class="p-6 border-t border-white/5 bg-black/20">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <img class="h-10 w-10 rounded-xl border border-white/10" src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?background=3b82f6&color=fff&name=' . urlencode(auth()->user()->name) }}" alt="">
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-[#020617] rounded-full"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">Sovereign Elite</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Universal Header: The Command Bar -->
            <header class="h-20 premium-glass border-b border-white/5 flex items-center justify-between px-8 z-40">
                <div class="flex items-center flex-1 space-x-8">
                    <!-- Global Search -->
                    <div class="relative w-full max-w-xl">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" placeholder="Search the empire..." class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-6 text-sm text-white placeholder-gray-600 focus:border-blue-500/50 outline-none transition-all">
                    </div>

                    <!-- Dynamic Header Menu (Managed from YG Master) -->
                    <nav class="hidden lg:flex items-center space-x-6">
                        @php
                            $headerItems = \App\Models\UniversalNavigationItem::forService($service_key ?? 'account')->header()->get();
                        @endphp
                        @foreach($headerItems as $item)
                            <a href="{{ $item->url }}" class="text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-[0.2em] transition-colors flex items-center gap-2">
                                @if($item->icon) <i class="{{ $item->icon }} text-xs"></i> @endif
                                {{ $item->label }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="flex items-center space-x-6 ml-8">
                    <!-- Notifications -->
                    <button class="relative w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                    </button>

                    <!-- User Portal Switcher -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-xl hover:bg-white/5 transition-all">
                            <i class="fas fa-th text-gray-400"></i>
                        </button>
                        <!-- Grid App Switcher (Google Style) -->
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-4 w-72 premium-glass rounded-3xl p-6 shadow-3xl border border-white/10">
                            <div class="grid grid-cols-3 gap-6">
                                <a href="/pay" class="flex flex-col items-center group">
                                    <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-blue-500/20 transition-all">
                                        <i class="fas fa-wallet text-blue-400"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pay</span>
                                </a>
                                <a href="/mail" class="flex flex-col items-center group">
                                    <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-purple-500/20 transition-all">
                                        <i class="fas fa-envelope text-purple-400"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mail</span>
                                </a>
                                <a href="/ai" class="flex flex-col items-center group">
                                    <div class="w-12 h-12 bg-teal-500/10 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-teal-500/20 transition-all">
                                        <i class="fas fa-brain text-teal-400"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">AI</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">
                            {{ __("Exit Empire") }}
                        </button>
                    </form>
                </div>
            </header>

            <!-- Workspace Content -->
            <main class="flex-1 overflow-y-auto relative flex flex-col">
                <div class="flex-1">
                    @yield('content')
                </div>

                <!-- Dynamic Footer (Managed from YG Master) -->
                <footer class="p-8 border-t border-white/5 bg-black/20">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center space-x-6">
                            @php
                                $footerItems = \App\Models\UniversalNavigationItem::forService($service_key ?? 'account')->footer()->get();
                            @endphp
                            @foreach($footerItems as $item)
                                <a href="{{ $item->url }}" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">
                                    {{ $item->label }}
                                </a>
                            @endforeach
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
