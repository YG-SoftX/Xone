@php
    $themeService = app(\App\Services\HomeThemeService::class);
    $ecoService = app(\App\Services\EcosystemService::class);
    $activeApps = $ecoService->getActiveApps();
    $theme = $themeService->getTheme();
    $colors = $theme['colors'];
    $headerLogo = $themeService->getHeaderLogoHtml('h-8');
    $currentUrl = request('url', '');
    $isBrowsing = !empty($currentUrl);
    
    // Browser config from master admin panel
    $browserConfig = app(\App\Services\BrowserConfigService::class);
    $quickLinks = $browserConfig->quickLinks();
    $searchEngine = $browserConfig->defaultSearchEngine();
    $agentEnabled = $browserConfig->bool('agent_enabled', true);
    $byokEnabled = $browserConfig->bool('byok_enabled', true);
    $pwaInstallEnabled = $browserConfig->bool('pwa_install_enabled', true);
    $browserName = $browserConfig->get('browser_brand_name', 'YGXONE Agentic Browser');
@endphp

@extends('layouts.app')

@section('title', $isBrowsing ? ($pageTitle ?? 'Browsing...') . ' — YGXONE Browser' : 'YGXONE — Agentic Browser')

@section('content')
<div x-data="browserState()" x-init="init()" class="flex flex-col h-screen">

    {{-- ── TOP: Navigation Bar ── --}}
    <header class="relative z-50 flex-shrink-0 bg-white/90 backdrop-blur-xl border-b border-[var(--yg-border)] shadow-sm">
        <div class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-4 py-2">

            {{-- Logo / Home button --}}
            <a href="{{ route('browser.home') }}"
               class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-[var(--yg-surface)] transition-colors flex-shrink-0"
               title="Home">
                {!! $headerLogo !!}
            </a>

            {{-- Navigation buttons --}}
            <div class="flex items-center gap-0.5">
                <button @click="goBack()"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors disabled:opacity-30"
                        :disabled="!canGoBack"
                        title="Back">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button @click="goForward()"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors disabled:opacity-30"
                        :disabled="!canGoForward"
                        title="Forward">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
                <button @click="refresh()"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        title="Refresh">
                    <i class="fas fa-redo text-xs" :class="{ 'fa-spin': loading }"></i>
                </button>
                <button @click="navigateTo('')"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        title="Home">
                    <i class="fas fa-home text-xs"></i>
                </button>
            </div>

            {{-- Omnibox — the key component: URL bar + search --}}
            <form @submit.prevent="navigateTo(urlInput)"
                  class="flex-1 min-w-0 mx-1 sm:mx-2">
                <div class="search-glass rounded-xl flex items-center gap-2 px-3 sm:px-4 py-2 transition-all duration-200 border border-[var(--yg-border)]"
                     :class="{ 'ring-2 ring-[var(--yg-primary)]/20 border-[var(--yg-primary)]': focused }">
                    {{-- Padlock icon for HTTPS --}}
                    <template x-if="currentUrl && currentUrl.startsWith('https://')">
                        <i class="fas fa-lock text-[10px] text-[var(--yg-success)]"></i>
                    </template>
                    <template x-if="currentUrl && !currentUrl.startsWith('https://')">
                        <i class="fas fa-globe text-[11px] text-[var(--yg-text-dim)] opacity-50"></i>
                    </template>
                    <template x-if="!currentUrl">
                        <i class="fas fa-search text-[var(--yg-text-dim)]"></i>
                    </template>
                    <input type="text"
                           x-model="urlInput"
                           x-ref="omnibox"
                           @focus="focused = true"
                           @blur="focused = false"
                           @keydown.escape="urlInput = currentUrl || ''; $refs.omnibox.blur()"
                           class="flex-1 bg-transparent border-none outline-none text-sm placeholder:text-[var(--yg-text-dim)]/40 focus:ring-0 font-body min-w-0"
                           placeholder="Search or enter website URL..."
                           autocomplete="off"
                           spellcheck="false">
                    {{-- Clear button --}}
                    <button type="button"
                            x-show="urlInput.length > 0"
                            @click="urlInput = ''; $refs.omnibox.focus()"
                            class="p-1 hover:bg-[var(--yg-surface)] rounded-lg transition-colors text-[var(--yg-text-dim)] flex-shrink-0">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                    {{-- Go button --}}
                    <button type="submit"
                            class="p-1.5 rounded-lg bg-[var(--yg-surface)] hover:bg-[var(--yg-border)] transition-colors text-[var(--yg-text)] flex-shrink-0"
                            title="Go">
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </button>
                </div>
            </form>

            {{-- Right actions --}}
            <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                {{-- Ecosystem Apps toggle --}}
                <button @click="showApps = !showApps"
                        class="w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        :class="{ 'bg-[var(--yg-surface)]': showApps }"
                        title="YG Apps">
                    <i class="fas fa-th text-xs"></i>
                    <span class="hidden sm:inline">Apps</span>
                </button>

                {{-- AI Agent toggle (controlled by master admin) --}}
                @if($agentEnabled)
                <button @click="showAgent = !showAgent; if (showAgent) checkAgentStatus()"
                        class="w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-semibold text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] hover:opacity-90 transition-all shadow-sm"
                        :class="{ 'ring-2 ring-[var(--yg-primary)]/30': showAgent }"
                        title="AI Agent">
                    <i class="fas fa-sparkles text-[10px]"></i>
                    <span class="hidden sm:inline">AI</span>
                </button>
                @endif

                {{-- Auth --}}
                @if(auth()->check())
                <a href="{{ route('sso.logout') }}"
                   class="w-8 h-8 rounded-lg bg-[var(--yg-surface)] flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-border)] transition-colors text-xs"
                   title="Sign out">
                    <i class="fas fa-sign-out-alt text-[10px]"></i>
                </a>
                @else
                <a href="{{ route('sso.initiate') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] hover:opacity-90 transition-all">
                    <i class="fas fa-key text-[10px]"></i>
                    <span class="hidden sm:inline">Login</span>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- ── MIDDLE: Content Area ── --}}
    <div class="flex-1 flex overflow-hidden relative">

        {{-- MAIN CONTENT: Web Page (iframe) or Home Grid --}}
        <div class="flex-1 flex flex-col min-w-0 bg-white" :class="{ 'mr-[340px]': showAgent }">
            <template x-if="isBrowsing && currentUrl">
                {{-- Loading bar --}}
                <div x-show="loading" x-cloak
                     class="h-0.5 bg-[var(--yg-surface)] overflow-hidden flex-shrink-0">
                    <div class="h-full w-1/3 bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] animate-pulse"></div>
                </div>
                {{-- Iframe --}}
                <iframe x-ref="browserFrame"
                        :src="'/browse?url=' + encodeURIComponent(currentUrl)"
                        class="flex-1 w-full border-0"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                        @load="onFrameLoad()"
                        allow="fullscreen"
                        referrerpolicy="no-referrer">
                </iframe>
            </template>

            {{-- Home state — show ecosystem grid + search --}}
            <template x-if="!isBrowsing || !currentUrl">
                <div class="flex-1 flex flex-col items-center justify-center px-4 py-8 overflow-y-auto">
                    <div class="w-full max-w-lg text-center mb-8 fade-in-up">
                        <div class="text-5xl sm:text-6xl font-heading font-black tracking-tighter mb-2">
                            <span class="gradient-text">{{ $browserName }}</span>
                        </div>
                        <p class="text-sm text-[var(--yg-text-dim)] font-medium">
                            Agentic Browser — Browse anything, automate everything
                        </p>
                    </div>

                    {{-- Quick Links (from Master Admin panel) --}}
                    <div class="w-full max-w-2xl mb-12 fade-in-up fade-in-delay-1">
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 sm:gap-3">
                            @foreach($quickLinks as $link)
                            <button @click="navigateTo('{{ $link['url'] }}')"
                                    class="eco-tile group flex flex-col items-center gap-2 p-3 sm:p-4 rounded-xl bg-white border border-[var(--yg-border)] hover:border-transparent transition-all duration-300"
                                    style="--tile-color: {{ $link['color'] }}">
                                <div class="icon-wrapper w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl text-white transition-transform duration-300"
                                     style="background: {{ $link['color'] }}">
                                    <i class="{{ $link['icon'] }}"></i>
                                </div>
                                <span class="text-xs font-semibold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors">
                                    {{ $link['name'] }}
                                </span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- YG Ecosystem Grid --}}
                    <div class="w-full max-w-4xl fade-in-up fade-in-delay-2">
                        <div class="text-center mb-6">
                            <div class="flex items-center justify-center gap-2 text-[11px] font-bold text-[var(--yg-text-dim)] uppercase tracking-[0.2em]">
                                <span class="w-8 h-px bg-[var(--yg-border)]"></span>
                                <span>YG Ecosystem</span>
                                <span class="w-8 h-px bg-[var(--yg-border)]"></span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3">
                            @foreach($activeApps as $app)
                            <a href="{{ $app['url'] }}" target="_blank"
                               class="eco-tile group flex flex-col items-center gap-2 p-3 sm:p-4 rounded-xl bg-white border border-[var(--yg-border)] hover:border-transparent transition-all duration-300"
                               style="--tile-color: {{ $app['icon_color'] }}">
                                <div class="icon-wrapper w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl text-white transition-transform duration-300"
                                     style="background: linear-gradient(135deg, {{ $app['icon_color'] }}, {{ $app['icon_color'] }}cc)">
                                    <i class="{{ $app['icon'] }}"></i>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs font-bold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors">
                                        {{ $app['name'] }}
                                    </div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- SIDE PANEL: AI Agent --}}
        <div x-show="showAgent"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[340px] flex-shrink-0 border-l border-[var(--yg-border)] bg-[var(--yg-surface)]/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-white/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-sparkles text-[var(--yg-primary)] text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">YG Agent</span>
                </div>
                <button @click="showAgent = false"
                        class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Agent chat area --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="agentMessages">
                {{-- Welcome / not configured state --}}
                <div x-show="agentMessages.length <= 1 && !agentConfigured" class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-[var(--yg-primary)]/10 to-[var(--yg-secondary)]/10 flex items-center justify-center">
                        <i class="fas fa-robot text-2xl" style="background:linear-gradient(135deg,var(--yg-primary),var(--yg-secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent"></i>
                    </div>
                    <h3 class="text-sm font-bold text-[var(--yg-text)] mb-1">YG Agentic Browser</h3>
                    <p class="text-xs text-[var(--yg-text-dim)] leading-relaxed mb-3">
                        Add your API key to enable AI-powered browsing.
                    </p>
                    <a href="/agent/settings" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white text-xs font-bold hover:opacity-90 transition-all">
                        <i class="fas fa-key text-[10px]"></i> Configure Agent
                    </a>
                </div>

                {{-- Agent conversation messages --}}
                <template x-for="msg in agentMessages" :key="msg.id">
                    <div>
                        {{-- User message --}}
                        <div x-show="msg.role === 'user'" class="flex gap-2 justify-end mb-3">
                            <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs bg-[var(--yg-primary)] text-white">
                                <span x-text="msg.content"></span>
                            </div>
                        </div>

                        {{-- Agent response (final) --}}
                        <div x-show="msg.role === 'assistant'" class="flex gap-2 mb-3">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-[var(--yg-primary)]/20 to-[var(--yg-secondary)]/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-sparkles text-[8px] text-[var(--yg-primary)]"></i>
                            </div>
                            <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs bg-white border border-[var(--yg-border)] text-[var(--yg-text)]">
                                <span x-text="msg.content"></span>
                                {{-- Show steps if present --}}
                                <div x-show="msg.steps && msg.steps.length > 0" class="mt-2 pt-2 border-t border-[var(--yg-border)]">
                                    <div class="text-[10px] text-[var(--yg-text-dim)] space-y-1">
                                        <template x-for="step in msg.steps" :key="step.step">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fas" :class="step.type === 'tool' ? 'fa-cog text-[var(--yg-primary)]' : 'fa-check text-[var(--yg-success)]'"></i>
                                                <span x-text="step.tool || 'Done'"></span>
                                                <span x-show="step.result" class="text-[var(--yg-success)]">✓</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Agent thinking indicator --}}
                        <div x-show="msg.role === 'thinking'" class="flex gap-2 mb-3">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-[var(--yg-primary)]/20 to-[var(--yg-secondary)]/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-sparkles text-[8px] text-[var(--yg-primary)]"></i>
                            </div>
                            <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs bg-white border border-[var(--yg-border)] text-[var(--yg-text-dim)] italic">
                                <i class="fas fa-circle-notch fa-spin mr-1.5 text-[9px]"></i>
                                <span x-text="msg.content"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Agent input --}}
            <div class="p-3 border-t border-[var(--yg-border)] bg-white/50">
                <form @submit.prevent="sendAgentMessage()" class="flex gap-2">
                    <input type="text"
                           x-model="agentInput"
                           placeholder="What should I do? (e.g., 'Find the price of iPhone 16')"
                           :disabled="agentRunning"
                           class="flex-1 bg-white border border-[var(--yg-border)] rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-[var(--yg-primary)]/20 focus:border-[var(--yg-primary)] disabled:opacity-50">
                    <button type="submit"
                            class="w-8 h-8 rounded-xl bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white flex items-center justify-center hover:opacity-90 transition-all text-xs flex-shrink-0 disabled:opacity-50"
                            :disabled="!agentInput.trim() || agentRunning">
                        <i class="fas" :class="agentRunning ? 'fa-circle-notch fa-spin text-[10px]' : 'fa-paper-plane text-[10px]'"></i>
                    </button>
                </form>
                <div class="flex items-center justify-between mt-1.5">
                    <a href="/agent/settings" target="_blank" class="text-[10px] text-[var(--yg-text-dim)] hover:text-[var(--yg-primary)] transition-colors">
                        <i class="fas fa-cog text-[9px] mr-1"></i>Agent settings
                    </a>
                    <span x-show="!agentConfigured" class="text-[10px] text-amber-600">
                        <i class="fas fa-exclamation-triangle text-[9px] mr-1"></i>Not configured
                    </span>
                    <span x-show="agentConfigured" class="text-[10px] text-[var(--yg-success)]">
                        <i class="fas fa-circle text-[6px] mr-1"></i>Ready
                    </span>
                </div>
            </div>
        </div>

        {{-- SIDE PANEL: Ecosystem Apps --}}
        <div x-show="showApps"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[300px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)]">
                <span class="text-sm font-bold text-[var(--yg-text)]">YG Ecosystem</span>
                <button @click="showApps = false"
                        class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 grid grid-cols-2 gap-1 auto-rows-max">
                @foreach($activeApps as $app)
                <a href="{{ $app['url'] }}" target="_blank"
                   class="flex items-center gap-3 p-3 rounded-xl hover:bg-[var(--yg-surface)] transition-colors group">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm flex-shrink-0"
                         style="background: {{ $app['icon_color'] }}">
                        <i class="{{ $app['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors truncate">
                            {{ $app['name'] }}
                        </div>
                        <div class="text-[10px] text-[var(--yg-text-dim)] truncate">{{ $app['description'] }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── BOTTOM: Status Bar ── --}}
    <div class="flex-shrink-0 bg-[var(--yg-surface)] border-t border-[var(--yg-border)] px-3 py-1.5 flex items-center gap-4 text-[10px] text-[var(--yg-text-dim)]">
        <span x-text="currentUrl || '{{ $browserName }}'"></span>
        <span class="flex-1"></span>
        <span x-show="loading" x-cloak>
            <i class="fas fa-circle-notch fa-spin mr-1 text-[9px]"></i>Loading...
        </span>
        <span>v1.0</span>
    </div>
</div>

{{-- ── Alpine State ── --}}
<script>
    function browserState() {
        return {
            // Browser state
            urlInput: '{{ $currentUrl }}',
            currentUrl: '{{ $currentUrl }}',
            history: [],
            historyIndex: -1,
            canGoBack: false,
            canGoForward: false,
            loading: false,
            focused: false,
            isBrowsing: {{ $isBrowsing ? 'true' : 'false' }},
            pageTitle: '{{ $pageTitle ?? '' }}',

            // Panels
            showAgent: false,
            showApps: false,

            // Agent state
            agentInput: '',
            agentRunning: false,
            agentConfigured: false,
            agentMessages: [],

            init() {
                // Load history from session storage
                try {
                    const saved = sessionStorage.getItem('yg_browser_history');
                    if (saved) {
                        const data = JSON.parse(saved);
                        this.history = data.history || [];
                        this.historyIndex = data.index ?? -1;
                        this.updateNavButtons();
                    }
                } catch (e) {}

                // Check agent configuration status
                fetch('/agent/status')
                    .then(r => r.json())
                    .then(data => {
                        this.agentConfigured = data.configured;
                        if (data.configured) {
                            this.agentMessages = [{
                                id: Date.now(),
                                role: 'assistant',
                                content: 'Hello! I\'m your YG browsing agent. I can navigate websites, fill forms, extract information, and search the web for you. What would you like me to do?'
                            }];
                        }
                    })
                    .catch(() => {});

                // Focus omnibox if not browsing
                if (!this.isBrowsing) {
                    setTimeout(() => { if (this.$refs.omnibox) this.$refs.omnibox.focus(); }, 100);
                }

                // Listen for iframe messages
                window.addEventListener('message', (e) => {
                    if (e.data && e.data.type === 'yg_navigate') {
                        this.navigateTo(e.data.url);
                    }
                });

                // Listen for browser back/forward buttons (popstate)
                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.url) {
                        const url = e.state.url;
                        this.currentUrl = url;
                        this.urlInput = url;
                        this.isBrowsing = true;
                        this.loading = true;
                        this.updateNavButtons();
                        this.saveHistory();
                        if (this.$refs.browserFrame) {
                            this.$refs.browserFrame.src = '/browse?url=' + encodeURIComponent(url);
                        }
                    } else {
                        this.currentUrl = '';
                        this.urlInput = '';
                        this.isBrowsing = false;
                        this.history = [];
                        this.historyIndex = -1;
                        this.updateNavButtons();
                        this.saveHistory();
                    }
                });
            },

            navigateTo(url) {
                url = url.trim();
                if (!url) return;

                // Detect if it's a search query or URL
                if (!url.includes('.') && !url.startsWith('http') && !url.startsWith('localhost')) {
                    // Treat as search — redirect to ecosystem search
                    window.location.href = '/search?q=' + encodeURIComponent(url);
                    return;
                }

                // Add protocol if missing
                if (!url.startsWith('http://') && !url.startsWith('https://')) {
                    url = 'https://' + url;
                }

                // Push to history
                if (this.currentUrl && this.currentUrl !== url) {
                    // Trim forward history
                    this.history = this.history.slice(0, this.historyIndex + 1);
                    this.history.push(url);
                    this.historyIndex = this.history.length - 1;
                } else if (!this.currentUrl) {
                    this.history.push(url);
                    this.historyIndex = this.history.length - 1;
                }

                this.currentUrl = url;
                this.urlInput = url;
                this.isBrowsing = true;
                this.loading = true;

                // Update browser URL without reload
                if (history.pushState) {
                    const newUrl = '/browser?url=' + encodeURIComponent(url);
                    history.pushState({ url: url }, '', newUrl);
                }

                this.updateNavButtons();
                this.saveHistory();
            },

            goBack() {
                if (!this.canGoBack) return;
                this.historyIndex--;
                const url = this.history[this.historyIndex];
                this.loadUrlFromHistory(url);
            },

            goForward() {
                if (!this.canGoForward) return;
                this.historyIndex++;
                const url = this.history[this.historyIndex];
                this.loadUrlFromHistory(url);
            },

            loadUrlFromHistory(url) {
                this.currentUrl = url;
                this.urlInput = url;
                this.isBrowsing = true;
                this.loading = true;
                this.updateNavButtons();
                this.saveHistory();

                if (history.pushState) {
                    const newUrl = '/browser?url=' + encodeURIComponent(url);
                    history.pushState({ url: url }, '', newUrl);
                }

                // Reload iframe
                if (this.$refs.browserFrame) {
                    this.$refs.browserFrame.src = '/browse?url=' + encodeURIComponent(url);
                }
            },

            refresh() {
                if (!this.currentUrl) return;
                this.loading = true;
                if (this.$refs.browserFrame) {
                    this.$refs.browserFrame.src = this.$refs.browserFrame.src;
                }
            },

            onFrameLoad() {
                this.loading = false;
            },

            updateNavButtons() {
                this.canGoBack = this.historyIndex > 0;
                this.canGoForward = this.historyIndex < this.history.length - 1;
            },

            saveHistory() {
                try {
                    sessionStorage.setItem('yg_browser_history', JSON.stringify({
                        history: this.history,
                        index: this.historyIndex
                    }));
                } catch (e) {}
            },

            // Agent — sends task to /agent/run and displays results
            sendAgentMessage() {
                const text = this.agentInput.trim();
                if (!text || this.agentRunning) return;

                if (!this.agentConfigured) {
                    this.agentMessages.push({
                        id: Date.now(),
                        role: 'assistant',
                        content: 'Please configure your API key first. Go to Agent Settings to add your own OpenAI, Claude, or Ollama key.'
                    });
                    this.scrollAgentChat();
                    return;
                }

                // Add user message
                this.agentMessages.push({ id: Date.now(), role: 'user', content: text });
                this.agentInput = '';
                this.agentRunning = true;

                // Add thinking indicator
                const thinkingId = Date.now() + 1;
                this.agentMessages.push({ id: thinkingId, role: 'thinking', content: 'Processing your task...' });
                this.scrollAgentChat();

                // Call agent API
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                fetch('/agent/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        task: text,
                        current_url: this.currentUrl || '',
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    // Remove thinking indicator
                    this.agentMessages = this.agentMessages.filter(m => m.id !== thinkingId);

                    // Add agent response with steps
                    this.agentMessages.push({
                        id: Date.now(),
                        role: 'assistant',
                        content: data.response || 'No response received.',
                        steps: data.steps || [],
                    });
                    this.agentRunning = false;
                    this.scrollAgentChat();
                })
                .catch(err => {
                    this.agentMessages = this.agentMessages.filter(m => m.id !== thinkingId);
                    this.agentMessages.push({
                        id: Date.now(),
                        role: 'assistant',
                        content: 'Sorry, an error occurred. Please check your API key and try again.',
                    });
                    this.agentRunning = false;
                    this.scrollAgentChat();
                });
            },

            scrollAgentChat() {
                this.$nextTick(() => {
                    if (this.$refs.agentMessages) {
                        this.$refs.agentMessages.scrollTop = this.$refs.agentMessages.scrollHeight;
                    }
                });
            },

            checkAgentStatus() {
                fetch('/agent/status')
                    .then(r => r.json())
                    .then(data => {
                        this.agentConfigured = data.configured;
                        if (data.configured && this.agentMessages.length === 0) {
                            this.agentMessages = [{
                                id: Date.now(),
                                role: 'assistant',
                                content: 'Hello! I\'m your YG browsing agent. I can navigate websites, fill forms, extract information, and search the web for you. What would you like me to do?'
                            }];
                        }
                    })
                    .catch(() => {});
            }
        }
    }
</script>
@endsection
