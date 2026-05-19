<!DOCTYPE html>
<html lang="en" class="h-full bg-[#020202]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — YG Support</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --crimson: #ff003c;
            --crimson-dim: #9b1b30;
            --surface: #0a0a0a;
            --border: rgba(255,255,255,0.06);
        }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 10px; }
    </style>
</head>
<body class="h-full antialiased text-white">

<div class="flex h-full">
    {{-- Sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r" style="background:rgba(8,8,8,0.95);border-color:var(--border)">
        {{-- Brand --}}
        <div class="px-6 py-5 border-b" style="border-color:var(--border)">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-black" style="background:var(--crimson)">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <div class="text-sm font-bold tracking-wide">YG Support</div>
                    <div class="text-[10px] uppercase tracking-widest" style="color:#9b8e90">Help Center</div>
                </div>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto text-sm">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i> Overview
            </a>
            <a href="{{ route('tickets.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tickets.*') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                <i class="fas fa-ticket w-5 text-center"></i> My Tickets
            </a>
            <a href="{{ route('tickets.create') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tickets.create') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                <i class="fas fa-plus-circle w-5 text-center"></i> New Ticket
            </a>

            <div class="pt-6 pb-1 px-3 text-[10px] uppercase tracking-widest" style="color:#4a4044">Resources</div>
            <a href="{{ route('knowledge.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('knowledge.*') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                <i class="fas fa-book w-5 text-center"></i> Knowledge Base
            </a>
            <a href="{{ route('knowledge.index') }}#faq" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                <i class="fas fa-question-circle w-5 text-center"></i> FAQ
            </a>
            <a href="{{ route('status.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('status.*') ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                <i class="fas fa-shield-alt w-5 text-center"></i> System Status
            </a>

            {{-- Ecosystem bridge --}}
            <div class="pt-6 pb-1 px-3 text-[10px] uppercase tracking-widest" style="color:#4a4044">Ecosystem</div>
            @foreach([
                ['https://ygxone.com',       '🏠', 'Home'],
                ['https://mail.ygxone.com',   '📧', 'Mail'],
                ['https://xcel.ygxone.com',   '📊', 'Xcel'],
                ['https://docs.ygxone.com',   '📝', 'DocX'],
                ['https://drive.ygxone.com',  '☁️', 'Drive'],
                ['https://notes.ygxone.com',  '📝', 'Notes'],
                ['https://chat.ygxone.com',   '💬', 'Chat'],
                ['https://meet.ygxone.com',   '🎥', 'Meet'],
                ['https://calendar.ygxone.com','📅', 'Calendar'],
                ['https://contacts.ygxone.com','👥', 'Contacts'],
                ['https://developer.ygxone.com','💻', 'Developer'],
                ['https://pay.ygxone.com',    '💳', 'Pay'],
                ['https://master.ygxone.com', '⚙️', 'Admin'],
                ['https://account.ygxone.com', '👤', 'My Account'],
            ] as [$url, $icon, $label])
            <a href="{{ $url }}" target="_blank"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors text-xs">
                <span class="w-5 text-center">{{ $icon }}</span> {{ $label }} <span class="ml-auto text-[9px] opacity-50">↗</span>
            </a>
            @endforeach
        </nav>

        {{-- User footer --}}
        <div class="px-4 py-4 border-t" style="border-color:var(--border)">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:var(--crimson-dim)">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs truncate" style="color:#9b8e90">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out" class="text-gray-500 hover:text-white transition-colors text-sm">⏻</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between px-8 py-4 border-b flex-shrink-0"
                style="background:rgba(5,5,5,0.8);border-color:var(--border)">
            <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            <a href="https://account.ygxone.com" target="_blank"
               class="text-xs px-3 py-1.5 rounded-lg border transition-colors hover:bg-white/5"
               style="color:#9b8e90;border-color:var(--border)">
                YG Account ↗
            </a>
        </header>

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
