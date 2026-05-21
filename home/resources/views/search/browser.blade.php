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
                @if(auth()->check())
                <a href="{{ route('sso.logout') }}"
                   class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-gray-600 hover:bg-red-50 hover:text-red-600 hover:shadow-md transition-all text-xs"
                   title="Sign out">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                </a>
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

    {{-- ── BOTTOM: Modern Status Bar ── --}}
    <div class="flex-shrink-0 bg-white/90 backdrop-blur-xl border-t border-gray-200/50 px-4 py-2 flex items-center gap-4 text-[10px] text-gray-600 shadow-sm">
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span x-text="currentUrl || '{{ $browserName }}'" class="truncate font-medium"></span>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Shields status --}}
            <span x-show="shieldsEnabled" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
                <i class="fas fa-shield-alt text-[9px]"></i>
                <span x-show="shieldsStats.adsBlocked > 0" class="font-semibold"><span x-text="shieldsStats.adsBlocked"></span> ads</span>
                <span x-show="shieldsStats.trackersBlocked > 0" class="font-semibold"><span x-text="shieldsStats.trackersBlocked"></span> trackers</span>
                <span x-show="shieldsStats.httpsUpgraded" class="text-blue-600 font-semibold">HTTPS ↑</span>
            </span>
            
            {{-- Loading indicator --}}
            <span x-show="loading" x-cloak class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-blue-700">
                <i class="fas fa-circle-notch fa-spin text-[9px]"></i>
                <span class="font-semibold">Loading...</span>
            </span>
            
            {{-- Version badge --}}
            <span class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 border border-gray-300 font-bold text-gray-700">
                v2.0
            </span>
        </div>
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
