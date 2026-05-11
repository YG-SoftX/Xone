@extends('layouts.app')
@section('title', 'YG Collect')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-10">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                <i class="fas fa-list-ul text-white"></i>
            </div>
            <div>
                <h1 class="text-[26px] font-normal text-gray-900 tracking-tight">YG Collect</h1>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">Sovereign Data Collection</p>
            </div>
        </div>
        <a href="{{ route('collect.create') }}" class="px-6 py-2.5 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-md hover:bg-opacity-90 transition-all flex items-center gap-2">
            <i class="fas fa-plus text-[10px]"></i> New Form
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($forms as $form)
            <div class="google-card hover:border-brand/30 transition-all group relative">
                <div class="absolute top-4 right-4 text-gray-300 group-hover:text-brand transition-colors">
                    <i class="fas fa-file-alt text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1 pr-8 truncate">{{ $form->title }}</h3>
                <p class="text-xs text-gray-500 mb-6 truncate">{{ $form->description ?? 'No description' }}</p>
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Responses</span>
                        <span class="text-lg font-normal text-gray-900">{{ $form->submissions_count }}</span>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('collect.results', $form->id) }}" class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 hover:bg-brand hover:text-white transition-all" title="View Results">
                            <i class="fas fa-chart-pie text-xs"></i>
                        </a>
                        <a href="{{ route('collect.public', $form->slug) }}" target="_blank" class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 hover:bg-brand hover:text-white transition-all" title="View Public Form">
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center border-2 border-dashed border-gray-200 rounded-3xl">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300 text-2xl">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Forms Yet</h3>
                <p class="text-sm text-gray-500 mb-6">Create your first sovereign form to start collecting data.</p>
                <a href="{{ route('collect.create') }}" class="px-6 py-2 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest hover:bg-opacity-90 inline-block">
                    Create Form
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
