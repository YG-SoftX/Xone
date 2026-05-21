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

    {{-- ── TAB BAR ── --}}
    <div class="flex-shrink-0 bg-[var(--yg-surface)] border-b border-[var(--yg-border)] flex items-center gap-0.5 px-1 py-1 overflow-x-auto scrollbar-hide"
         x-show="tabs.length > 0"
         x-cloak>
        {{-- Tab strip --}}
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="switchTab(tab.id)"
                    class="tab-btn group relative flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex-shrink-0 max-w-[160px]"
                    :class="tab.id === activeTabId
                        ? 'bg-white text-[var(--yg-text)] shadow-sm border border-[var(--yg-border)]'
                        : 'text-[var(--yg-text-dim)] hover:bg-white/50 hover:text-[var(--yg-text)]'">
                {{-- Favicon/initial --}}
                <span class="w-4 h-4 rounded flex items-center justify-center text-[9px] font-bold flex-shrink-0"
                      :style="tab.id === activeTabId ? 'background:var(--yg-primary);color:white' : 'background:var(--yg-border);color:var(--yg-text-dim)'"
                      x-text="tab.title ? tab.title.charAt(0).toUpperCase() : '?'">
                </span>
                {{-- Tab title --}}
                <span class="truncate" x-text="tab.title || 'New Tab'"></span>
                {{-- Close button --}}
                <button @click.stop="closeTab(tab.id)"
                        class="w-4 h-4 rounded flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] hover:text-[var(--yg-text)] transition-colors flex-shrink-0 ml-0.5">
                    <i class="fas fa-times text-[8px]"></i>
                </button>
            </button>
        </template>
        {{-- New tab button --}}
        <button @click="newTab()"
                class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white hover:text-[var(--yg-primary)] transition-all flex-shrink-0 border border-dashed border-[var(--yg-border)] hover:border-[var(--yg-primary)]"
                title="New tab">
            <i class="fas fa-plus text-[10px]"></i>
        </button>
    </div>

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

                {{-- Deep Research Mode toggle --}}
                <button @click="showResearch = !showResearch"
                        class="w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium transition-all"
                        :class="showResearch ? 'bg-purple-100 text-purple-700 border border-purple-200' : 'text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)]'"
                        title="Deep Research Mode">
                    <i class="fas fa-microscope text-xs"></i>
                    <span class="hidden sm:inline">Research</span>
                </button>

                {{-- Citations Panel toggle --}}
                <button @click="showCitations = !showCitations"
                        class="relative w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        :class="{ 'bg-[var(--yg-surface)]': showCitations }"
                        title="Citations">
                    <i class="fas fa-quote-right text-xs"></i>
                    <span class="hidden sm:inline">Cite</span>
                    <span x-show="citationCount > 0"
                          class="absolute -top-1 -right-1 bg-[var(--yg-primary)] text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
                          x-text="citationCount"></span>
                </button>

                {{-- Knowledge Graph toggle --}}
                <button @click="showKnowledgeGraph = !showKnowledgeGraph"
                        class="w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        :class="{ 'bg-[var(--yg-surface)]': showKnowledgeGraph }"
                        title="Knowledge Graph">
                    <i class="fas fa-project-diagram text-xs"></i>
                    <span class="hidden sm:inline">Graph</span>
                </button>

                {{-- Visual Summaries toggle --}}
                <button @click="showVisualSummaries = !showVisualSummaries"
                        class="w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        :class="{ 'bg-[var(--yg-surface)]': showVisualSummaries }"
                        title="Visual Summaries (Charts & Tables)">
                    <i class="fas fa-chart-bar text-xs"></i>
                    <span class="hidden sm:inline">Charts</span>
                </button>

                {{-- Password Manager toggle --}}
                <button @click="showPasswords = !showPasswords"
                        class="relative w-8 h-8 sm:w-auto sm:px-3 rounded-lg flex items-center gap-1.5 text-xs font-medium text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors"
                        :class="{ 'bg-[var(--yg-surface)]': showPasswords }"
                        title="Password Manager">
                    <i class="fas fa-key text-xs"></i>
                    <span class="hidden sm:inline">Keys</span>
                    <span x-show="passwordCount > 0"
                          class="absolute -top-1 -right-1 bg-green-500 text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
                          x-text="passwordCount"></span>
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

        {{-- SIDE PANEL: Deep Research --}}
        <div x-show="showResearch"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[400px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-gradient-to-r from-purple-50 to-blue-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-microscope text-purple-600 text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">Deep Research</span>
                </div>
                <button @click="showResearch = false"
                        class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Research input --}}
            <div class="p-4 border-b border-[var(--yg-border)] bg-white/30">
                <form @submit.prevent="startDeepResearch()" class="space-y-2">
                    <textarea x-model="researchQuery"
                              placeholder="Enter research topic (e.g., 'Latest advances in quantum computing 2025')"
                              rows="3"
                              class="w-full bg-white border border-[var(--yg-border)] rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 resize-none"></textarea>
                    <div class="flex items-center justify-between">
                        <select x-model="researchMaxPages" class="text-[10px] bg-white border border-[var(--yg-border)] rounded-lg px-2 py-1">
                            <option value="3">Quick (3 pages)</option>
                            <option value="8" selected>Balanced (8 pages)</option>
                            <option value="15">Thorough (15 pages)</option>
                        </select>
                        <button type="submit"
                                :disabled="!researchQuery.trim() || researchRunning"
                                class="px-4 py-1.5 rounded-xl bg-gradient-to-r from-purple-600 to-blue-600 text-white text-xs font-bold hover:opacity-90 transition-all disabled:opacity-50">
                            <i class="fas" :class="researchRunning ? 'fa-circle-notch fa-spin' : 'fa-search'" class="mr-1"></i>
                            <span x-text="researchRunning ? 'Researching...' : 'Start Research'"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Research progress/results --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="researchResults">
                {{-- Empty state --}}
                <div x-show="!researchStarted && !researchComplete" class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center">
                        <i class="fas fa-microscope text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-sm font-bold text-[var(--yg-text)] mb-1">Deep Research Mode</h3>
                    <p class="text-xs text-[var(--yg-text-dim)] leading-relaxed">
                        I'll search multiple sources, analyze content, and generate a comprehensive report with citations.
                    </p>
                </div>

                {{-- Progress indicator --}}
                <div x-show="researchRunning" class="space-y-3">
                    <div class="flex items-center gap-2 text-xs text-purple-600">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span x-text="researchStatus"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-600 to-blue-600 h-2 rounded-full transition-all duration-300"
                             :style="'width: ' + researchProgress + '%'"></div>
                    </div>
                </div>

                {{-- Research report --}}
                <div x-show="researchComplete && researchReport" class="space-y-4">
                    {{-- Executive Summary --}}
                    <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl p-3 border border-purple-200">
                        <h4 class="text-xs font-bold text-purple-800 mb-2 flex items-center gap-1">
                            <i class="fas fa-star text-[10px]"></i> Executive Summary
                        </h4>
                        <div class="text-xs text-gray-700 leading-relaxed whitespace-pre-line" x-text="researchReport?.executive_summary"></div>
                    </div>

                    {{-- Sections --}}
                    <template x-for="(section, idx) in researchReport?.sections" :key="idx">
                        <div class="bg-white rounded-xl p-3 border border-[var(--yg-border)]">
                            <h4 class="text-xs font-bold text-[var(--yg-text)] mb-2" x-text="section.heading"></h4>
                            <div class="text-xs text-[var(--yg-text-dim)] leading-relaxed whitespace-pre-line" x-text="section.content"></div>
                        </div>
                    </template>

                    {{-- Key Takeaways --}}
                    <div x-show="researchReport?.key_takeaways?.length > 0" class="bg-green-50 rounded-xl p-3 border border-green-200">
                        <h4 class="text-xs font-bold text-green-800 mb-2 flex items-center gap-1">
                            <i class="fas fa-lightbulb text-[10px]"></i> Key Takeaways
                        </h4>
                        <ul class="space-y-1">
                            <template x-for="(takeaway, idx) in researchReport.key_takeaways" :key="idx">
                                <li class="text-xs text-gray-700 flex items-start gap-1.5">
                                    <i class="fas fa-check text-green-600 text-[9px] mt-0.5"></i>
                                    <span x-text="takeaway"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- Sources --}}
                    <div x-show="researchReport?.sources?.length > 0" class="border-t border-[var(--yg-border)] pt-3">
                        <h4 class="text-xs font-bold text-[var(--yg-text)] mb-2 flex items-center gap-1">
                            <i class="fas fa-bookmark text-[10px]"></i> Sources (<span x-text="researchReport.sources.length"></span>)
                        </h4>
                        <div class="space-y-1">
                            <template x-for="(source, idx) in researchReport.sources" :key="idx">
                                <a :href="source.url" target="_blank" class="block text-[10px] text-blue-600 hover:text-blue-800 truncate">
                                    <span x-text="(idx + 1) + '. ' + source.title"></span>
                                </a>
                            </template>
                        </div>
                    </div>

                    {{-- Metadata --}}
                    <div class="text-[10px] text-[var(--yg-text-dim)] border-t border-[var(--yg-border)] pt-2">
                        <div>Completed in <span x-text="researchElapsed"></span>s • <span x-text="researchReport?.sources_count"></span> sources analyzed</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SIDE PANEL: Citations --}}
        <div x-show="showCitations"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[350px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-gradient-to-r from-yellow-50 to-orange-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-quote-right text-orange-600 text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">Citations</span>
                    <span x-show="citationCount > 0" class="bg-orange-500 text-white text-[9px] font-bold rounded-full px-1.5 py-0.5" x-text="citationCount"></span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="exportCitations()" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs" title="Export">
                        <i class="fas fa-download text-[10px]"></i>
                    </button>
                    <button @click="clearCitationsList()" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs" title="Clear all">
                        <i class="fas fa-trash text-[10px]"></i>
                    </button>
                    <button @click="showCitations = false" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Citation format selector --}}
            <div class="p-3 border-b border-[var(--yg-border)] bg-white/30">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-[var(--yg-text-dim)]">Format:</span>
                    <select x-model="citationStyle" class="text-[10px] bg-white border border-[var(--yg-border)] rounded-lg px-2 py-1">
                        <option value="apa">APA</option>
                        <option value="mla">MLA</option>
                        <option value="chicago">Chicago</option>
                    </select>
                </div>
            </div>

            {{-- Citations list --}}
            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                {{-- Empty state --}}
                <div x-show="citations.length === 0" class="text-center py-8">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-quote-right text-lg text-orange-600"></i>
                    </div>
                    <p class="text-xs text-[var(--yg-text-dim)]">No citations yet.<br>Sources will appear here as you browse.</p>
                </div>

                {{-- Citation items --}}
                <template x-for="citation in citations" :key="citation.id">
                    <div class="bg-white rounded-xl p-3 border border-[var(--yg-border)] hover:border-orange-300 transition-colors group">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="text-xs font-bold text-[var(--yg-text)] line-clamp-2" x-text="citation.title"></h4>
                            <button @click="copyCitation(citation.id)" class="text-[10px] text-[var(--yg-text-dim)] hover:text-orange-600 transition-colors" title="Copy citation">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <a :href="citation.url" target="_blank" class="text-[10px] text-blue-600 hover:text-blue-800 truncate block mb-2" x-text="citation.url"></a>
                        <div class="flex items-center justify-between text-[9px] text-[var(--yg-text-dim)]">
                            <span x-show="citation.author" x-text="citation.author"></span>
                            <span x-show="citation.visit_count > 1" class="flex items-center gap-1">
                                <i class="fas fa-eye text-[8px]"></i>
                                <span x-text="citation.visit_count"></span>
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Stats footer --}}
            <div x-show="citationStats.total_sources > 0" class="p-3 border-t border-[var(--yg-border)] bg-white/50 text-[10px] text-[var(--yg-text-dim)]">
                <div class="flex items-center justify-between">
                    <span><span x-text="citationStats.total_sources"></span> sources</span>
                    <span><span x-text="citationStats.total_visits"></span> visits</span>
                </div>
            </div>
        </div>

        {{-- SIDE PANEL: Knowledge Graph --}}
        <div x-show="showKnowledgeGraph"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[450px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-gradient-to-r from-indigo-50 to-purple-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-project-diagram text-indigo-600 text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">Knowledge Graph</span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="refreshKnowledgeGraph()" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs" title="Refresh">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                    </button>
                    <button @click="showKnowledgeGraph = false" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Search entities --}}
            <div class="p-3 border-b border-[var(--yg-border)] bg-white/30">
                <input type="text"
                       x-model="entitySearchQuery"
                       @input.debounce.300ms="searchEntities()"
                       placeholder="Search entities..."
                       class="w-full bg-white border border-[var(--yg-border)] rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- Graph visualization or entity list --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Graph view (placeholder for D3.js) --}}
                <div x-show="!entitySearchQuery" class="h-full flex flex-col">
                    <div id="knowledge-graph-viz" class="flex-1 bg-white m-3 rounded-xl border border-[var(--yg-border)] flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-project-diagram text-4xl text-indigo-300 mb-3"></i>
                            <p class="text-xs text-[var(--yg-text-dim)]">Loading graph...</p>
                            <p class="text-[10px] text-[var(--yg-text-dim)] mt-1" x-text="graphData.total_entities + ' entities, ' + graphData.total_relationships + ' connections'"></p>
                        </div>
                    </div>

                    {{-- Entity stats --}}
                    <div class="px-4 pb-3 grid grid-cols-2 gap-2">
                        <div class="bg-white rounded-xl p-2 border border-[var(--yg-border)] text-center">
                            <div class="text-lg font-bold text-indigo-600" x-text="graphData.total_entities"></div>
                            <div class="text-[10px] text-[var(--yg-text-dim)]">Entities</div>
                        </div>
                        <div class="bg-white rounded-xl p-2 border border-[var(--yg-border)] text-center">
                            <div class="text-lg font-bold text-purple-600" x-text="graphData.total_relationships"></div>
                            <div class="text-[10px] text-[var(--yg-text-dim)]">Connections</div>
                        </div>
                    </div>
                </div>

                {{-- Search results --}}
                <div x-show="entitySearchQuery" class="p-3 space-y-2">
                    <template x-for="entity in entitySearchResults" :key="entity.name">
                        <div class="bg-white rounded-xl p-3 border border-[var(--yg-border)] hover:border-indigo-300 transition-colors cursor-pointer"
                             @click="viewEntityDetails(entity.name)">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-xs font-bold text-[var(--yg-text)]" x-text="entity.name"></h4>
                                <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700" x-text="entity.type"></span>
                            </div>
                            <div class="text-[10px] text-[var(--yg-text-dim)]">
                                <span x-text="entity.mentions + ' mentions'"></span>
                                <span class="mx-1">•</span>
                                <a :href="entity.first_seen_url" target="_blank" class="text-blue-600 hover:text-blue-800 truncate inline-block max-w-[200px]" x-text="entity.first_seen_url"></a>
                            </div>
                        </div>
                    </template>
                    <div x-show="entitySearchResults.length === 0 && entitySearchQuery" class="text-center py-8 text-xs text-[var(--yg-text-dim)]">
                        No entities found matching "<span x-text="entitySearchQuery"></span>"
                    </div>
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
        <span x-show="shieldsEnabled" class="flex items-center gap-1.5 text-green-600">
            <i class="fas fa-shield-alt text-[9px]"></i>
            <span x-show="shieldsStats.adsBlocked > 0"><span x-text="shieldsStats.adsBlocked"></span> ads</span>
            <span x-show="shieldsStats.trackersBlocked > 0"><span x-text="shieldsStats.trackersBlocked"></span> trackers</span>
            <span x-show="shieldsStats.httpsUpgraded" class="text-blue-600">HTTPS ↑</span>
        </span>
        <span x-show="loading" x-cloak>
            <i class="fas fa-circle-notch fa-spin mr-1 text-[9px]"></i>Loading...
        </span>
        <span>v2.0</span>
    </div>
</div>

{{-- ── Apple WebKit / iOS Detection ── --}}
<script>
    // Detect iOS and set global flags for conditional rendering
    (function() {
        const ua = navigator.userAgent || '';
        const isIOS = /iPad|iPhone|iPod/.test(ua) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isSafari = /Safari/.test(ua) && !/Chrome|CriOS|Edg|OPR|FxiOS/.test(ua);
        const isPWA = window.matchMedia('(display-mode: standalone)').matches;
        const isIPhoneX = isIOS && window.screen.height >= 812;

        // Store as CSS custom properties and data attrs for conditional CSS
        document.documentElement.style.setProperty('--is-ios', isIOS ? '1' : '0');
        document.documentElement.setAttribute('data-is-ios', isIOS ? 'true' : 'false');
        document.documentElement.setAttribute('data-is-safari', isSafari ? 'true' : 'false');
        document.documentElement.setAttribute('data-is-pwa', isPWA ? 'true' : 'false');
        document.documentElement.setAttribute('data-is-iphonex', isIPhoneX ? 'true' : 'false');

        // Prevent iOS double-tap zoom on buttons/links (aggressive approach)
        if (isIOS) {
            document.addEventListener('touchstart', function() {}, { passive: true });

            // Fix iOS keyboard avoidance for fixed bottom inputs
            document.addEventListener('focusin', function(e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    const viewport = document.querySelector('meta[name="viewport"]');
                    if (viewport && isPWA) {
                        // In PWA mode, prevent viewport shift when keyboard opens
                        document.body.style.position = 'relative';
                    }
                }
            });

            document.addEventListener('focusout', function() {
                if (isPWA) {
                    document.body.style.position = '';
                }
            });

            // Block iOS overscroll rubber-banding within the app shell
            document.addEventListener('touchmove', function(e) {
                // Only prevent if at the edge of scroll container
                const target = e.target.closest('.ios-scroll, .overflow-y-auto, .overflow-auto');
                if (!target) {
                    e.preventDefault();
                }
            }, { passive: false });
        }

        // Expose to Alpine
        window.__ygIOS = { isIOS, isSafari, isPWA, isIPhoneX };
    })();
</script>

{{-- ── Alpine State ── --}}
<script>
    function browserState() {
        return {
            // Browser state
            urlInput: '{{ $currentUrl }}',
            currentUrl: '{{ $currentUrl }}',
            canGoBack: false,
            canGoForward: false,
            loading: false,
            focused: false,
            isBrowsing: {{ $isBrowsing ? 'true' : 'false' }},
            pageTitle: '{{ $pageTitle ?? '' }}',

            // iOS info
            isIOS: window.__ygIOS?.isIOS ?? false,
            isSafari: window.__ygIOS?.isSafari ?? false,
            isPWA: window.__ygIOS?.isPWA ?? false,
            isIPhoneX: window.__ygIOS?.isIPhoneX ?? false,

            // Panels
            showAgent: false,
            showApps: false,

            // Agent state
            agentInput: '',
            agentRunning: false,
            agentConfigured: false,
            agentMessages: [],

            // Tab management
            tabs: [],
            activeTabId: null,

            // Shields state (Brave-style ad/tracker blocking)
            shieldsEnabled: true,
            speedReaderEnabled: false,
            shieldsStats: {
                adsBlocked: 0,
                trackersBlocked: 0,
                scriptsBlocked: 0,
                httpsUpgraded: false,
                fingerprintingBlocked: 0,
            },

            // Deep Research state
            showResearch: false,
            researchQuery: '',
            researchRunning: false,
            researchStarted: false,
            researchComplete: false,
            researchStatus: '',
            researchProgress: 0,
            researchReport: null,
            researchElapsed: 0,
            researchMaxPages: 8,

            // Citations state
            showCitations: false,
            citations: [],
            citationCount: 0,
            citationStyle: 'apa',
            citationStats: { total_sources: 0, total_visits: 0, contexts_used: 0 },

            // Knowledge Graph state
            showKnowledgeGraph: false,
            graphData: { nodes: [], links: [], total_entities: 0, total_relationships: 0 },
            entitySearchQuery: '',
            entitySearchResults: [],

            // Visual Summaries state
            showVisualSummaries: false,
            visualizations: [],
            visualGenerating: false,

            // Password Manager state
            showPasswords: false,
            savedPasswords: [],
            passwordCount: 0,
            currentSiteCredentials: null,
            newPasswordUsername: '',
            newPasswordPassword: '',

            init() {
                // Load history from session storage
                try {
                    const saved = sessionStorage.getItem('yg_browser_history');
                    if (saved) {
                        const data = JSON.parse(saved);
                        const activeTab = this.tabs.find(t => t.id === this.activeTabId);
                        if (activeTab && data.tabHistory) {
                            activeTab.history = data.tabHistory;
                            activeTab.historyIndex = data.tabIndex ?? 0;
                        }
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

                // Initialize with one tab if not browsing
                if (this.tabs.length === 0) {
                    this.newTab();
                }

                // ── iOS Gesture Navigation ──────────────────────────────────
                // Swipe from left edge → go back
                // Swipe from right edge → go forward
                if (this.isIOS) {
                    this.initIOSGestures();
                }

                // Listen for browser back/forward buttons (popstate)
                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.url) {
                        const tab = this.tabs.find(t => t.id === this.activeTabId);
                        if (tab) {
                            tab.historyIndex++;
                            tab.history.push(e.state.url);
                            this.currentUrl = e.state.url;
                            this.urlInput = e.state.url;
                            this.isBrowsing = true;
                            this.loading = true;
                            this.updateNavButtons();
                            if (this.$refs.browserFrame) {
                                const iframeUrl = new URL('/browse', window.location.origin);
                                iframeUrl.searchParams.set('url', e.state.url);
                                if (!this.shieldsEnabled) iframeUrl.searchParams.set('shields', '0');
                                if (this.speedReaderEnabled) iframeUrl.searchParams.set('speed_reader', '1');
                                this.$refs.browserFrame.src = iframeUrl.toString();
                            }
                        }
                    } else {
                        this.currentUrl = '';
                        this.urlInput = '';
                        this.isBrowsing = false;
                        this.updateNavButtons();
                    }
                });
            },

            newTab(url = '') {
                const id = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                const tab = {
                    id,
                    url: url || '',
                    title: url ? this.extractTitleFromUrl(url) : 'New Tab',
                    shieldsEnabled: true,
                    speedReaderEnabled: false,
                    history: [],
                    historyIndex: -1,
                    loading: false,
                };
                this.tabs.push(tab);
                this.switchTab(id);
                if (url) {
                    this.updateTabUrl(id, url);
                }
            },

            closeTab(id) {
                const idx = this.tabs.findIndex(t => t.id === id);
                if (idx === -1) return;
                this.tabs.splice(idx, 1);
                if (this.tabs.length === 0) {
                    this.newTab();
                    return;
                }
                if (this.activeTabId === id) {
                    // Switch to nearest tab
                    const newIdx = Math.min(idx, this.tabs.length - 1);
                    this.switchTab(this.tabs[newIdx].id);
                }
            },

            switchTab(id) {
                // Save current tab state before switching
                if (this.activeTabId) {
                    const currentTab = this.tabs.find(t => t.id === this.activeTabId);
                    if (currentTab) {
                        currentTab.url = this.currentUrl;
                        currentTab.loading = this.loading;
                        currentTab.shieldsEnabled = this.shieldsEnabled;
                        currentTab.speedReaderEnabled = this.speedReaderEnabled;
                    }
                }
                // Initialize with one tab if not browsing
                if (this.tabs.length === 0) {
                    this.newTab();
                }

                // Load citations on init
                this.loadCitations();

                // Load knowledge graph data
                this.refreshKnowledgeGraph();

                // Load saved passwords
                this.loadPasswords();

                // ── iOS Gesture Navigation ──────────────────────────────────


                const tab = this.tabs.find(t => t.id === id);
                if (!tab) return;

                this.activeTabId = id;
                this.currentUrl = tab.url;
                this.urlInput = tab.url;
                this.isBrowsing = !!tab.url;
                this.loading = tab.loading;
                this.shieldsEnabled = tab.shieldsEnabled;
                this.speedReaderEnabled = tab.speedReaderEnabled;

                // Update history from tab state
                this.history = tab.history;
                this.historyIndex = tab.historyIndex;
                this.updateNavButtons();

                // Reload iframe if browsing
                if (tab.url && this.$refs.browserFrame) {
                    const iframeUrl = new URL('/browse', window.location.origin);
                    iframeUrl.searchParams.set('url', tab.url);
                    if (!this.shieldsEnabled) iframeUrl.searchParams.set('shields', '0');
                    if (this.speedReaderEnabled) iframeUrl.searchParams.set('speed_reader', '1');
                    this.$refs.browserFrame.src = iframeUrl.toString();
                } else if (!tab.url && this.$refs.browserFrame) {
                    this.$refs.browserFrame.src = 'about:blank';
                }

                // Update URL bar without navigation
                if (history.pushState) {
                    const newUrl = '/' + (tab.url ? '?url=' + encodeURIComponent(tab.url) : '');
                    history.replaceState({ url: tab.url }, '', newUrl);
                }
            },

            updateTabUrl(id, url) {
                const tab = this.tabs.find(t => t.id === id);
                if (!tab) return;
                tab.url = url;
                tab.title = this.extractTitleFromUrl(url);

                // Push to tab's own history
                if (tab.url && tab.url !== url) {
                    tab.history = tab.history.slice(0, tab.historyIndex + 1);
                    tab.history.push(url);
                    tab.historyIndex = tab.history.length - 1;
                } else if (!tab.url) {
                    tab.history.push(url);
                    tab.historyIndex = tab.history.length - 1;
                }
            },

            extractTitleFromUrl(url) {
                if (!url) return 'New Tab';
                try {
                    const u = new URL(url);
                    return u.hostname.replace('www.', '');
                } catch {
                    return url.substring(0, 30);
                }
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

                // Update current tab's URL
                this.updateTabUrl(this.activeTabId, url);

                // Push to active tab's history
                const tab = this.tabs.find(t => t.id === this.activeTabId);
                if (tab) {
                    if (tab.historyIndex < tab.history.length - 1) {
                        tab.history = tab.history.slice(0, tab.historyIndex + 1);
                    }
                    tab.history.push(url);
                    tab.historyIndex = tab.history.length - 1;
                }

                this.currentUrl = url;
                this.urlInput = url;
                this.isBrowsing = true;
                this.loading = true;

                // Update browser URL without reload
                if (history.pushState) {
                    const newUrl = '/?url=' + encodeURIComponent(url);
                    history.pushState({ url: url }, '', newUrl);
                }

                // Update active tab title
                const activeTab = this.tabs.find(t => t.id === this.activeTabId);
                if (activeTab) activeTab.title = this.extractTitleFromUrl(url);

                this.updateNavButtons();
                this.saveHistory();
            },

            goBack() {
                const tab = this.tabs.find(t => t.id === this.activeTabId);
                if (!tab || tab.historyIndex <= 0) return;
                tab.historyIndex--;
                const url = tab.history[tab.historyIndex];
                this.currentUrl = url;
                this.urlInput = url;
                this.loading = true;
                if (history.pushState) {
                    history.pushState({ url }, '', '/browser?url=' + encodeURIComponent(url));
                }
                this.updateNavButtons();
                this.saveHistory();
                if (this.$refs.browserFrame) {
                    const iframeUrl = new URL('/browse', window.location.origin);
                    iframeUrl.searchParams.set('url', url);
                    if (!this.shieldsEnabled) iframeUrl.searchParams.set('shields', '0');
                    if (this.speedReaderEnabled) iframeUrl.searchParams.set('speed_reader', '1');
                    this.$refs.browserFrame.src = iframeUrl.toString();
                }
            },

            goForward() {
                const tab = this.tabs.find(t => t.id === this.activeTabId);
                if (!tab || tab.historyIndex >= tab.history.length - 1) return;
                tab.historyIndex++;
                const url = tab.history[tab.historyIndex];
                this.currentUrl = url;
                this.urlInput = url;
                this.loading = true;
                if (history.pushState) {
                    history.pushState({ url }, '', '/?url=' + encodeURIComponent(url));
                }
                this.updateNavButtons();
                this.saveHistory();
                if (this.$refs.browserFrame) {
                    const iframeUrl = new URL('/browse', window.location.origin);
                    iframeUrl.searchParams.set('url', url);
                    if (!this.shieldsEnabled) iframeUrl.searchParams.set('shields', '0');
                    if (this.speedReaderEnabled) iframeUrl.searchParams.set('speed_reader', '1');
                    this.$refs.browserFrame.src = iframeUrl.toString();
                }
            },

            refresh() {
                if (!this.currentUrl) return;
                this.loading = true;
                if (this.$refs.browserFrame) {
                    const iframeUrl = new URL('/browse', window.location.origin);
                    iframeUrl.searchParams.set('url', this.currentUrl);
                    if (!this.shieldsEnabled) iframeUrl.searchParams.set('shields', '0');
                    if (this.speedReaderEnabled) iframeUrl.searchParams.set('speed_reader', '1');
                    this.$refs.browserFrame.src = iframeUrl.toString();
                }
            },

            onFrameLoad() {
                this.loading = false;
            },

            updateNavButtons() {
                const tab = this.tabs.find(t => t.id === this.activeTabId);
                if (tab) {
                    this.canGoBack = tab.historyIndex > 0;
                    this.canGoForward = tab.historyIndex < tab.history.length - 1;
                } else {
                    this.canGoBack = false;
                    this.canGoForward = false;
                }
            },

            saveHistory() {
                try {
                    const tab = this.tabs.find(t => t.id === this.activeTabId);
                    if (tab) {
                        sessionStorage.setItem('yg_browser_history', JSON.stringify({
                            tabHistory: tab.history,
                            tabIndex: tab.historyIndex,
                        }));
                    }
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
            },

            toggleShields() {
                this.shieldsEnabled = !this.shieldsEnabled;
                // Reload iframe with shields flag
                if (this.currentUrl && this.$refs.browserFrame) {
                    this.loading = true;
                    const url = new URL('/browse', window.location.origin);
                    url.searchParams.set('url', this.currentUrl);
                    url.searchParams.set('shields', this.shieldsEnabled ? '1' : '0');
                    if (this.speedReaderEnabled) url.searchParams.set('speed_reader', '1');
                    this.$refs.browserFrame.src = url.toString();
                }
            },

            toggleSpeedReader() {
                this.speedReaderEnabled = !this.speedReaderEnabled;
                if (this.currentUrl && this.$refs.browserFrame) {
                    this.loading = true;
                    const url = new URL('/browse', window.location.origin);
                    url.searchParams.set('url', this.currentUrl);
                    if (this.shieldsEnabled) url.searchParams.set('shields', '1');
                    url.searchParams.set('speed_reader', this.speedReaderEnabled ? '1' : '0');
                    this.$refs.browserFrame.src = url.toString();
                }
            },

            // ── iOS Gesture Navigation ───────────────────────────────────────
            initIOSGestures() {
                const edgeWidth = 40; // px from edge to start gesture
                const minSwipe = 80;  // min distance to trigger action
                let startX = 0;
                let startY = 0;
                let phase = 'idle'; // idle | tracking
                const indicator = document.getElementById('gesture-indicator');

                document.addEventListener('touchstart', (e) => {
                    if (!this.isBrowsing) return;
                    const x = e.touches[0].clientX;
                    const y = e.touches[0].clientY;
                    if (x < edgeWidth || x > window.innerWidth - edgeWidth) {
                        startX = x;
                        startY = y;
                        phase = 'tracking';
                        if (indicator) {
                            indicator.style.opacity = '1';
                            indicator.style.left = (x < edgeWidth ? '0' : 'auto');
                            indicator.style.right = (x > window.innerWidth - edgeWidth ? '0' : 'auto');
                        }
                    }
                }, { passive: true });

                document.addEventListener('touchmove', (e) => {
                    if (phase !== 'tracking') return;
                    const dx = e.touches[0].clientX - startX;
                    const dy = e.touches[0].clientY - startY;
                    // Update indicator progress
                    if (indicator) {
                        const progress = Math.min(Math.abs(dx) / minSwipe, 1);
                        const dir = startX < edgeWidth ? 'left' : 'right';
                        indicator.querySelector('.gesture-progress').style.transform =
                            dir === 'left' ? `translateX(${progress * 40}px)` : `translateX(-${progress * 40}px)`;
                    }
                    // Cancel if vertical scroll is dominant
                    if (Math.abs(dy) > Math.abs(dx) * 1.5) {
                        phase = 'idle';
                        if (indicator) indicator.style.opacity = '0';
                    }
                }, { passive: true });

                document.addEventListener('touchend', (e) => {
                    if (phase !== 'tracking') return;
                    const dx = e.changedTouches[0].clientX - startX;
                    phase = 'idle';
                    if (indicator) indicator.style.opacity = '0';

                    if (Math.abs(dx) < minSwipe) return;

                    if (startX < edgeWidth && dx > minSwipe && this.canGoBack) {
                        // Swipe right from left edge → go back
                        this.showGestureFeedback('←', '#2563eb');
                        this.goBack();
                    } else if (startX > window.innerWidth - edgeWidth && dx < -minSwipe && this.canGoForward) {
                        // Swipe left from right edge → go forward
                        this.showGestureFeedback('→', '#7c3aed');
                        this.goForward();
                    }
                }, { passive: true });
            },

            showGestureFeedback(icon, color) {
                const fb = document.createElement('div');
                fb.style.cssText = `
                    position:fixed;top:50%;transform:translateY(-50%);
                    z-index:99999;font-size:48px;font-weight:900;
                    color:${color};opacity:0;transition:opacity 0.15s;
                    pointer-events:none;width:80px;height:80px;
                    display:flex;align-items:center;justify-content:center;
                    border-radius:50%;background:rgba(255,255,255,0.9);
                    box-shadow:0 4px 20px rgba(0,0,0,0.2);
                `;
                fb.textContent = icon;
                document.body.appendChild(fb);
                requestAnimationFrame(() => { fb.style.opacity = '1'; });
                setTimeout(() => {
                    fb.style.opacity = '0';
                    setTimeout(() => fb.remove(), 150);
                }, 400);
            },

            // ── Deep Research Methods ───────────────────────────────────────────

            startDeepResearch() {
                if (!this.researchQuery.trim() || this.researchRunning) return;

                this.researchRunning = true;
                this.researchStarted = true;
                this.researchComplete = false;
                this.researchProgress = 0;
                this.researchStatus = 'Searching for sources...';

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                fetch('/api/research/deep', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        query: this.researchQuery,
                        max_pages: parseInt(this.researchMaxPages),
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    this.researchRunning = false;

                    if (data.success) {
                        this.researchComplete = true;
                        this.researchReport = data.report;
                        this.researchElapsed = data.elapsed_seconds;

                        // Update citations panel
                        if (data.citations && data.citations.length > 0) {
                            this.loadCitations();
                        }
                    } else {
                        alert('Research failed: ' + (data.error || 'Unknown error'));
                        this.researchStarted = false;
                    }
                })
                .catch(err => {
                    this.researchRunning = false;
                    this.researchStarted = false;
                    alert('Research failed. Please try again.');
                });
            },

            // ── Citations Methods ───────────────────────────────────────────────

            loadCitations() {
                fetch('/api/citations/recent?limit=20')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.citations = data.citations;
                            this.citationCount = data.citations.length;
                            this.citationStats = data.stats || { total_sources: 0, total_visits: 0, contexts_used: 0 };
                        }
                    })
                    .catch(err => console.error('Failed to load citations:', err));
            },

            exportCitations() {
                fetch(`/api/citations/recent?export=1&style=${this.citationStyle}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Copy to clipboard
                            navigator.clipboard.writeText(data.citations).then(() => {
                                alert('Citations copied to clipboard in ' + this.citationStyle.toUpperCase() + ' format!');
                            });
                        }
                    })
                    .catch(err => alert('Export failed'));
            },

            copyCitation(citationId) {
                fetch('/api/citations/format', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        citation_id: citationId,
                        style: this.citationStyle,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        navigator.clipboard.writeText(data.citation).then(() => {
                            // Show brief success indicator
                        });
                    }
                })
                .catch(err => console.error('Failed to copy citation'));
            },

            clearCitationsList() {
                if (!confirm('Clear all citations?')) return;

                fetch('/api/citations/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.citations = [];
                        this.citationCount = 0;
                        this.citationStats = { total_sources: 0, total_visits: 0, contexts_used: 0 };
                    }
                });
            },

            // ── Knowledge Graph Methods ─────────────────────────────────────────

            refreshKnowledgeGraph() {
                fetch('/api/knowledge-graph?limit=50')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.graphData = data.graph;
                            // TODO: Render D3.js visualization here
                            this.renderKnowledgeGraph();
                        }
                    })
                    .catch(err => console.error('Failed to load knowledge graph'));
            },

            searchEntities() {
                if (!this.entitySearchQuery.trim()) {
                    this.entitySearchResults = [];
                    return;
                }

                fetch(`/api/knowledge-graph/search?q=${encodeURIComponent(this.entitySearchQuery)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.entitySearchResults = data.entities;
                        }
                    })
                    .catch(err => console.error('Entity search failed'));
            },

            viewEntityDetails(entityName) {
                fetch(`/api/knowledge-graph/connections?entity=${encodeURIComponent(entityName)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.entity) {
                            // Show entity details modal or expand in place
                            alert(`Entity: ${entityName}\nType: ${data.entity.type}\nMentions: ${data.entity.mentions}\nConnections: ${data.connections.length}`);
                        }
                    })
                    .catch(err => console.error('Failed to get entity details'));
            },

            renderKnowledgeGraph() {
                // Placeholder for D3.js force-directed graph rendering
                // This would create an interactive network visualization
                const container = document.getElementById('knowledge-graph-viz');
                if (!container) return;

                // For now, just update the stats
                // In production, integrate D3.js here for full visualization
            },

            // ── Visual Summaries Methods ────────────────────────────────────────

            generateVisualSummary() {
                if (this.visualGenerating || !this.currentUrl) return;

                this.visualGenerating = true;
                this.visualizations = [];

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                // Fetch current page HTML via proxy
                fetch(`/browse?url=${encodeURIComponent(this.currentUrl)}`)
                    .then(r => r.text())
                    .then(html => {
                        // Send to visual summary API
                        return fetch('/api/visual-summary/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                html: html,
                                url: this.currentUrl,
                            }),
                        });
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.visualGenerating = false;

                        if (data.success && data.visualizations.length > 0) {
                            this.visualizations = data.visualizations;
                        } else {
                            alert('No visualizable data found on this page.');
                        }
                    })
                    .catch(err => {
                        this.visualGenerating = false;
                        alert('Failed to generate visual summaries.');
                    });
            },

            // ── Password Manager Methods ────────────────────────────────────────

            loadPasswords() {
                fetch('/api/passwords/list')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.savedPasswords = data.passwords;
                            this.passwordCount = data.count;

                            // Check if current site has saved credentials
                            if (this.currentUrl) {
                                this.checkCurrentSiteCredentials();
                            }
                        }
                    })
                    .catch(err => console.error('Failed to load passwords'));
            },

            checkCurrentSiteCredentials() {
                fetch(`/api/passwords/get?url=${encodeURIComponent(this.currentUrl)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.credentials) {
                            this.currentSiteCredentials = data.credentials;
                        } else {
                            this.currentSiteCredentials = null;
                        }
                    })
                    .catch(err => console.error('Failed to check credentials'));
            },

            fillCredentials() {
                if (!this.currentSiteCredentials) return;

                // Inject credentials into iframe or send message to page
                const iframe = document.getElementById('browser-iframe');
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.postMessage({
                        type: 'yg_autofill',
                        username: this.currentSiteCredentials.username,
                        // Don't send password directly - use secure channel in production
                        action: 'fill_form',
                    }, '*');

                    alert('Auto-fill triggered. You may need to manually enter password for security.');
                }
            },

            saveCurrentPassword() {
                if (!this.newPasswordUsername || !this.newPasswordPassword || !this.currentUrl) return;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                fetch('/api/passwords/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        url: this.currentUrl,
                        username: this.newPasswordUsername,
                        password: this.newPasswordPassword,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Password saved successfully!');
                        this.newPasswordUsername = '';
                        this.newPasswordPassword = '';
                        this.loadPasswords();
                    } else {
                        alert('Failed to save password: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => alert('Failed to save password'));
            },

            deletePassword(id) {
                if (!confirm('Delete this saved password?')) return;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                fetch(`/api/passwords/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.loadPasswords();
                    } else {
                        alert('Failed to delete password');
                    }
                })
                .catch(err => alert('Failed to delete password'));
            },

            formatDate(dateString) {
                if (!dateString) return 'Never';
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

                if (diffDays === 0) return 'Today';
                if (diffDays === 1) return 'Yesterday';
                if (diffDays < 7) return `${diffDays} days ago`;
                if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

                return date.toLocaleDateString();
            },

        }
    }
</script>
@endsection
