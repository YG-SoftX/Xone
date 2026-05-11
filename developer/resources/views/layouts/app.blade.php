<!DOCTYPE html>
<html lang="en" class="h-full bg-[#020202]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — YG Developer</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --crimson: #ff003c;
            --crimson-dim: #9b1b30;
            --surface: #0a0a0a;
            --border: rgba(255,255,255,0.06);
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-white">

<div class="flex h-full">
    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r"
           style="background:rgba(8,8,8,0.95);border-color:var(--border)">

        {{-- Brand --}}
        <div class="px-6 py-5 border-b" style="border-color:var(--border)">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-black"
                     style="background:var(--crimson)">YG</div>
                <div>
                    <div class="text-sm font-bold tracking-wide">YG Developer</div>
                    <div class="text-[10px] uppercase tracking-widest" style="color:#9b8e90">Console</div>
                </div>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm">
            @php
                function navLink(string $route, string $icon, string $label, string $match = ''): string {
                    $active = request()->routeIs($match ?: $route . '*');
                    $base   = 'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ';
                    $style  = $active
                        ? 'background:rgba(255,0,60,0.12);color:#fff'
                        : 'color:#9b8e90';
                    $hover  = $active ? '' : ' hover:text-white hover:bg-white/5';
                    return "<a href=\"" . route($route) . "\" class=\"{$base}{$hover}\" style=\"{$style}\"><span>{$icon}</span>{$label}</a>";
                }
            @endphp

            {!! navLink('dashboard', '⬛', 'Overview', 'dashboard') !!}

            <div class="pt-4 pb-1 px-3 text-[10px] uppercase tracking-widest" style="color:#4a4044">Build</div>
            {!! navLink('projects.index', '📁', 'Projects', 'projects.*') !!}
            {!! navLink('tokens.index',   '🔑', 'API Tokens', 'tokens.*') !!}
            {!! navLink('apps.index',     '🔗', 'OAuth Apps', 'apps.*') !!}

            <div class="pt-4 pb-1 px-3 text-[10px] uppercase tracking-widest" style="color:#4a4044">Account</div>
            {!! navLink('billing.index', '💳', 'Billing', 'billing.*') !!}
        </nav>

        {{-- User footer --}}
        <div class="px-4 py-4 border-t" style="border-color:var(--border)">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                     style="background:var(--crimson-dim)">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs truncate" style="color:#9b8e90">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="text-gray-500 hover:text-white transition-colors text-sm">⏻</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main content ─────────────────────────────────────────────────────── --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Top bar --}}
        <header class="flex items-center justify-between px-8 py-4 border-b flex-shrink-0"
                style="background:rgba(5,5,5,0.8);border-color:var(--border)">
            <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            
            {{-- Unified Search Integration --}}
            <div class="flex-1 max-w-md mx-8">
                @if(view()->exists('components.unified-search'))
                    <x-unified-search />
                @endif
            </div>
            
            <div class="flex items-center gap-3">
                {{-- Notification Center Integration --}}
                @if(view()->exists('components.notification-center'))
                    <x-notification-center />
                @endif
                
                <a href="{{ config('services.yg_account.url') }}" target="_blank"
                   class="text-xs px-3 py-1.5 rounded-lg border transition-colors hover:bg-white/5"
                   style="color:#9b8e90;border-color:var(--border)">
                    YG Account ↗
                </a>
            </div>
        </header>

        {{-- Page body --}}
        <div class="flex-1 overflow-y-auto p-8">
            @if(session('success'))
                <div class="mb-6 px-4 py-3 rounded-lg text-sm" style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 px-4 py-3 rounded-lg text-sm" style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

</body>
</html>
