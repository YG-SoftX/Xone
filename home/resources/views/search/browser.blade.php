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
<div x-data="browserState()" x-init="init()" class="flex flex-col h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">

    {{-- ── TAB BAR (Modern Floating Design) ── --}}
    <div class="flex-shrink-0 px-2 pt-2 pb-1 overflow-x-auto scrollbar-hide"
         x-show="tabs.length > 0"
         x-cloak>
        <div class="flex items-center gap-1 max-w-7xl mx-auto">
            {{-- Tab strip --}}
            <template x-for="tab in tabs" :key="tab.id">
                <button @click="switchTab(tab.id)"
                        class="group relative flex items-center gap-2 px-4 py-2 rounded-t-xl text-xs font-medium transition-all duration-200 flex-shrink-0 max-w-[200px] backdrop-blur-sm"
                        :class="tab.id === activeTabId
                            ? 'bg-white/95 shadow-lg border border-b-0 border-gray-200 text-gray-800 translate-y-0.5'
                            : 'bg-white/40 hover:bg-white/70 text-gray-600 hover:text-gray-800 border border-transparent hover:border-gray-200'">
                    {{-- Favicon/initial --}}
                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold flex-shrink-0 transition-all"
                          :style="tab.id === activeTabId 
                              ? 'background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary)); color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1)' 
                              : 'background: #e5e7eb; color: #6b7280'"
                          x-text="tab.title ? tab.title.charAt(0).toUpperCase() : '?'">
                    </span>
                    {{-- Tab title --}}
                    <span class="truncate flex-1" x-text="tab.title || 'New Tab'"></span>
                    {{-- Close button --}}
                    <button @click.stop="closeTab(tab.id)"
                            class="w-5 h-5 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all opacity-0 group-hover:opacity-100 flex-shrink-0">
                        <i class="fas fa-times text-[9px]"></i>
                    </button>
                </button>
            </template>
            
            {{-- New tab button --}}
            <button @click="newTab()"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-500 hover:bg-white hover:text-blue-600 hover:shadow-md transition-all flex-shrink-0 border border-dashed border-gray-300 hover:border-blue-400 ml-1"
                    title="New tab (Ctrl+T)">
                <i class="fas fa-plus text-xs"></i>
            </button>
        </div>
    </div>

    {{-- ── TOP: Modern Navigation Bar ── --}}
    <header class="relative z-50 flex-shrink-0 bg-white/80 backdrop-blur-2xl border-b border-gray-200/50 shadow-sm">
        <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5">

            {{-- Logo / Home button --}}
            <a href="{{ route('browser.home') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all duration-200 flex-shrink-0 group"
               title="Home">
                <div class="transform group-hover:scale-105 transition-transform">
                    {!! $headerLogo !!}
                </div>
            </a>

            {{-- Navigation buttons --}}
            <div class="flex items-center gap-1 bg-gray-50/50 rounded-xl p-1">
                <button @click="goBack()"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all disabled:opacity-30 disabled:hover:bg-transparent"
                        :disabled="!canGoBack"
                        title="Back (Alt+←)">
                    <i class="fas fa-chevron-left text-sm"></i>
                </button>
                <button @click="goForward()"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all disabled:opacity-30 disabled:hover:bg-transparent"
                        :disabled="!canGoForward"
                        title="Forward (Alt+→)">
                    <i class="fas fa-chevron-right text-sm"></i>
                </button>
                <button @click="refresh()"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        title="Refresh (F5)">
                    <i class="fas fa-redo text-sm" :class="{ 'fa-spin': loading }"></i>
                </button>
                <button @click="navigateTo('')"
                        class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        title="Home">
                    <i class="fas fa-home text-sm"></i>
                </button>
            </div>

            {{-- Omnibox — Modern URL bar + search --}}
            <form @submit.prevent="navigateTo(urlInput)"
                  class="flex-1 min-w-0 mx-1 sm:mx-2">
                <div class="relative group">
                    <div class="omnibox-glass rounded-2xl flex items-center gap-2 px-4 py-2.5 transition-all duration-300 border border-gray-200/80 hover:border-blue-300/80 hover:shadow-md"
                         :class="{ 'ring-2 ring-blue-500/20 border-blue-400 shadow-lg': focused }">
                        {{-- Security indicator --}}
                        <template x-if="currentUrl && currentUrl.startsWith('https://')">
                            <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-green-50 border border-green-200">
                                <i class="fas fa-lock text-xs text-green-600"></i>
                                <span class="text-[10px] font-semibold text-green-700 hidden sm:inline">Secure</span>
                            </div>
                        </template>
                        <template x-if="currentUrl && !currentUrl.startsWith('https://')">
                            <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-orange-50 border border-orange-200">
                                <i class="fas fa-shield-alt text-xs text-orange-600"></i>
                                <span class="text-[10px] font-semibold text-orange-700 hidden sm:inline">Not Secure</span>
                            </div>
                        </template>
                        <template x-if="!currentUrl">
                            <i class="fas fa-search text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        </template>
                        
                        <input type="text"
                               x-model="urlInput"
                               x-ref="omnibox"
                               @focus="focused = true"
                               @blur="focused = false"
                               @keydown.escape="urlInput = currentUrl || ''; $refs.omnibox.blur()"
                               class="flex-1 bg-transparent border-none outline-none text-sm placeholder:text-gray-400 focus:ring-0 font-medium min-w-0"
                               placeholder="Search the web or enter URL..."
                               autocomplete="off"
                               spellcheck="false">
                        
                        {{-- Clear button --}}
                        <button type="button"
                                x-show="urlInput.length > 0"
                                @click="urlInput = ''; $refs.omnibox.focus()"
                                class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600 flex-shrink-0">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                        
                        {{-- Go button --}}
                        <button type="submit"
                                class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 transition-all text-white font-semibold text-xs flex-shrink-0 shadow-sm hover:shadow-md transform hover:scale-105"
                                title="Go">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <span class="hidden sm:inline">Go</span>
                        </button>
                    </div>
                </div>
            </form>

            {{-- Right actions --}}
            <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                {{-- Ecosystem Apps toggle --}}
                <button @click="showApps = !showApps"
                        class="w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium text-gray-600 hover:bg-white hover:shadow-md transition-all"
                        :class="{ 'bg-white shadow-md text-blue-600': showApps }"
                        title="YG Apps">
                    <i class="fas fa-th text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Apps</span>
                </button>

                {{-- Deep Research Mode toggle --}}
                <button @click="showResearch = !showResearch"
                        class="w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium transition-all"
                        :class="showResearch ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-lg' : 'text-gray-600 hover:bg-white hover:shadow-md'"
                        title="Deep Research Mode">
                    <i class="fas fa-microscope text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Research</span>
                </button>

                {{-- Citations Panel toggle --}}
                <button @click="showCitations = !showCitations"
                        class="relative w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium text-gray-600 hover:bg-white hover:shadow-md transition-all"
                        :class="{ 'bg-white shadow-md text-orange-600': showCitations }"
                        title="Citations">
                    <i class="fas fa-quote-right text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Cite</span>
                    <span x-show="citationCount > 0"
                          class="absolute -top-1 -right-1 bg-gradient-to-r from-orange-500 to-red-500 text-white text-[9px] font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg animate-pulse"
                          x-text="citationCount"></span>
                </button>

                {{-- Knowledge Graph toggle --}}
                <button @click="showKnowledgeGraph = !showKnowledgeGraph"
                        class="w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium text-gray-600 hover:bg-white hover:shadow-md transition-all"
                        :class="{ 'bg-white shadow-md text-indigo-600': showKnowledgeGraph }"
                        title="Knowledge Graph">
                    <i class="fas fa-project-diagram text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Graph</span>
                </button>

                {{-- Visual Summaries toggle --}}
                <button @click="showVisualSummaries = !showVisualSummaries"
                        class="w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium text-gray-600 hover:bg-white hover:shadow-md transition-all"
                        :class="{ 'bg-white shadow-md text-teal-600': showVisualSummaries }"
                        title="Visual Summaries (Charts & Tables)">
                    <i class="fas fa-chart-bar text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Charts</span>
                </button>

                {{-- Password Manager toggle --}}
                <button @click="showPasswords = !showPasswords"
                        class="relative w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-medium text-gray-600 hover:bg-white hover:shadow-md transition-all"
                        :class="{ 'bg-white shadow-md text-emerald-600': showPasswords }"
                        title="Password Manager">
                    <i class="fas fa-key text-sm"></i>
                    <span class="hidden sm:inline font-semibold">Keys</span>
                    <span x-show="passwordCount > 0"
                          class="absolute -top-1 -right-1 bg-gradient-to-r from-emerald-500 to-green-500 text-white text-[9px] font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-lg"
                          x-text="passwordCount"></span>
                </button>

                {{-- AI Agent toggle (controlled by master admin) --}}
                @if($agentEnabled)
                <button @click="showAgent = !showAgent; if (showAgent) checkAgentStatus()"
                        class="w-9 h-9 sm:w-auto sm:px-3 rounded-xl flex items-center gap-2 text-xs font-semibold text-white bg-gradient-to-r from-violet-600 via-purple-600 to-blue-600 hover:from-violet-700 hover:via-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105"
                        :class="{ 'ring-2 ring-purple-500/50 ring-offset-2': showAgent }"
                        title="AI Agent">
                    <i class="fas fa-sparkles text-sm"></i>
                    <span class="hidden sm:inline">AI</span>
                </button>
                @endif

                {{-- Auth --}}
                @php
                    $isLoggedIn = auth()->check() || session('sso_user');
                    $currentUser = auth()->user() ?? session('sso_user');
                @endphp

                @if($isLoggedIn)
                <div class="flex items-center gap-2">
                    @if($currentUser)
                    <span class="hidden lg:inline text-xs font-medium text-gray-700">
                        {{ is_array($currentUser) ? ($currentUser['name'] ?? 'User') : ($currentUser->name ?? 'User') }}
                    </span>
                    @endif
                    <a href="{{ route('sso.logout') }}"
                       class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-gray-600 hover:bg-red-50 hover:text-red-600 hover:shadow-md transition-all text-xs"
                       title="Sign out">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </a>
                </div>
                @else
                <a href="{{ route('sso.initiate') }}"
                   class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                    <i class="fas fa-key text-sm"></i>
                    <span class="hidden sm:inline">Login</span>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- ── MIDDLE: Content Area ── --}}
    <div class="flex-1 flex overflow-hidden relative">

        {{-- MAIN CONTENT: Web Page (iframe) or Home Grid --}}
        <div class="flex-1 flex flex-col min-w-0 bg-white/50 backdrop-blur-sm" :class="{ 'mr-[360px]': showAgent }">
            <template x-if="isBrowsing && currentUrl">
                {{-- Loading bar with animation --}}
                <div x-show="loading" x-cloak
                     class="h-1 bg-gray-100 overflow-hidden flex-shrink-0">
                    <div class="h-full w-1/2 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 animate-loading-bar"></div>
                </div>
                
                {{-- Iframe with modern container --}}
                <div class="flex-1 w-full relative">
                    <iframe x-ref="browserFrame"
                            :src="'/browse?url=' + encodeURIComponent(currentUrl)"
                            class="w-full h-full border-0"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                            @load="onFrameLoad()"
                            allow="fullscreen"
                            referrerpolicy="no-referrer">
                    </iframe>
                    
                    {{-- Page info overlay --}}
                    <div x-show="loading" 
                         class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center animate-pulse">
                                <i class="fas fa-globe text-3xl text-white"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Loading page...</p>
                            <p class="text-xs text-gray-500 mt-1 truncate max-w-xs" x-text="currentUrl"></p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Home state — Modern start page with enhanced design --}}
            <template x-if="!isBrowsing || !currentUrl">
                <div class="flex-1 flex flex-col items-center justify-center px-4 py-12 overflow-y-auto">
                    <div class="w-full max-w-5xl">
                        {{-- Hero Section --}}
                        <div class="text-center mb-12 fade-in-up">
                            <div class="inline-block mb-6">
                                <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 p-0.5 shadow-2xl">
                                    <div class="w-full h-full rounded-3xl bg-white flex items-center justify-center">
                                        <i class="fas fa-compass text-5xl bg-gradient-to-br from-blue-500 to-purple-600 bg-clip-text text-transparent"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <h1 class="text-6xl sm:text-7xl font-black tracking-tight mb-4">
                                <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    {{ $browserName }}
                                </span>
                            </h1>
                            
                            <p class="text-lg text-gray-600 font-medium max-w-2xl mx-auto leading-relaxed">
                                The intelligent browser that thinks for you. Browse, research, and automate with AI-powered assistance.
                            </p>
                            
                            {{-- Quick search bar --}}
                            <div class="mt-8 max-w-2xl mx-auto">
                                <form @submit.prevent="navigateTo(quickSearch)" class="relative">
                                    <div class="relative group">
                                        <input type="text"
                                               x-model="quickSearch"
                                               placeholder="Search anything or enter URL..."
                                               class="w-full px-6 py-4 pr-32 rounded-2xl bg-white border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none text-base shadow-xl hover:shadow-2xl transition-all duration-300 placeholder:text-gray-400"
                                               autocomplete="off">
                                        <button type="submit"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 px-6 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                                            <i class="fas fa-search mr-2"></i>
                                            Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Quick Links (from Master Admin panel) --}}
                        <div class="mb-12 fade-in-up fade-in-delay-1">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-bolt text-white text-lg"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-800">Quick Access</h2>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                                @foreach($quickLinks as $link)
                                <button @click="navigateTo('{{ $link['url'] }}')"
                                        class="group relative flex flex-col items-center gap-3 p-5 rounded-2xl bg-white border-2 border-gray-100 hover:border-transparent transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                                        style="--tile-color: {{ $link['color'] }}">
                                    <div class="icon-wrapper w-14 h-14 rounded-2xl flex items-center justify-center text-2xl text-white transition-all duration-300 group-hover:scale-110 shadow-lg"
                                         style="background: linear-gradient(135deg, {{ $link['color'] }}, {{ $link['color'] }}dd)">
                                        <i class="{{ $link['icon'] }}"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 group-hover:text-gray-900 transition-colors text-center">
                                        {{ $link['name'] }}
                                    </span>
                                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                                         style="background: linear-gradient(135deg, {{ $link['color'] }}10, transparent)"></div>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- YG Ecosystem Grid --}}
                        <div class="fade-in-up fade-in-delay-2">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-layer-group text-white text-lg"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-800">YG Ecosystem</h2>
                                <span class="ml-auto text-sm text-gray-500">{{ count($activeApps) }} apps</span>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                                @foreach($activeApps as $app)
                                <a href="{{ $app['url'] }}" target="_blank"
                                   class="group relative flex flex-col items-center gap-3 p-5 rounded-2xl bg-white border-2 border-gray-100 hover:border-transparent transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                                   style="--tile-color: {{ $app['icon_color'] }}">
                                    <div class="icon-wrapper w-14 h-14 rounded-2xl flex items-center justify-center text-2xl text-white transition-all duration-300 group-hover:scale-110 shadow-lg"
                                         style="background: linear-gradient(135deg, {{ $app['icon_color'] }}, {{ $app['icon_color'] }}cc)">
                                        <i class="{{ $app['icon'] }}"></i>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-sm font-bold text-gray-700 group-hover:text-gray-900 transition-colors">
                                            {{ $app['name'] }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-0.5 line-clamp-1">
                                            {{ $app['description'] }}
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                                         style="background: linear-gradient(135deg, {{ $app['icon_color'] }}10, transparent)"></div>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        
                        {{-- Footer tips --}}
                        <div class="mt-12 text-center">
                            <div class="inline-flex items-center gap-6 text-xs text-gray-500 bg-white/50 backdrop-blur-sm px-6 py-3 rounded-xl border border-gray-200">
                                <span><i class="fas fa-keyboard mr-1.5 text-blue-500"></i><kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px]">Ctrl+T</kbd> New Tab</span>
                                <span><i class="fas fa-keyboard mr-1.5 text-purple-500"></i><kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px]">Ctrl+L</kbd> Focus URL</span>
                                <span><i class="fas fa-keyboard mr-1.5 text-green-500"></i><kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px]">F5</kbd> Refresh</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- SIDE PANEL: AI Agent --}}
        <div x-show="showAgent"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             class="w-[360px] flex-shrink-0 border-l border-gray-200/50 bg-white/95 backdrop-blur-xl flex flex-col overflow-hidden shadow-2xl">
            
            {{-- Panel Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200/50 bg-gradient-to-r from-violet-50 via-purple-50 to-blue-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 via-purple-600 to-blue-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-sparkles text-white text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-gray-800 block">YG Agent</span>
                        <span class="text-[10px] text-gray-500">AI-Powered Assistant</span>
                    </div>
                </div>
                <button @click="showAgent = false"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:bg-white hover:text-red-500 transition-all">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Agent chat area --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="agentMessages">
                {{-- Welcome / not configured state --}}
                <div x-show="agentMessages.length <= 1 && !agentConfigured" class="text-center py-10">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-3xl bg-gradient-to-br from-violet-100 via-purple-100 to-blue-100 flex items-center justify-center shadow-inner">
                        <i class="fas fa-robot text-3xl bg-gradient-to-br from-violet-600 to-purple-600 bg-clip-text text-transparent"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-2">Welcome to YG Agent</h3>
                    <p class="text-xs text-gray-600 leading-relaxed mb-4 max-w-[260px] mx-auto">
                        Your AI-powered browsing companion. Automate tasks, research topics, and get intelligent assistance.
                    </p>
                    <a href="/agent/settings" target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 text-white text-xs font-bold hover:from-violet-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-key text-[10px]"></i> 
                        Configure Agent
                    </a>
                </div>

                {{-- Agent conversation messages --}}
                <template x-for="msg in agentMessages" :key="msg.id">
                    <div class="animate-fade-in">
                        {{-- User message --}}
                        <div x-show="msg.role === 'user'" class="flex gap-3 justify-end mb-4">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 text-xs bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-md">
                                <span x-text="msg.content" class="font-medium"></span>
                            </div>
                        </div>

                        {{-- Agent response (final) --}}
                        <div x-show="msg.role === 'assistant'" class="flex gap-3 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fas fa-sparkles text-xs text-purple-600"></i>
                            </div>
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 text-xs bg-white border border-gray-200 text-gray-700 shadow-sm">
                                <span x-text="msg.content" class="leading-relaxed"></span>
                                
                                {{-- Show steps if present --}}
                                <div x-show="msg.steps && msg.steps.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="text-[10px] text-gray-600 space-y-2">
                                        <template x-for="step in msg.steps" :key="step.step">
                                            <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50">
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                                                     :class="step.type === 'tool' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'">
                                                    <i class="fas" :class="step.type === 'tool' ? 'fa-cog text-[9px]' : 'fa-check text-[9px]'"></i>
                                                </div>
                                                <span x-text="step.tool || 'Complete'" class="font-medium"></span>
                                                <span x-show="step.result" class="ml-auto text-green-600 font-bold">✓</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Agent thinking indicator --}}
                        <div x-show="msg.role === 'thinking'" class="flex gap-3 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center flex-shrink-0 shadow-sm animate-pulse">
                                <i class="fas fa-sparkles text-xs text-purple-600"></i>
                            </div>
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 text-xs bg-white border border-gray-200 text-gray-600 italic shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-bounce" style="animation-delay: 0s"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-bounce" style="animation-delay: 0.1s"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-bounce" style="animation-delay: 0.2s"></div>
                                    </div>
                                    <span x-text="msg.content"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Agent input --}}
            <div class="p-4 border-t border-gray-200/50 bg-gradient-to-r from-gray-50 to-white">
                <form @submit.prevent="sendAgentMessage()" class="space-y-3">
                    <div class="relative">
                        <input type="text"
                               x-model="agentInput"
                               placeholder="What should I do? (e.g., 'Find iPhone 16 prices')"
                               :disabled="agentRunning"
                               class="w-full bg-white border-2 border-gray-200 rounded-xl px-4 py-3 pr-12 text-xs outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 disabled:opacity-50 transition-all shadow-sm">
                        <button type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-gradient-to-r from-violet-600 to-purple-600 text-white flex items-center justify-center hover:from-violet-700 hover:to-purple-700 transition-all text-xs flex-shrink-0 disabled:opacity-50 shadow-md hover:shadow-lg transform hover:scale-105"
                                :disabled="!agentInput.trim() || agentRunning">
                            <i class="fas" :class="agentRunning ? 'fa-circle-notch fa-spin text-[10px]' : 'fa-paper-plane text-[10px]'"></i>
                        </button>
                    </div>
                    
                    <div class="flex items-center justify-between text-[10px]">
                        <a href="/agent/settings" target="_blank" class="flex items-center gap-1.5 text-gray-500 hover:text-purple-600 transition-colors">
                            <i class="fas fa-cog text-[9px]"></i>
                            Settings
                        </a>
                        <div class="flex items-center gap-2">
                            <span x-show="!agentConfigured" class="flex items-center gap-1.5 text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-[9px]"></i>
                                Not configured
                            </span>
                            <span x-show="agentConfigured" class="flex items-center gap-1.5 text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                Ready
                            </span>
                        </div>
                    </div>
                </form>
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

            {{-- Knowledge graph visualization area --}}
            <div class="flex-1 overflow-y-auto p-3 space-y-3" x-ref="graphContainer">
                <div x-show="!graphData || !graphData.nodes || graphData.nodes.length === 0" class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-project-diagram text-2xl text-indigo-600"></i>
                    </div>
                    <h4 class="text-sm font-bold text-[var(--yg-text)] mb-1">Knowledge Graph</h4>
                    <p class="text-xs text-[var(--yg-text-dim)]">Connect related concepts<br>across your browsing history.</p>
                </div>
                
                {{-- Entity search results --}}
                <div x-show="entitySearchResults.length > 0" class="space-y-2 p-2 max-h-60 overflow-y-auto">
                    <h5 class="text-xs font-bold text-[var(--yg-text)] px-2">Search Results</h5>
                    <template x-for="entity in entitySearchResults" :key="entity.name">
                        <div @click="viewEntityDetails(entity.name)"
                             class="flex items-center gap-3 p-2 rounded-lg hover:bg-indigo-50 cursor-pointer transition-colors"
                             :title="entity.description || entity.type">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                <i class="fas fa-atom"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-[var(--yg-text)] truncate" x-text="entity.name"></div>
                                <div class="text-[10px] text-[var(--yg-text-dim)] truncate" x-text="entity.type"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- SIDE PANEL: Visual Summaries --}}
        <div x-show="showVisualSummaries"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[450px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-gradient-to-r from-teal-50 to-cyan-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-bar text-teal-600 text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">Visual Summaries</span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="generateVisualSummary()" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs" title="Generate">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                    </button>
                    <button @click="showVisualSummaries = false" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-4" x-ref="visualContainer">
                <div x-show="visualizations.length === 0" class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-100 flex items-center justify-center">
                        <i class="fas fa-chart-network text-2xl text-teal-600"></i>
                    </div>
                    <h4 class="text-sm font-bold text-[var(--yg-text)] mb-1">Visual Summaries</h4>
                    <p class="text-xs text-[var(--yg-text-dim)]">Generate charts and tables<br>from current page content.</p>
                </div>

                <template x-for="viz in visualizations" :key="viz.id">
                    <div class="bg-white rounded-xl p-3 border border-[var(--yg-border)]">
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="text-xs font-bold text-[var(--yg-text)]" x-text="viz.title"></h5>
                            <span class="text-[10px] text-[var(--yg-text-dim)]" x-text="viz.type"></span>
                        </div>
                        <div class="text-[10px] text-[var(--yg-text-dim)] mb-3" x-text="viz.description"></div>
                        <div class="border border-[var(--yg-border)] rounded-lg p-2 bg-gray-50 min-h-[150px] flex items-center justify-center" x-html="viz.html"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- SIDE PANEL: Password Manager --}}
        <div x-show="showPasswords"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-4 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             class="w-[400px] flex-shrink-0 border-l border-[var(--yg-border)] bg-white/50 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--yg-border)] bg-gradient-to-r from-emerald-50 to-green-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-key text-emerald-600 text-sm"></i>
                    <span class="text-sm font-bold text-[var(--yg-text)]">Password Manager</span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="loadPasswords()" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs" title="Refresh">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                    </button>
                    <button @click="showPasswords = false" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-white transition-colors text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="p-3 border-b border-[var(--yg-border)] bg-white/30 space-y-3">
                <div class="flex items-center gap-2" x-show="currentSiteCredentials">
                    <div class="text-[10px] text-[var(--yg-text-dim)]">Current site:</div>
                    <button @click="fillCredentials()" class="px-3 py-1 rounded-lg bg-emerald-500 text-white text-[10px] font-bold hover:bg-emerald-600 transition-colors">
                        Fill Credentials
                    </button>
                </div>
                <div class="border-t border-[var(--yg-border)] pt-3 space-y-3">
                    <h5 class="text-xs font-bold text-[var(--yg-text)]">Save New Password</h5>
                    <div class="space-y-2">
                        <input type="text"
                               x-model="newPasswordUsername"
                               placeholder="Username"
                               class="w-full bg-white border border-[var(--yg-border)] rounded-lg px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <input type="password"
                               x-model="newPasswordPassword"
                               placeholder="Password"
                               class="w-full bg-white border border-[var(--yg-border)] rounded-lg px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <button @click="saveCurrentPassword()"
                                :disabled="!newPasswordUsername || !newPasswordPassword"
                                class="w-full py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-green-500 text-white text-xs font-bold hover:opacity-90 transition-all disabled:opacity-50">
                            Save Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2" x-ref="passwordContainer">
                <div x-show="savedPasswords.length === 0" class="text-center py-8">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-lock text-emerald-600"></i>
                    </div>
                    <p class="text-xs text-[var(--yg-text-dim)]">No saved passwords yet.</p>
                </div>

                <template x-for="pwd in savedPasswords" :key="pwd.id">
                    <div class="bg-white rounded-xl p-3 border border-[var(--yg-border)] hover:border-emerald-300 transition-colors group">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="text-xs font-bold text-[var(--yg-text)] line-clamp-1" x-text="pwd.username"></h4>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="fillCredentialsFor(pwd)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-[10px] text-[var(--yg-text-dim)] hover:bg-emerald-50 hover:text-emerald-600 transition-colors"
                                        title="Fill">
                                    <i class="fas fa-fill-drip"></i>
                                </button>
                                <button @click="deletePassword(pwd.id)"
                                        class="w-6 h-6 rounded flex items-center justify-center text-[10px] text-[var(--yg-text-dim)] hover:bg-red-50 hover:text-red-500 transition-colors"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <a :href="pwd.url" target="_blank" class="text-[10px] text-blue-600 hover:text-blue-800 truncate block mb-2" x-text="pwd.url"></a>
                        <div class="flex items-center justify-between text-[9px] text-[var(--yg-text-dim)]">
                            <span x-text="'Saved: ' + formatDate(pwd.created_at)"></span>
                            <span x-text="'Visits: ' + pwd.visit_count"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    // Browser state management with Alpine.js
    document.addEventListener('alpine:init', () => {
        Alpine.data('browserState', () => ({
            // Core state
            currentUrl: @json($currentUrl),
            urlInput: @json($currentUrl ?: $searchEngine),
            pageTitle: @json($isBrowsing ? ($pageTitle ?? 'Browsing...') : null),
            loading: false,
            focused: false,
            canGoBack: false,
            canGoForward: false,

            // Tab management
            tabs: [],
            activeTabId: null,
            nextTabId: 1,

            // Panel states
            showApps: false,
            showResearch: false,
            showCitations: false,
            showKnowledgeGraph: false,
            showVisualSummaries: false,
            showPasswords: false,
            showAgent: false,

            // Research state
            researchQuery: '',
            researchRunning: false,
            researchStarted: false,
            researchComplete: false,
            researchStatus: '',
            researchProgress: 0,
            researchReport: null,
            researchElapsed: 0,
            researchMaxPages: 8,

            // Citation state
            citations: [],
            citationCount: 0,
            citationStats: { total_sources: 0, total_visits: 0, contexts_used: 0 },
            citationStyle: 'apa',

            // Knowledge graph state
            graphData: null,
            entitySearchQuery: '',
            entitySearchResults: [],

            // Visual summaries state
            visualizations: [],
            visualGenerating: false,

            // Password manager state
            savedPasswords: [],
            passwordCount: 0,
            newPasswordUsername: '',
            newPasswordPassword: '',
            currentSiteCredentials: null,

            // Agent state
            agentMessages: [{ role: 'system', content: 'YG Agent ready.' }],
            agentInput: '',
            agentRunning: false,
            agentConfigured: false,

            // Quick search
            quickSearch: '',

            init() {
                // Initialize with current URL as first tab if there's one
                if (this.currentUrl) {
                    this.addTab(this.currentUrl, this.pageTitle || 'New Tab');
                    this.activeTabId = this.tabs[0].id;
                } else {
                    // If no URL, create a blank tab
                    this.addTab('', 'Home');
                    this.activeTabId = this.tabs[0].id;
                }

                // Load persisted data
                this.loadCitations();
                this.loadPasswords();
            },

            // ── Tab Management ──────────────────────────────────────────────────

            addTab(url, title = 'New Tab') {
                const tab = {
                    id: this.nextTabId++,
                    url: url,
                    title: title,
                    lastVisited: new Date(),
                };
                this.tabs.push(tab);
                this.switchTab(tab.id);
                return tab;
            },

            switchTab(tabId) {
                const tabIndex = this.tabs.findIndex(tab => tab.id === tabId);
                if (tabIndex !== -1) {
                    this.activeTabId = tabId;
                    const activeTab = this.tabs[tabIndex];
                    this.currentUrl = activeTab.url;
                    this.urlInput = activeTab.url || this.searchEngine;
                    this.pageTitle = activeTab.title;
                }
            },

            closeTab(tabId) {
                if (this.tabs.length <= 1) return;

                const tabIndex = this.tabs.findIndex(tab => tab.id === tabId);
                if (tabIndex !== -1) {
                    this.tabs.splice(tabIndex, 1);

                    // Switch to another tab if closing the active one
                    if (tabId === this.activeTabId) {
                        const newIndex = Math.min(tabIndex, this.tabs.length - 1);
                        if (this.tabs[newIndex]) {
                            this.switchTab(this.tabs[newIndex].id);
                        }
                    }
                }
            },

            newTab() {
                const newTab = this.addTab('', 'New Tab');
                this.switchTab(newTab.id);
                setTimeout(() => {
                    this.$refs.omnibox?.focus();
                    this.$refs.omnibox?.select();
                }, 100);
            },

            // ── Navigation Methods ──────────────────────────────────────────────

            navigateTo(url) {
                if (!url.trim()) return;

                // Add protocol if missing
                if (!url.includes('://')) {
                    if (url.includes('.') && !url.includes(' ')) {
                        url = 'https://' + url;
                    } else {
                        url = '{{ $searchEngine }}' + encodeURIComponent(url);
                    }
                }

                // Update current tab or create new one if none exists
                if (this.activeTabId) {
                    const activeTab = this.tabs.find(tab => tab.id === this.activeTabId);
                    if (activeTab) {
                        activeTab.url = url;
                        activeTab.lastVisited = new Date();
                    }
                } else {
                    const newTab = this.addTab(url, 'New Tab');
                    this.activeTabId = newTab.id;
                }

                this.currentUrl = url;
                this.urlInput = url;
                this.loading = true;
                this.updatePageTitle();

                // Navigate via iframe proxy
                window.location.href = `/search/browser?url=${encodeURIComponent(url)}`;
            },

            goBack() {
                // Since we're using iframe proxy, we rely on browser history stored in session
                // For now, we'll just trigger a navigation event that the backend can handle
                if (this.currentUrl) {
                    // In a real implementation, we'd have a way to go back through history
                    // For now, we'll just trigger a page reload to the previous URL
                    window.history.back();
                }
            },

            goForward() {
                window.history.forward();
            },

            refresh() {
                if (this.currentUrl) {
                    // Force reload of iframe content
                    this.loading = true;
                    setTimeout(() => {
                        this.loading = false;
                        this.$nextTick(() => {
                            const frame = this.$refs.browserFrame;
                            if (frame) {
                                frame.src = frame.src;
                            }
                        });
                    }, 300);
                }
            },

            onFrameLoad() {
                this.loading = false;
                this.canGoBack = true; // Simplified - in reality would check actual history
                this.canGoForward = true;
                this.updatePageTitle();
            },

            updatePageTitle() {
                // Attempt to update tab title based on loaded content
                // This would typically involve messaging with the iframe content
                if (this.activeTabId) {
                    const activeTab = this.tabs.find(tab => tab.id === this.activeTabId);
                    if (activeTab) {
                        activeTab.title = this.extractDomain(this.currentUrl);
                    }
                }
            },

            extractDomain(url) {
                try {
                    return new URL(url).hostname.replace('www.', '');
                } catch (e) {
                    return url.substring(0, 30) + (url.length > 30 ? '...' : '');
                }
            },

            // ── Deep Research Methods ───────────────────────────────────────────

            async startDeepResearch() {
                if (!this.researchQuery.trim() || this.researchRunning) return;

                this.researchRunning = true;
                this.researchStarted = true;
                this.researchComplete = false;
                this.researchProgress = 0;
                this.researchStatus = 'Initializing research...';

                try {
                    const startTime = Date.now();
                    const response = await fetch('/api/deep-research', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            query: this.researchQuery,
                            max_pages: parseInt(this.researchMaxPages),
                        }),
                    });

                    if (response.ok) {
                        const data = await response.json();
                        
                        if (data.success) {
                            this.researchReport = data.report;
                            this.researchComplete = true;
                            this.researchElapsed = ((Date.now() - startTime) / 1000).toFixed(1);
                            
                            // Add sources to citations
                            if (data.report?.sources) {
                                for (const source of data.report.sources) {
                                    // Add to citations if not already present
                                    if (!this.citations.some(c => c.url === source.url)) {
                                        this.citations.unshift({
                                            id: Date.now() + Math.random(),
                                            title: source.title,
                                            url: source.url,
                                            visit_count: 1,
                                        });
                                    }
                                }
                                this.citationCount = this.citations.length;
                            }
                        } else {
                            alert('Research failed: ' + (data.error || 'Unknown error'));
                        }
                    } else {
                        alert('Research failed: Server error');
                    }
                } catch (err) {
                    console.error('Research failed:', err);
                    alert('Research failed. Please try again.');
                } finally {
                    this.researchRunning = false;
                    this.researchStatus = '';
                }
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