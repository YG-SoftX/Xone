@extends('support.layout')

@section('support_content')
<div class="col-span-full">
    <div class="flex items-center gap-4 mb-10">
        <a href="{{ route('support.index') }}" class="w-10 h-10 bg-white border border-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-600 hover:border-blue-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-3xl font-black text-gray-900 font-google">{{ ucfirst($category) }} Help Center</h1>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @forelse($articles as $article)
            <a href="{{ route('support.article', $article->slug) }}" class="bg-white p-6 rounded-3xl border border-gray-100 hover:border-blue-600 transition group">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">{{ $article->title }}</h3>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-600 group-hover:translate-x-1 transition"></i>
                </div>
                <p class="text-gray-500 text-sm line-clamp-2">{{ $article->summary }}</p>
            </a>
        @empty
            <div class="col-span-full py-20 text-center bg-gray-50 rounded-[3rem] border-2 border-dashed border-gray-200">
                <i class="fas fa-folder-open text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 font-bold">No articles found in this category yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
