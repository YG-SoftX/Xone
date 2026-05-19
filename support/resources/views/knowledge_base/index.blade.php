@extends('layouts.app')
@section('title', 'Knowledge Base')

@section('content')
{{-- Hero search --}}
<div class="rounded-2xl p-8 mb-8 text-center"
     style="background:linear-gradient(135deg,rgba(155,27,48,0.3) 0%,rgba(255,0,60,0.05) 100%);border:1px solid rgba(255,0,60,0.15)">
    <h2 class="text-2xl font-bold mb-2">How can we help you?</h2>
    <p class="text-sm mb-6" style="color:#9b8e90">Search our knowledge base for guides, tutorials, and FAQs.</p>
    <form action="{{ route('knowledge.search') }}" method="GET" class="max-w-xl mx-auto">
        <div class="relative">
            <input type="text" name="q" placeholder="Search articles..."
                   class="w-full pl-10 pr-4 py-3 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#ff003c]/50 transition-all"
                   style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#fff">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2" style="color:#9b8e90"></i>
        </div>
    </form>
</div>

{{-- Categories grid --}}
<h3 class="text-sm font-semibold uppercase tracking-wider mb-4" style="color:#9b8e90">Browse by Category</h3>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-10">
    @foreach($categories as $key => $label)
        <a href="{{ route('knowledge.category', $key) }}"
           class="rounded-xl p-4 hover:bg-white/5 transition-colors"
           style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div class="text-lg mb-2">
                @switch($key)
                    @case('getting-started') 🚀 @break
                    @case('account') 💳 @break
                    @case('mail') 📧 @break
                    @case('xcel') 📊 @break
                    @case('docx') 📝 @break
                    @case('troubleshooting') 🔧 @break
                    @case('security') 🔒 @break
                    @default 📖
                @endswitch
            </div>
            <div class="text-sm font-semibold">{{ $label }}</div>
            <div class="text-xs mt-1" style="color:#9b8e90">{{ $articlesByCategory[$key]->count() }} articles</div>
        </a>
    @endforeach
</div>

{{-- Featured articles --}}
@if($featured->count() > 0)
    <h3 class="text-sm font-semibold uppercase tracking-wider mb-4" style="color:#9b8e90">Featured Articles</h3>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
        @foreach($featured as $article)
            <a href="{{ route('knowledge.show', $article->slug) }}"
               class="rounded-xl p-5 hover:bg-white/5 transition-colors"
               style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
                <div class="text-xs font-medium uppercase tracking-wider mb-2" style="color:#fb7185">
                    {{ $article->categoryLabel() }}
                </div>
                <h4 class="font-semibold mb-2">{{ $article->title }}</h4>
                <p class="text-xs" style="color:#9b8e90">{{ $article->excerpt(120) }}</p>
            </a>
        @endforeach
    </div>
@endif

{{-- FAQ accordion --}}
@if($faqs->count() > 0)
    <div id="faq"></div>
    <h3 class="text-sm font-semibold uppercase tracking-wider mb-4" style="color:#9b8e90">Frequently Asked Questions</h3>
    <div class="rounded-2xl overflow-hidden mb-8" style="border:1px solid rgba(255,255,255,0.06)">
        <div x-data="{ open: null }" class="divide-y" style="divide-color:rgba(255,255,255,0.04)">
            @foreach($faqs as $faq)
                <div>
                    <button @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-white/5 transition-all text-sm font-medium"
                            style="background:open === {{ $faq->id }} ? 'rgba(255,255,255,0.03)' : 'transparent'">
                        <span>{{ $faq->title }}</span>
                        <i class="fas fa-chevron-down transition-transform duration-200"
                           :class="open === {{ $faq->id }} ? 'rotate-180' : ''"
                           style="color:#9b8e90"></i>
                    </button>
                    <div x-show="open === {{ $faq->id }}"
                         x-collapse
                         class="px-6 pb-4 text-sm leading-relaxed"
                         style="color:#b0a4a8">
                        {!! nl2br(e($faq->content)) !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="text-center pt-4 pb-2">
    <p class="text-sm mb-3" style="color:#9b8e90">Can't find what you're looking for?</p>
    <a href="{{ route('tickets.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
       style="background:#ff003c;color:#fff">
        <i class="fas fa-headset"></i> Contact Support
    </a>
</div>
@endsection
