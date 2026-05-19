@php
    $ecoService = app(\App\Services\EcosystemService::class);
    $themeService = app(\App\Services\HomeThemeService::class);
    $activeApps = $ecoService->getActiveApps();
    $theme = $themeService->getTheme();
    $colors = $theme['colors'];
    $headerLogo = $themeService->getHeaderLogoHtml('h-10');
@endphp

@extends('layouts.app')

@section('title', 'YGXONE — Sovereign Intelligence Portal')

@section('content')
<div class="min-h-screen flex flex-col" x-data="homeState()" x-init="init()">
    
    {{-- ── Header / Navigation ── --}}
    <header class="relative z-50 px-4 sm:px-8 lg:px-12 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ route('search.home') }}" class="flex items-center gap-2 no-underline hover:opacity-90 transition-opacity">
                {!! $headerLogo !!}
            </a>

            {{-- Right Navigation --}}
            <nav class="flex items-center gap-2 sm:gap-4">
                {{-- Ecosystem Launcher --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click.prevent="open = !open" 
                            class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-all duration-200"
                            :class="open ? 'bg-[var(--yg-surface)]' : ''">
                        <i class="fas fa-th text-xs"></i>
                        <span class="hidden sm:inline">Apps</span>
                    </button>
                    
                    {{-- Apps Dropdown --}}
                    <div x-show="open" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 mt-2 w-72 sm:w-80 bg-white/95 backdrop-blur-xl border border-[var(--yg-border)] rounded-2xl shadow-2xl overflow-hidden">
                        <div class="p-3 border-b border-[var(--yg-border)]">
                            <span class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">Ecosystem Apps</span>
                        </div>
                        <div class="p-2 grid grid-cols-2 gap-1">
                            @foreach($activeApps as $app)
                            <a href="{{ $app['url'] }}" target="_blank"
                               class="flex items-center gap-3 p-3 rounded-xl hover:bg-[var(--yg-surface)] transition-all duration-200 group">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm"
                                     style="background: {{ $app['icon_color'] }}20; color: {{ $app['icon_color'] }}">
                                    <i class="{{ $app['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors">
                                        {{ $app['name'] }}
                                    </div>
                                    <div class="text-[11px] text-[var(--yg-text-dim)]">{{ $app['description'] }}</div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                        <div class="p-3 border-t border-[var(--yg-border)] bg-[var(--yg-surface)]/50">
                            <a href="https://master.ygxone.com/admin" target="_blank"
                               class="text-xs text-[var(--yg-text-dim)] hover:text-[var(--yg-primary)] transition-colors flex items-center gap-1.5">
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                                Manage apps from Master Panel
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Quick Links --}}
                <a href="{{ route('sso.initiate') }}" class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-all duration-200">
                    <i class="fas fa-question-circle text-xs"></i>
                    <span>Help</span>
                </a>

                {{-- Auth --}}
                @if(auth()->check())
                <div class="flex items-center gap-3">
                    <span class="hidden sm:block text-sm font-semibold text-[var(--yg-text)]">
                        {{ auth()->user()->name }}
                    </span>
                    <a href="{{ route('sso.logout') }}" 
                       class="w-9 h-9 rounded-xl bg-gradient-to-br from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white flex items-center justify-center text-sm hover:opacity-90 transition-all duration-200 shadow-lg shadow-[var(--yg-primary)]/20"
                       title="Sign out">
                        <i class="fas fa-sign-out-alt text-xs"></i>
                    </a>
                </div>
                @else
                <a href="{{ route('sso.initiate') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] hover:opacity-90 transition-all duration-200 shadow-lg shadow-[var(--yg-primary)]/25">
                    <i class="fas fa-key text-xs"></i>
                    <span>Login with YG</span>
                </a>
                @endif
            </nav>
        </div>
    </header>

    {{-- SSO Flash Messages --}}
    @if(session('sso_success'))
    <div class="max-w-lg mx-auto w-full px-4 mt-2 fade-in">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[var(--yg-success)]/10 border border-[var(--yg-success)]/20 text-sm font-medium" style="color: var(--yg-success)">
            <i class="fas fa-check-circle"></i>
            {{ session('sso_success') }}
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="max-w-lg mx-auto w-full px-4 mt-2 fade-in">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[var(--yg-danger)]/10 border border-[var(--yg-danger)]/20 text-sm font-medium" style="color: var(--yg-danger)">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    </div>
    @endif

    {{-- ── Hero Section ── --}}
    <section class="flex-1 flex flex-col items-center justify-center px-4 pb-16 sm:pb-24">
        
        {{-- Hero Logo / Title --}}
        <div class="text-center mb-8 fade-in-up">
            <div class="text-6xl sm:text-7xl lg:text-8xl font-heading font-black tracking-tighter mb-2">
                <span class="gradient-text">YGXONE</span>
            </div>
            <p class="text-sm sm:text-base text-[var(--yg-text-dim)] font-medium tracking-wide max-w-md mx-auto">
                Sovereign Intelligence Platform
            </p>
        </div>

        {{-- ── Search Bar ── --}}
        <div class="w-full max-w-xl px-4 mb-10 fade-in-up fade-in-delay-1">
            <form id="search-form" action="{{ route('search.index') }}" method="GET" class="relative">
                <div class="search-glass rounded-[calc(var(--yg-radius,12px)*2)] overflow-hidden transition-all duration-300">
                    <div class="flex items-center px-5 py-3.5 gap-3">
                        <i class="fas fa-search text-[var(--yg-text-dim)] text-lg opacity-60"></i>
                        <input type="text" 
                               name="q"
                               x-model="searchQuery"
                               @input.debounce.300ms="fetchSuggestions()"
                               @focus="if(suggestions.length) showSuggestions = true"
                               @keydown.escape="showSuggestions = false"
                               @keydown.enter="showSuggestions = false"
                               class="flex-1 bg-transparent border-none outline-none text-base sm:text-lg placeholder:text-[var(--yg-text-dim)]/50 focus:ring-0 font-body"
                               placeholder="Search the ecosystem or ask Yuga AI..."
                               autofocus
                               autocomplete="off">
                        
                        {{-- Loading Spinner --}}
                        <div x-show="loading" x-cloak>
                            <i class="fas fa-circle-notch fa-spin text-[var(--yg-text-dim)]"></i>
                        </div>
                        
                        {{-- Clear Button --}}
                        <button type="button" 
                                x-show="searchQuery.length > 0"
                                @click="clearSearch()"
                                x-cloak
                                class="p-1 hover:bg-[var(--yg-surface)] rounded-lg transition-colors text-[var(--yg-text-dim)]">
                            <i class="fas fa-times"></i>
                        </button>

                        {{-- Yuga AI Badge --}}
                        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)]">
                            <i class="fas fa-sparkles"></i>
                            <span>Yuga LLM</span>
                        </div>
                    </div>
                </div>

                {{-- Autocomplete Dropdown --}}
                <div x-show="showSuggestions" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     class="absolute top-full left-0 right-0 mt-2 bg-white/95 backdrop-blur-xl border border-[var(--yg-border)] rounded-2xl shadow-2xl overflow-hidden z-50">
                    <template x-for="(suggestion, index) in suggestions" :key="index">
                        <a href="#"
                           @click.prevent="selectSuggestion(suggestion)"
                           class="flex items-center gap-3 px-5 py-3 hover:bg-[var(--yg-surface)] transition-colors group">
                            <i class="fas fa-search text-[var(--yg-text-dim)] text-xs opacity-40 group-hover:opacity-60"></i>
                            <span class="text-sm text-[var(--yg-text)]" x-text="suggestion"></span>
                        </a>
                    </template>
                    <div class="px-5 py-2.5 border-t border-[var(--yg-border)] bg-[var(--yg-surface)]/50">
                        <span class="text-[11px] text-[var(--yg-text-dim)]">
                            <i class="fas fa-robot text-[10px] mr-1"></i>
                            AI-powered suggestions
                        </span>
                    </div>
                </div>
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-center gap-3 mt-4 fade-in-up fade-in-delay-2">
                <button onclick="document.getElementById('search-form').submit()" 
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-[var(--yg-surface)] text-[var(--yg-text)] hover:bg-[var(--yg-border)] transition-all duration-200 border border-[var(--yg-border)]">
                    <i class="fas fa-search text-xs"></i>
                    Ecosystem Search
                </button>
                <a href="https://ai.ygxone.com" target="_blank"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-[var(--yg-primary)]/10 to-[var(--yg-secondary)]/10 text-[var(--yg-primary)] hover:from-[var(--yg-primary)]/20 hover:to-[var(--yg-secondary)]/20 transition-all duration-200 border border-[var(--yg-primary)]/20">
                    <i class="fas fa-sparkles text-xs"></i>
                    Ask Yuga AI
                </a>
            </div>
        </div>

        {{-- ── Ecosystem App Grid ── --}}
        <div class="w-full max-w-4xl px-4 fade-in-up fade-in-delay-3">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-2 text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-[0.2em]">
                    <span class="w-8 h-px bg-[var(--yg-border)]"></span>
                    <span>YG Ecosystem</span>
                    <span class="w-8 h-px bg-[var(--yg-border)]"></span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                @foreach($activeApps as $app)
                <a href="{{ $app['url'] }}" target="_blank"
                   class="eco-tile group flex flex-col items-center gap-3 p-4 sm:p-5 rounded-[calc(var(--yg-radius,12px)*1.5)] bg-white border border-[var(--yg-border)] hover:border-transparent"
                   style="--tile-color: {{ $app['icon_color'] }}"
                   @mouseenter="$el.style.boxShadow = '0 8px 30px ' + getComputedStyle($el).getPropertyValue('--tile-color') + '25'"
                   @mouseleave="$el.style.boxShadow = ''">
                    
                    {{-- Icon --}}
                    <div class="icon-wrapper w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-xl sm:text-2xl text-white transition-all duration-300"
                         style="background: linear-gradient(135deg, {{ $app['icon_color'] }}, {{ $app['icon_color'] }}cc)">
                        <i class="{{ $app['icon'] }}"></i>
                    </div>
                    
                    {{-- Label --}}
                    <div class="text-center">
                        <div class="text-sm font-bold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors">
                            {{ $app['name'] }}
                        </div>
                        <div class="text-[11px] text-[var(--yg-text-dim)] mt-0.5">{{ $app['description'] }}</div>
                    </div>

                    {{-- Hover Glow --}}
                    <div class="absolute inset-0 rounded-[calc(var(--yg-radius,12px)*1.5)] opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                         style="background: radial-gradient(200px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), {{ $app['icon_color'] }}08, transparent)"></div>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Footer ── --}}
    <footer class="relative z-10 border-t border-[var(--yg-border)] bg-white/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-12 py-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-4 sm:gap-6">
                    <a href="#" class="text-xs font-medium text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] transition-colors">About</a>
                    <a href="#" class="text-xs font-medium text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] transition-colors">Privacy</a>
                    <a href="#" class="text-xs font-medium text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] transition-colors">Terms</a>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[var(--yg-text-dim)]">
                        {{ $themeService->getCopyrightText() }}
                    </span>
                    <span class="live-dot ml-2"></span>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
    function homeState() {
        return {
            searchQuery: '',
            suggestions: [],
            showSuggestions: false,
            loading: false,

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
                const form = document.getElementById('search-form');
                const input = form.querySelector('input[name="q"]');
                if (input) input.value = suggestion;
                form.submit();
            },

            clearSearch() {
                this.searchQuery = '';
                this.suggestions = [];
                this.showSuggestions = false;
            }
        }
    }
</script>
@endsection
