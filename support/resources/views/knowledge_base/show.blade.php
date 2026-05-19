@extends('layouts.app')
@section('title', $article->title)

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs mb-6" style="color:#9b8e90">
    <a href="{{ route('knowledge.index') }}" class="hover:text-white transition-colors">Knowledge Base</a>
    <span>›</span>
    @if($article->category)
        <a href="{{ route('knowledge.category', $article->category) }}" class="hover:text-white transition-colors">
            {{ $article->categoryLabel() }}
        </a>
        <span>›</span>
    @endif
    <span>{{ \Illuminate\Support\Str::limit($article->title, 40) }}</span>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    {{-- Article content --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl p-6 lg:p-8" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            @if($article->type === 'faq')
                <div class="inline-block text-xs font-medium uppercase tracking-wider px-2 py-0.5 rounded-full mb-4"
                     style="background:rgba(250,204,21,0.15);color:#facc15;border:1px solid rgba(250,204,21,0.2)">
                    FAQ
                </div>
            @endif

            <h1 class="text-2xl font-bold mb-4">{{ $article->title }}</h1>

            <div class="text-sm leading-relaxed prose prose-invert max-w-none" style="color:#d0c5c8">
                {!! nl2br(e($article->content)) !!}
            </div>

            <div class="mt-8 pt-6 border-t" style="border-color:rgba(255,255,255,0.06)">
                <p class="text-xs" style="color:#6b5f63">
                    Last updated: {{ $article->updated_at->format('F j, Y') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Still need help? --}}
        <div class="rounded-2xl p-5 text-center" style="background:rgba(255,0,60,0.05);border:1px solid rgba(255,0,60,0.12)">
            <div class="text-2xl mb-2">💬</div>
            <h4 class="font-semibold text-sm mb-2">Still need help?</h4>
            <p class="text-xs mb-4" style="color:#9b8e90">Can't find the answer? Our support team is here.</p>
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all hover:opacity-90"
               style="background:#ff003c;color:#fff">
                Create Ticket
            </a>
        </div>

        {{-- Related articles --}}
        @if($related->count() > 0)
            <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
                <h4 class="font-semibold text-sm mb-3">Related Articles</h4>
                <div class="space-y-2">
                    @foreach($related as $rel)
                        <a href="{{ route('knowledge.show', $rel->slug) }}"
                           class="block text-xs py-2 hover:text-[#ff003c] transition-colors" style="color:#b0a4a8">
                            {{ $rel->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
