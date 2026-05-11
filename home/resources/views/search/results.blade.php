@extends('layouts.app')

@section('title', e($query) . ' - YGXONE Search')

@section('content')
<style>
    .results-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        display: flex;
        gap: 40px;
    }
    .main-results {
        flex: 1;
        min-width: 0;
    }
    .sidebar-wrapper {
        width: 320px;
        flex-shrink: 0;
    }
    @media (max-width: 1024px) {
        .results-container { flex-direction: column; }
        .sidebar-wrapper { width: 100%; order: -1; }
    }

    .results-header {
        position: sticky;
        top: 0;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--yg-border);
        padding: 15px 40px;
        z-index: 100;
        display: flex;
        align-items: center;
        gap: 30px;
    }
    .mini-logo { font-size: 24px; font-weight: 900; letter-spacing: -1px; color: #0f172a; text-decoration: none; }
    .mini-logo span { background: linear-gradient(135deg, #2563eb, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    
    .search-input-wrapper { flex: 1; max-width: 650px; position: relative; }
    .mini-search-bar { width: 100%; background: #ffffff; border: 1px solid #cbd5e1; padding: 12px 20px 12px 45px; border-radius: 24px; font-size: 14px; outline: none; transition: all 0.2s; font-family: inherit; }
    .mini-search-bar:focus { border-color: var(--yg-accent-blue); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1); }
    .mini-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }

    .result-card { background: #fff; padding: 24px; border-radius: 16px; border: 1px solid #f1f5f9; transition: all 0.2s; margin-bottom: 16px; }
    .result-card:hover { border-color: var(--yg-border); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
    .result-url { color: var(--text-dim); font-size: 12px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .result-title { color: var(--yg-accent-blue); font-size: 19px; font-weight: 600; text-decoration: none; display: block; margin-bottom: 8px; }
    .result-title:hover { text-decoration: underline; }
    .result-snippet { color: var(--text-main); font-size: 14px; line-height: 1.6; opacity: 0.8; }
    
    .ai-sidebar { background: #f8fafc; border: 1px solid var(--yg-border); border-radius: 20px; padding: 24px; position: sticky; top: 100px; }
    .eco-link { display: flex; align-items: center; gap: 15px; padding: 12px; border-radius: 12px; text-decoration: none; transition: all 0.2s; margin-bottom: 8px; }
    .eco-link:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .eco-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .eco-text .title { font-size: 14px; font-weight: 700; color: #0f172a; }
    .eco-text .desc { font-size: 11px; color: var(--text-dim); }

    .ai-answer-card { background: linear-gradient(135deg, #eff6ff 0%, #f5f3ff 100%); border: 1px solid #dbeafe; padding: 30px; border-radius: 24px; margin-bottom: 30px; }
    .ai-tag { display: flex; align-items: center; gap: 8px; color: var(--yg-accent-blue); font-weight: 800; font-size: 12px; text-transform: uppercase; margin-bottom: 15px; }
</style>

<div class="results-header" style="flex-direction: column; align-items: stretch; padding: 0;">
    <div style="display: flex; align-items: center; gap: 30px; padding: 12px 40px;">
        <a href="{{ route('search.home') }}" class="mini-logo">YGX<span>ONE</span></a>
        
        <div class="search-input-wrapper">
            <i class="fas fa-search mini-search-icon"></i>
            <form action="{{ route('search.index') }}" method="GET" style="display: flex; align-items: center; width: 100%;">
                <input type="text" name="q" value="{{ $query }}" class="mini-search-bar" placeholder="Ask Yuga AI...">
                <div style="position: absolute; right: 20px; display: flex; items-center gap: 15px; color: #64748b; font-size: 14px;">
                    <i class="fas fa-times cursor-pointer hover:text-gray-900" onclick="document.querySelector('.mini-search-bar').value=''"></i>
                    <div style="width: 1px; height: 20px; background: #e2e8f0;"></div>
                    <i class="fas fa-microphone cursor-pointer hover:text-blue-600"></i>
                    <i class="fas fa-camera cursor-pointer hover:text-blue-600"></i>
                </div>
            </form>
        </div>

        <div class="nav-links" style="display: flex; gap: 20px; margin-left: auto;">
            <a href="#" class="text-gray-500 hover:bg-gray-100 p-2 rounded-lg"><i class="fas fa-th"></i></a>
            @if(auth()->check())
                <a href="https://account.ygxone.com" class="profile-btn" style="background: #2563eb; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; items-center justify-center; font-size: 14px;">
                    {{ substr(auth()->user()->name ?? 'P', 0, 1) }}
                </a>
            @else
                <a href="https://account.ygxone.com/login" class="nav-link" style="background: #2563eb; color: #fff; padding: 8px 20px; border-radius: 20px; font-weight: bold; text-decoration: none; font-size: 13px;">Sign In</a>
            @endif
        </div>
    </div>

    {{-- Search Tabs --}}
    <div style="display: flex; gap: 25px; padding: 0 40px 0 160px; border-top: 1px solid #f1f5f9;">
        <a href="?q={{ urlencode($query) }}&type=ai" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ $type == 'ai' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-sparkles text-[10px]"></i> AI Mode
        </a>
        <a href="?q={{ urlencode($query) }}&type=all" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ ($type == 'all' || $type == 'web') ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-search text-[10px]"></i> All
        </a>
        <a href="?q={{ urlencode($query) }}&type=images" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ $type == 'images' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-image text-[10px]"></i> Images
        </a>
        <a href="?q={{ urlencode($query) }}&type=news" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ $type == 'news' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-newspaper text-[10px]"></i> News
        </a>
        <a href="?q={{ urlencode($query) }}&type=videos" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ $type == 'videos' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-play-circle text-[10px]"></i> Videos
        </a>
        <a href="?q={{ urlencode($query) }}&type=shopping" class="py-3 px-1 text-sm font-medium flex items-center gap-2 {{ $type == 'shopping' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600' }}">
            <i class="fas fa-shopping-bag text-[10px]"></i> Shopping
        </a>
        <div class="ml-auto flex items-center gap-4">
            <a href="#" class="py-3 text-sm font-medium text-gray-500 hover:text-blue-600">Tools</a>
        </div>
    </div>
</div>

<div class="results-container">
    <div class="main-results">
    
    <div class="max-w-6xl mx-auto px-4 py-8">
        @if($total_results > 0)
            <p class="text-sm text-gray-600 mb-6">About {{ number_format($total_results) }} results for "{{ e($query) }}"</p>
        @else
            <p class="text-sm text-gray-600 mb-6">No results found for "{{ e($query) }}"</p>
        @endif
        
        {{-- Rich AI Knowledge Card (Google Style) --}}
        @if(isset($results['ai_enhanced']) && $results['ai_enhanced'])
        <div class="mb-8">
            <div class="flex flex-col md:flex-row gap-8">
                {{-- Main AI Answer --}}
                <div class="flex-1">
                    <div class="ai-answer-card" style="background: transparent; border: none; padding: 0; margin-bottom: 20px;">
                        <div class="ai-tag" style="margin-bottom: 10px;">
                            <i class="fas fa-sparkles"></i>
                            Yuga AI Overview
                        </div>
                        <div style="font-size: 18px; line-height: 1.6; color: #1e293b; margin-bottom: 20px;">
                            {!! nl2br(e($results['ai_enhanced']['answer'])) !!}
                        </div>
                        
                        {{-- People Also Ask Accordion --}}
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">People also ask</h3>
                            <div class="space-y-0 border-b">
                                @php $questions = $results['suggestions']['ai'] ?? ['Who is ' . $query . '?', 'What is ' . $query . ' known for?', 'Latest news on ' . $query]; @endphp
                                @foreach(array_slice($questions, 0, 3) as $q)
                                <div class="border-t py-4 group cursor-pointer hover:bg-gray-50 transition-colors px-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-base font-medium text-gray-800">{{ $q }}</span>
                                        <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform group-hover:translate-y-0.5"></i>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Side Knowledge Panel (Desktop) --}}
                <div class="w-full md:w-[360px] flex-shrink-0">
                    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm sticky top-24">
                        <div class="p-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ e($query) }}</h2>
                            <p class="text-sm text-gray-500 mb-6">Subject Profile</p>
                            
                            <div class="space-y-5">
                                @php
                                    $facts = [];
                                    $lines = explode("\n", $results['ai_enhanced']['answer']);
                                    foreach($lines as $line) {
                                        if(str_contains($line, ':')) {
                                            $parts = explode(':', $line, 2);
                                            $facts[trim($parts[0])] = trim($parts[1]);
                                        }
                                    }
                                    if(empty($facts)) {
                                        $facts = ['Source' => 'Yuga Intelligence', 'Type' => 'AI Generated Profile'];
                                    }
                                @endphp
                                
                                @foreach($facts as $label => $value)
                                <div class="border-t pt-3">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                                    <p class="text-sm text-gray-800 mt-1 font-medium">{{ $value }}</p>
                                </div>
                                @endforeach
                                
                                <div class="pt-6 border-t mt-6">
                                    <h4 class="text-[11px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">YG Ecosystem</h4>
                                    <div class="space-y-2">
                                        <a href="https://mail.ygxone.com" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-xl transition-colors group">
                                            <div class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center text-xs">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-900">YG Mail</div>
                                                <div class="text-[10px] text-gray-400">Sovereign Email</div>
                                            </div>
                                        </a>
                                        <a href="https://drive.ygxone.com" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-xl transition-colors group">
                                            <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center text-xs">
                                                <i class="fas fa-hard-drive"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-900">YG Drive</div>
                                                <div class="text-[10px] text-gray-400">Encrypted Storage</div>
                                            </div>
                                        </a>
                                        <a href="https://ai.ygxone.com" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-xl transition-colors group">
                                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-500 flex items-center justify-center text-xs">
                                                <i class="fas fa-brain"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-900">Yuga AI</div>
                                                <div class="text-[10px] text-gray-400">Intelligence</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div class="pt-6 border-t mt-6 text-[11px] text-gray-400 leading-relaxed italic">
                                    You are searching within the YGXONE Sovereign Intelligence Platform. Your data remains encrypted and sovereign.
                                </div>

                                <div class="pt-4 flex gap-4">
                                    <a href="#" class="text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition-colors border border-blue-100 flex-1 text-center text-xs font-bold">Follow</a>
                                    <a href="#" class="text-gray-600 hover:bg-gray-50 p-2 rounded-lg transition-colors border border-gray-200 flex-1 text-center text-xs font-bold">Share</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Ecosystem Results --}}
        @if(isset($results['ecosystem']) && count($results['ecosystem']) > 0)
        <div class="mb-10">
            <h3 class="text-lg font-medium text-gray-800 mb-4 pb-2 border-b border-gray-200">Ecosystem Results</h3>
            <div class="space-y-4">
                @foreach($results['ecosystem'] as $result)
                @php
                    $ecoUrl = (str_starts_with($result['url'] ?? '', 'http://') || str_starts_with($result['url'] ?? '', 'https://'))
                        ? $result['url'] : '#';
                    
                    $badgeColors = [
                        'mail' => 'bg-red-50 text-red-600 border-red-100',
                        'drive' => 'bg-green-50 text-green-600 border-green-100',
                        'docs' => 'bg-blue-50 text-blue-600 border-blue-100',
                        'contacts' => 'bg-yellow-50 text-yellow-600 border-yellow-100',
                    ];
                    $badgeClass = $badgeColors[$result['type']] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                @endphp
                <div class="bg-white p-5 rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="flex justify-between items-start mb-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-bold uppercase border {{ $badgeClass }}">
                            @if(isset($result['icon']))
                                <i class="fas fa-{{ e($result['icon']) }}"></i>
                            @endif
                            {{ ucfirst(e($result['type'])) }}
                        </span>
                        @if(isset($result['metadata']['date']))
                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($result['metadata']['date'])->diffForHumans() }}</span>
                        @endif
                    </div>
                    <a href="{{ e($ecoUrl) }}" class="block text-lg text-blue-700 hover:underline font-medium mb-1" rel="noopener noreferrer">
                        {{ e($result['title']) }}
                    </a>
                    <p class="text-sm text-gray-600 line-clamp-2">{{ strip_tags($result['snippet'] ?? '') }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Result Views Based on Type --}}
        @if($type == 'images')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($results['web'] as $result)
                <div class="group relative aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200 hover:shadow-lg transition-all">
                    @php 
                        // Try to find an image in the result or use a placeholder
                        $hasImage = isset($result['image']) || str_contains($result['url'] ?? '', '.jpg') || str_contains($result['url'] ?? '', '.png');
                    @endphp
                    <div class="w-full h-full flex items-center justify-center bg-gray-50 text-gray-300">
                        <i class="fas fa-image text-3xl"></i>
                    </div>
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors p-3 flex flex-col justify-end opacity-0 group-hover:opacity-100">
                        <a href="{{ e($result['url'] ?? '#') }}" target="_blank" class="text-white text-xs font-medium line-clamp-2 hover:underline">
                            {{ e($result['title'] ?? 'Image Result') }}
                        </a>
                        <span class="text-gray-300 text-[10px]">{{ e($result['domain'] ?? '') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @elseif($type == 'news')
            <div class="space-y-8">
                @foreach($results['web'] as $result)
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded uppercase">News</span>
                            <span class="text-xs text-gray-400">Recent</span>
                        </div>
                        <a href="{{ e($result['url'] ?? '#') }}" target="_blank" class="text-xl font-semibold text-blue-700 hover:underline block mb-1">
                            {{ e($result['title'] ?? '') }}
                        </a>
                        <div class="text-sm text-gray-500 mb-2 flex items-center gap-2">
                            <span>{{ e($result['domain'] ?? '') }}</span>
                            <span>•</span>
                            <span>{{ date('M d, Y') }}</span>
                        </div>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            {{ Str::limit(strip_tags($result['snippet'] ?? ''), 220) }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            {{-- Default Web Results (All) --}}
            <div class="space-y-4">
                @foreach($results['web'] as $result)
                <div class="result-card">
                    <div class="result-url">
                        @if(isset($result['favicon']))
                            <img src="{{ e($result['favicon']) }}" class="w-4 h-4 rounded-full" alt="">
                        @endif
                        <span>{{ e($result['domain'] ?? '') }}</span>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <a href="{{ e($result['url'] ?? '#') }}" target="_blank" class="result-title">
                                {{ e($result['title'] ?? '') }}
                            </a>
                            <p class="result-snippet">
                                {{ Str::limit(strip_tags($result['snippet'] ?? ''), 180) }}
                            </p>
                        </div>
                        @if(isset($result['image']) && $result['image'])
                        <div class="hidden md:block">
                            <img src="{{ e($result['image']) }}" class="w-24 h-24 object-cover rounded-lg border border-gray-100" alt="">
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        {{-- Related Searches (Professional Style) --}}
        @if(isset($suggestions) && !empty($suggestions))
        <div class="mt-12 mb-12 p-6 bg-white border border-gray-200 rounded-xl">
            <h3 class="text-base font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fas fa-search text-gray-400"></i> 
                People also search for
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                @foreach($suggestions as $source => $items)
                    @if(is_array($items))
                        @foreach(array_slice($items, 0, 4) as $item)
                        <a href="?q={{ urlencode($item) }}" class="flex items-center gap-3 py-2 px-3 hover:bg-gray-50 rounded-lg transition-colors group">
                            <i class="fas fa-search text-gray-300 group-hover:text-blue-500 text-[10px]"></i>
                            <span class="text-sm font-medium text-blue-600 hover:underline">{{ $item }}</span>
                        </a>
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Professional Minimal Pagination --}}
        @if(isset($pagination) && $pagination['last_page'] > 1)
        <div class="mt-20 mb-20 flex flex-col items-center">
            <div class="flex items-center gap-2 mb-4">
                @if($pagination['current_page'] > 1)
                <a href="?q={{ urlencode($query) }}&type={{ $type }}&page={{ $pagination['current_page'] - 1 }}&per_page={{ $pagination['per_page'] }}" 
                   class="px-4 py-2 text-sm font-bold text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-chevron-left text-xs"></i> Previous
                </a>
                @endif

                <div class="flex items-center gap-1">
                    @for($i = max(1, $pagination['current_page'] - 3); $i <= min($pagination['last_page'], $pagination['current_page'] + 3); $i++)
                        <a href="?q={{ urlencode($query) }}&type={{ $type }}&page={{ $i }}&per_page={{ $pagination['per_page'] }}" 
                           class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-bold transition-all duration-200 
                           {{ $i == $pagination['current_page'] ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-500 hover:bg-gray-100' }}">
                            {{ $i }}
                        </a>
                    @endfor
                </div>

                @if($pagination['current_page'] < $pagination['last_page'])
                <a href="?q={{ urlencode($query) }}&type={{ $type }}&page={{ $pagination['current_page'] + 1 }}&per_page={{ $pagination['per_page'] }}" 
                   class="px-4 py-2 text-sm font-bold text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center gap-2">
                    Next <i class="fas fa-chevron-right text-xs"></i>
                </a>
                @endif
            </div>
            
            <div class="text-xs text-gray-400 font-medium tracking-wide uppercase">
                YGXONE Intelligence Index — Page {{ $pagination['current_page'] }}
            </div>
        </div>
        @endif

        {{-- Location Footer --}}
        <div class="mt-24 pt-10 border-t border-gray-200 text-sm text-gray-500 pb-16">
            <div style="max-width: 650px;">
                <div class="flex flex-wrap items-center gap-x-8 gap-y-4 mb-6">
                    <span class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 bg-gray-400 rounded-full animate-pulse"></span>
                        <strong class="text-gray-700">United Kingdom</strong>
                    </span>
                    <span class="text-gray-400">PO4 0EJ, Southsea, Portsmouth — From your device</span>
                    <a href="#" class="text-blue-600 font-semibold hover:underline">Update location</a>
                </div>
                <div class="flex flex-wrap gap-6 font-medium">
                    <a href="#" class="hover:text-gray-900 transition-colors">Help</a>
                    <a href="#" class="hover:text-gray-900 transition-colors">Send feedback</a>
                    <a href="#" class="hover:text-gray-900 transition-colors">Privacy</a>
                    <a href="#" class="hover:text-gray-900 transition-colors">Terms</a>
                </div>
            </div>
        </div>
        
    </div>

    </div>
</div>
@endsection
