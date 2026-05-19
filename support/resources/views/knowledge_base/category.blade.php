@extends('layouts.app')
@section('title', $categories[$category] ?? $category)

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs mb-6" style="color:#9b8e90">
    <a href="{{ route('knowledge.index') }}" class="hover:text-white transition-colors">Knowledge Base</a>
    <span>›</span>
    <span>{{ $categories[$category] ?? $category }}</span>
</div>

{{-- Category header --}}
<div class="rounded-2xl p-6 mb-8"
     style="background:linear-gradient(135deg,rgba(155,27,48,0.2) 0%,rgba(255,0,60,0.02) 100%);border:1px solid rgba(255,0,60,0.1)">
    <div class="text-3xl mb-2">
        @switch($category)
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
    <h2 class="text-xl font-bold">{{ $categories[$category] ?? $category }}</h2>
    <p class="text-sm mt-1" style="color:#9b8e90">{{ $articles->total() }} articles available</p>
</div>

{{-- Articles list --}}
@if($articles->count() > 0)
    <div class="space-y-3 mb-8">
        @foreach($articles as $article)
            <a href="{{ route('knowledge.show', $article->slug) }}"
               class="flex items-start justify-between rounded-xl p-5 hover:bg-white/5 transition-colors"
               style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold mb-1">{{ $article->title }}</h4>
                    <p class="text-xs" style="color:#9b8e90">{{ $article->excerpt(150) }}</p>
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
        <div class="text-3xl mb-3">📭</div>
        <p class="text-sm mb-1" style="color:#9b8e90">No articles in this category yet.</p>
        <p class="text-xs" style="color:#6b5f63">We're working on adding more content. Check back soon!</p>
    </div>
@endif

{{-- Related FAQs --}}
@if($faqs->count() > 0)
    <h3 class="text-sm font-semibold uppercase tracking-wider mb-4 mt-10" style="color:#9b8e90">Related FAQs</h3>
    <div class="rounded-2xl overflow-hidden mb-8" style="border:1px solid rgba(255,255,255,0.06)">
        <div x-data="{ open: null }" class="divide-y" style="divide-color:rgba(255,255,255,0.04)">
            @foreach($faqs as $faq)
                <div>
                    <button @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-white/5 transition-all text-sm font-medium">
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
@endsection
