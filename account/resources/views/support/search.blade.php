@extends('support.layout')

@section('support_content')
<div class="col-span-full">
    <div class="mb-12">
        <h1 class="text-3xl font-black text-gray-900 font-google">Search results for: <span class="text-blue-600">"{{ $query }}"</span></h1>
        <p class="text-gray-500 mt-2 font-medium">{{ is_array($results) ? count($results) : $results->count() }} articles found</p>
    </div>

    <div class="space-y-6">
        @forelse($results as $article)
            <a href="{{ route('support.article', $article->slug) }}" class="block bg-white p-8 rounded-[2.5rem] border border-gray-100 hover:border-blue-600 hover:shadow-xl hover:shadow-blue-600/5 transition group">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                    <span class="text-blue-600">{{ ucfirst($article->category) }}</span>
                    <i class="fas fa-chevron-right text-[8px]"></i>
                    <span>Article</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 mb-3">{{ $article->title }}</h3>
                <p class="text-gray-500 leading-relaxed">{{ $article->summary }}</p>
                
                <div class="mt-6 flex items-center gap-2 text-blue-600 font-bold text-sm">
                    Read guide <i class="fas fa-arrow-right ml-1 group-hover:translate-x-2 transition"></i>
                </div>
            </a>
        @empty
            <div class="py-20 text-center bg-gray-50 rounded-[3rem] border-2 border-dashed border-gray-200">
                <i class="fas fa-search-minus text-gray-300 text-5xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No results matching your search</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">Try searching for different keywords or browse our categories below.</p>
                <a href="{{ route('support.index') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gray-900 text-white rounded-2xl font-bold hover:bg-black transition">
                    Browse Categories
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
