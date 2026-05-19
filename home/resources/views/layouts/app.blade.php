{{-- 
  YGXONE Main Layout — Dynamic & Theme-Aware
  Controlled from Master Panel via app_modules & themes tables
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="appState()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $themeService?->getTheme()['colors']['primary'] ?? '#2563eb' }}">
    
    <title>@yield('title', 'YGXONE — Sovereign Intelligence Portal')</title>
    
    {{-- Theme CSS Variables (Dynamic — Set from Master Panel) --}}
    {!! $themeService?->getCssVariablesTag() ?? '' !!}
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    {{-- Icons: FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['Outfit', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        },
                    },
                    borderRadius: {
                        'theme': 'var(--yg-radius, 12px)',
                    },
                }
            }
        }
    </script>
    
    <style>
        :root {
            --yg-primary: #2563eb;
            --yg-secondary: #7c3aed;
            --yg-accent: #f59e0b;
            --yg-background: #ffffff;
            --yg-surface: #f8fafc;
            --yg-text: #0f172a;
            --yg-text-dim: #64748b;
            --yg-border: #e2e8f0;
            --yg-success: #22c55e;
            --yg-danger: #ef4444;
            --font-heading: 'Outfit', system-ui, sans-serif;
            --font-body: 'Inter', system-ui, sans-serif;
            --yg-radius: 12px;
            --yg-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --yg-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --yg-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background-color: var(--yg-background);
            color: var(--yg-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .font-heading { font-family: var(--font-heading); }

        /* Ambient Background Pattern */
        .bg-ambient {
            position: fixed;
            inset: 0;
            z-index: -1;
            overflow: hidden;
        }
        .bg-ambient::before {
            content: '';
            position: absolute;
            inset: -50%;
            background-image: radial-gradient(circle at 30% 20%, rgba(37, 99, 235, 0.03) 0%, transparent 50%),
                              radial-gradient(circle at 70% 80%, rgba(124, 58, 237, 0.03) 0%, transparent 50%),
                              radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.02) 0%, transparent 50%);
            animation: ambient-drift 20s ease-in-out infinite alternate;
        }
        .bg-ambient::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.25;
        }
        @keyframes ambient-drift {
            0%   { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(2%, 1%) rotate(2deg); }
        }

        /* Smooth Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Transitions */
        .fade-in { animation: fadeIn 0.5s ease-out both; }
        .fade-in-up { animation: fadeInUp 0.6s ease-out both; }
        .fade-in-delay-1 { animation-delay: 0.1s; }
        .fade-in-delay-2 { animation-delay: 0.2s; }
        .fade-in-delay-3 { animation-delay: 0.3s; }
        .fade-in-delay-4 { animation-delay: 0.4s; }
        .fade-in-delay-5 { animation-delay: 0.5s; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Search Bar Glass Effect */
        .search-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .search-glass:focus-within {
            border-color: var(--yg-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--yg-primary) 15%, transparent);
            background: rgba(255, 255, 255, 0.95);
        }

        /* Card Hover Effect */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: var(--yg-shadow-lg);
        }

        /* Pulse dot for live status */
        .live-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--yg-success);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Gradient text utility */
        .gradient-text {
            background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Ecosystem App Grid Tile */
        .eco-tile {
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .eco-tile::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .eco-tile:hover::before {
            opacity: 1;
        }
        .eco-tile .icon-wrapper {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .eco-tile:hover .icon-wrapper {
            transform: scale(1.1) rotate(-3deg);
        }

        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>
<body class="antialiased">
    <div class="bg-ambient"></div>

    {{-- Admin Bar (visible to admins only) --}}
    @if(auth()->check() && (
        in_array(auth()->user()->email, array_filter(explode(',', env('ADMIN_EMAILS', '')))) ||
        in_array((string) auth()->id(), array_filter(explode(',', env('ADMIN_USER_IDS', ''))))
    ))
    <div class="bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white px-4 py-2 text-xs font-semibold z-50" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <span><i class="fas fa-shield-alt mr-2"></i>Admin Mode</span>
                <span class="opacity-60">|</span>
                <a href="{{ route('admin.dashboard') }}" class="hover:underline flex items-center gap-1.5">
                    <i class="fas fa-chart-line text-[10px]"></i> Dashboard
                </a>
                <a href="https://master.ygxone.com/admin" target="_blank" class="hover:underline flex items-center gap-1.5">
                    <i class="fas fa-external-link-alt text-[10px]"></i> Master Panel
                </a>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5">
                    <span class="live-dot"></span>
                    <span>Live</span>
                </span>
                <span class="opacity-40">{{ now()->format('H:i:s') }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Global Alpine State --}}
    <script>
        function appState() {
            return {
                searchQuery: '',
                suggestions: [],
                showSuggestions: false,
                loading: false,
                themeLoaded: false,

                init() {
                    this.themeLoaded = true;
                },

                async fetchSuggestions() {
                    if (this.searchQuery.length < 2) {
                        this.suggestions = [];
                        this.showSuggestions = false;
                        return;
                    }
                    
                    this.loading = true;
                    try {
                        const res = await fetch(`/api/search/suggestions?q=${encodeURIComponent(this.searchQuery)}`);
                        const data = await res.json();
                        this.suggestions = data.suggestions || [];
                        this.showSuggestions = this.suggestions.length > 0;
                    } catch (e) {
                        this.suggestions = [];
                    } finally {
                        this.loading = false;
                    }
                },

                selectSuggestion(suggestion) {
                    this.searchQuery = suggestion;
                    this.showSuggestions = false;
                    document.getElementById('search-form')?.submit();
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.suggestions = [];
                    this.showSuggestions = false;
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
