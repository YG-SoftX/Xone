@extends('support.layout')

@section('support_content')
<div class="col-span-full lg:col-span-3">
    {{-- Article Header --}}
    <div class="mb-10">
        <nav class="flex items-center gap-2 text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">
            <a href="{{ route('support.index') }}" class="hover:text-blue-600">Support</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="{{ route('support.category', $article->category) }}" class="hover:text-blue-600">{{ $article->category }}</a>
        </nav>
        <h1 class="text-4xl md:text-5xl font-black text-gray-900 font-google leading-tight">{{ $article->title }}</h1>
    </div>

    {{-- Article Body --}}
    <div class="prose prose-lg max-w-none prose-blue bg-white p-8 md:p-12 rounded-[3rem] border border-gray-100 shadow-sm">
        {!! $article->content !!}
    </div>

    {{-- Feedback Section --}}
    <div class="mt-12 p-8 bg-gray-50 rounded-[2rem] flex flex-col md:flex-row items-center justify-between gap-6 border border-gray-100">
        <span class="text-gray-700 font-bold">Was this article helpful?</span>
        <div class="flex gap-4">
            <button class="px-8 py-3 bg-white border border-gray-200 rounded-2xl font-bold hover:bg-green-50 hover:border-green-500 hover:text-green-600 transition">Yes</button>
            <button class="px-8 py-3 bg-white border border-gray-200 rounded-2xl font-bold hover:bg-red-50 hover:border-red-500 hover:text-red-600 transition">No</button>
        </div>
    </div>
</div>

{{-- Sidebar: Related Articles --}}
<div class="hidden lg:block lg:col-span-1 space-y-8">
    <div class="bg-white p-8 rounded-[2rem] border border-gray-100">
        <h3 class="text-lg font-black text-gray-900 mb-6 font-google">Related Articles</h3>
        <div class="space-y-6">
            @foreach($related as $rel)
                <a href="{{ route('support.article', $rel->slug) }}" class="block group">
                    <p class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition">{{ $rel->title }}</p>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $rel->summary }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="bg-gradient-to-br from-blue-600 to-purple-600 p-8 rounded-[2rem] text-white shadow-xl shadow-blue-600/20">
        <h3 class="text-lg font-bold mb-3">Need more help?</h3>
        <p class="text-sm text-blue-100 mb-6 leading-relaxed">Our experts are available around the clock to assist you with any questions.</p>
        <a href="#" class="block w-full py-4 bg-white text-blue-600 text-center rounded-2xl font-bold hover:bg-blue-50 transition">Contact Support</a>
    </div>
</div>
@endsection
