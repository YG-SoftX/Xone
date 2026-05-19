@extends('layouts.app')
@section('title', 'Search: ' . $query)

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs mb-6" style="color:#9b8e90">
    <a href="{{ route('knowledge.index') }}" class="hover:text-white transition-colors">Knowledge Base</a>
    <span>›</span>
    <span>Search: "{{ $query }}"</span>
</div>

{{-- Search again --}}
<div class="rounded-2xl p-6 mb-8" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
    <form action="{{ route('knowledge.search') }}" method="GET" class="max-w-lg">
        <div class="relative">
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="Search articles..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#ff003c]/50 transition-all"
                   style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#fff">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2" style="color:#9b8e90"></i>
        </div>
    </form>
</div>

{{-- Results --}}
<h3 class="text-sm font-semibold mb-4" style="color:#9b8e90">
    {{ $articles->total() }} result{{ $articles->total() !== 1 ? 's' : '' }} for "{{ $query }}"
</h3>

@if($articles->count() > 0)
    <div class="space-y-3 mb-8">
        @foreach($articles as $article)
            <a href="{{ route('knowledge.show', $article->slug) }}"
               class="flex items-start justify-between rounded-xl p-5 hover:bg-white/5 transition-colors"
               style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-medium uppercase tracking-wider" style="color:#fb7185">
                            {{ $article->categoryLabel() }}
                        </span>
                        @if($article->type === 'faq')
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full" style="background:rgba(250,204,21,0.15);color:#facc15">FAQ</span>
                        @endif
                    </div>
                    <h4 class="font-semibold">{{ $article->title }}</h4>
                    <p class="text-xs mt-1" style="color:#9b8e90">{{ $article->excerpt(150) }}</p>
                </div>
                <i class="fas fa-chevron-right flex-shrink-0 mt-1 ml-4 text-xs" style="color:#4a4044"></i>
            </a>
        @endforeach
    </div>

    <div class="text-xs" style="color:#9b8e90">
        {{ $articles->links() }}
    </div>
@else
    <div class="rounded-2xl p-10 text-center" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-3xl mb-3">🔍</div>
        <p class="text-sm mb-1" style="color:#9b8e90">No results found for "{{ $query }}"</p>
        <p class="text-xs mb-4" style="color:#6b5f63">Try different keywords or browse categories above.</p>
        <a href="{{ route('tickets.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold"
           style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">
            <i class="fas fa-headset"></i> Contact Support
        </a>
    </div>
@endif
@endsection
