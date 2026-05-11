<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'YGXONE Empire')</title>

    @php
        $org = auth()->user()->organization;
    @endphp

    @if($org && $org->favicon)
        <link rel="icon" type="image/png" href="{{ Storage::url($org->favicon) }}">
    @else
        <link rel="icon" type="image/png" href="https://pay.ygxone.com/assets/images/logo-icon.png">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '{{ $org->primary_color ?? "#1a73e8" }}',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --brand-primary: {{ $org->primary_color ?? '#1a73e8' }};
            --brand-font: '{{ $org->primary_font ?? 'Inter' }}', sans-serif;
        }
        .bg-brand { background-color: var(--brand-primary) !important; }
        .text-brand { color: var(--brand-primary) !important; }
        .border-brand { border-color: var(--brand-primary) !important; }
        
        [x-cloak] { display: none !important; }
        body { font-family: var(--brand-font); background: #ffffff; color: #202124; }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            border-radius: 0 50px 50px 0;
            color: #5f6368;
            font-size: 14px;
            font-weight: 500;
            margin-right: 12px;
            transition: all 0.2s;
        }
        .sidebar-link:hover { background: #f1f3f4; color: #202124; }
        .sidebar-link.active { background: #e8f0fe; color: var(--brand-primary); }
        
        .google-card {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        .google-card:hover { 
            border-color: var(--brand-primary);
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.302), 0 1px 3px 1px rgba(60,64,67,0.149); 
        }
    </style>
    @stack('head')
</head>
<body class="antialiased">

    <!-- Top Navigation -->
    <header class="h-16 border-b border-gray-200 flex items-center justify-between px-4 sticky top-0 bg-white z-50">
        <div class="flex items-center gap-4">
            <button class="p-2 hover:bg-gray-100 rounded-full">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
            <div class="flex items-center gap-2">
                @if($org && $org->logo)
                    <img src="{{ Storage::url($org->logo) }}" class="h-8 w-auto object-contain" alt="{{ $org->name }}">
                @else
                    <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="h-6" alt="YG">
                @endif
                <span class="text-[22px] font-normal text-gray-600 tracking-tight">
                    {{ $org->name ?? 'Account' }}
                </span>
            </div>
        </div>

        <div class="flex-1 max-w-3xl mx-8 hidden md:block">
            <div class="bg-[#f1f3f4] rounded-lg flex items-center px-4 py-2 focus-within:bg-white focus-within:shadow-md transition-all border border-transparent focus-within:border-gray-200">
                <i class="fas fa-search text-gray-500 mr-4"></i>
                <input type="text" placeholder="Search YG Account" class="bg-transparent border-none outline-none w-full text-base">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ $org->support_url ?? '#' }}" target="_blank" class="p-2 hover:bg-gray-100 rounded-full group" title="Help & Support">
                <i class="far fa-question-circle text-gray-600 text-xl group-hover:text-brand"></i>
            </a>
            
            <div class="relative ml-2" x-data="{ open: false }">
                <button @click="open = !open" class="w-8 h-8 rounded-full overflow-hidden border border-gray-200">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1a73e8&color=fff" alt="">
                </button>
                <div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-3xl shadow-xl py-6 px-4 z-[100] text-center">
                    <div class="mb-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1a73e8&color=fff" class="w-20 h-20 rounded-full mx-auto mb-2" alt="">
                        <div class="font-bold text-lg text-gray-900">{{ auth()->user()->name }}</div>
                        <div class="text-sm text-gray-500">{{ auth()->user()->email }}</div>
                    </div>
                    <a href="#" class="inline-block px-6 py-2 border border-gray-300 rounded-full text-sm font-medium hover:bg-gray-50 mb-6">Manage your YG Account</a>
                    <hr class="mb-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 border border-gray-300 rounded-lg flex items-center justify-center gap-2 hover:bg-gray-50 text-gray-700 font-medium transition">
                            <i class="fas fa-sign-out-alt"></i> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 pt-4 hidden lg:block sticky top-16 h-[calc(100vh-64px)] overflow-y-auto">
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="#" class="sidebar-link">
                    <i class="far fa-user-circle"></i> Personal info
                </a>
                <a href="#" class="sidebar-link">
                    <i class="fas fa-shield-alt"></i> Data & privacy
                </a>
                <a href="{{ route('settings.security.index') }}" class="sidebar-link {{ request()->routeIs('settings.security.*') ? 'active' : '' }}">
                    <i class="fas fa-lock"></i> Security
                </a>
                
                @if(auth()->user()->account_type === 'business')
                <a href="{{ route('organization.index') }}" class="sidebar-link {{ request()->routeIs('organization.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> People & sharing
                </a>
                @endif

                <a href="{{ route('billing.upgrade') }}" class="sidebar-link">
                    <i class="far fa-credit-card"></i> Payments & subscriptions
                </a>
                
                <hr class="my-4 mx-4 border-gray-100">
                
                <a href="{{ $org->support_url ?? '#' }}" target="_blank" class="sidebar-link">
                    <i class="far fa-comment-alt"></i> Send feedback
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-h-[calc(100vh-64px)] bg-white overflow-x-hidden flex flex-col">
            <div class="flex-1">
                @yield('dashboard-content')
            </div>

            <footer class="p-8 border-t border-gray-100 bg-gray-50/20 text-center">
                @if($org && $org->footer_content)
                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-4">
                    {{ $org->footer_content }}
                </div>
                @endif
                <div class="flex justify-center gap-6 text-[11px] text-google-gray">
                    <a href="https://support.ygxone.com" target="_blank" class="hover:underline">Help</a>
                    <a href="https://ygxone.com/privacy" target="_blank" class="hover:underline">Privacy</a>
                    <a href="https://ygxone.com/terms" target="_blank" class="hover:underline">Terms</a>
                </div>
            </footer>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
