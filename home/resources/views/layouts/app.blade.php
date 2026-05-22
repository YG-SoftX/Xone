{{-- 
  YGXONE Main Layout — Dynamic & Theme-Aware
  Controlled from Master Panel via app_modules & themes tables
--}}
{{-- Load splash + PWA config from Master Panel EARLY (before <head> so it's available for meta tags) --}}
@php
    try {
        $browserConfig = app(\App\Services\BrowserConfigService::class);
        $splashConfig = $browserConfig->splashConfig();
        $pwaEnabled = $browserConfig->bool('pwa_install_enabled', true);
    } catch (\Exception $e) {
        $splashConfig = [
            'enabled' => true, 'title' => 'YGXONE', 'subtitle' => 'AI-Powered Agentic Browser',
            'logo_url' => '', 'bg_color' => '#0f172a', 'spinner_color' => '#2563eb',
        ];
        $pwaEnabled = true;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="appState()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $themeService?->getTheme()['colors']['primary'] ?? '#2563eb' }}">
    
    {{-- Dynamic PWA Manifest (reads from Master Panel Browser Settings) --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $splashConfig['title'] ?? 'YGXONE' }}">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    
    {{-- Apple Splash Screen (Launch Image) — only when splash is enabled in Master Panel --}}
    @if($splashConfig['enabled'] ?? true)
    {{-- iPhone SE, 6, 7, 8 --}}
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)" href="/icons/launch-750x1334.png">
    {{-- iPhone X, XS, 11 Pro --}}
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" href="/icons/launch-1125x2436.png">
    {{-- iPhone 11, XR --}}
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" href="/icons/launch-828x1792.png">
    {{-- iPhone 11 Pro Max, XS Max --}}
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)" href="/icons/launch-1242x2688.png">
    {{-- iPhone 12, 13, 14 --}}
    <link rel="apple-touch-startup-image" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)" href="/icons/launch-1170x2532.png">
    {{-- iPhone 14 Pro --}}
    <link rel="apple-touch-startup-image" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)" href="/icons/launch-1179x2556.png">
    {{-- iPad Mini, Air --}}
    <link rel="apple-touch-startup-image" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" href="/icons/launch-1536x2048.png">
    {{-- iPad Pro 11" --}}
    <link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" href="/icons/launch-1668x2388.png">
    {{-- iPad Pro 12.9" --}}
    <link rel="apple-touch-startup-image" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)" href="/icons/launch-2048x2732.png">
    @endif
    
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

        /* ── iOS / Apple WebKit Baseline ─────────────────────────── */
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sar: env(safe-area-inset-right, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
        }

        html {
            /* Prevent text size adjustment on orientation change (iOS) */
            -webkit-text-size-adjust: 100%;
            /* Enable momentum/kinetic scrolling on iOS */
            -webkit-overflow-scrolling: touch;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--yg-background);
            color: var(--yg-text);
            min-height: 100vh;
            /* Use dvh (dynamic viewport height) for iOS Safari to avoid toolbar overlap */
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            /* Safe-area-aware padding for iOS notch / home indicator */
            padding-top: var(--sat);
            padding-right: var(--sar);
            padding-bottom: var(--sab);
            padding-left: var(--sal);
            /* Prevent pull-to-refresh on iOS PWA when user is interacting with app UI */
            overscroll-behavior-y: none;
            overscroll-behavior-x: none;
            /* Remove tap highlight on iOS */
            -webkit-tap-highlight-color: transparent;
            /* Prevent iOS long-press callout menu on interactive chrome only */
            -webkit-touch-callout: none;
        }

        /* iOS input zoom prevention: 16px minimum font size (exclude custom-kept inputs) */
        input[type="text"]:not(.keep-native-font),
        input[type="search"]:not(.keep-native-font),
        input[type="url"]:not(.keep-native-font),
        input[type="email"]:not(.keep-native-font),
        input[type="password"]:not(.keep-native-font),
        textarea:not(.keep-native-font),
        select:not(.keep-native-font) {
            font-size: 16px !important;
            -webkit-appearance: none;
            appearance: none;
            border-radius: 0;
        }

        /* iOS momentum scroll containers */
        .ios-scroll {
            -webkit-overflow-scrolling: touch;
            overflow-y: auto;
        }

        /* Prevent overscroll bounce on fixed panels */
        .no-overscroll {
            overscroll-behavior: contain;
            -webkit-overscroll-behavior: contain;
        }

        /* iOS standalone mode (PWA) optimizations — scoped to touch devices */
        @supports (-webkit-touch-callout: none) {
            @media (display-mode: standalone) and (pointer: coarse) {
                body {
                    /* Full screen coverage for iOS PWA (notch + home indicator) */
                    position: fixed;
                    inset: 0;
                    overflow: hidden;
                }
            }
        }

        /* ── Custom Download Button Styles ───────────────────────── */
        .download-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            border-color: var(--yg-primary);
        }

        .download-btn:active {
            transform: translateY(0);
        }

        .download-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .download-btn:hover::before {
            left: 100%;
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

        /* ── iOS PWA Status Bar / Safe Area helpers ───────────────── */
        .safe-top { padding-top: var(--sat); }
        .safe-bottom { padding-bottom: var(--sab); }
        .safe-left { padding-left: var(--sal); }
        .safe-right { padding-right: var(--sar); }
        .safe-inset { padding: var(--sat) var(--sar) var(--sab) var(--sal); }

        /* CSS-only fix for iOS bottom safe area in PWA (home indicator gap) */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            @media (pointer: coarse) {
                .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
            }
        }
    </style>
    
    @stack('styles')
</head>