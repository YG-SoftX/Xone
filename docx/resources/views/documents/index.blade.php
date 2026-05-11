@extends('layouts.app')

@section('title', 'Sovereign Documents')

@section('content')
<div class="p-8 md:p-12" x-data="{ view: 'grid', tab: 'documents' }">
    
    <!-- Header: Document Authority -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
        <div>
            <h1 class="text-4xl font-black text-white leading-tight mb-2 tracking-tighter uppercase">{{ __("Sovereign Documents") }}</h1>
            <p class="text-gray-400 text-sm tracking-widest uppercase font-bold">{{ __("Ecosystem Knowledge Base") }}</p>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="flex bg-white/5 rounded-xl p-1 border border-white/10">
                <button @click="view = 'grid'" :class="view === 'grid' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-white'" class="p-2 rounded-lg transition-all">
                    <i class="fas fa-th-large"></i>
                </button>
                <button @click="view = 'list'" :class="view === 'list' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-white'" class="p-2 rounded-lg transition-all">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            <form action="{{ route('documents.store') }}" method="POST">
                @csrf
                <input type="hidden" name="title" value="Untitled Document">
                <button type="submit" class="group relative px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-[10px]">
                    <i class="fas fa-plus mr-2"></i> {{ __("New Document") }}
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Navigation Toggles -->
    <div class="flex gap-4 mb-10">
        <button @click="tab = 'documents'" :class="tab === 'documents' ? 'bg-white/10 text-white border-blue-500/50' : 'text-gray-500 hover:text-white border-white/5'" class="px-8 py-3 rounded-xl border font-black uppercase tracking-widest text-[10px] transition-all">
            {{ __("Library") }}
        </button>
        <button @click="tab = 'templates'" :class="tab === 'templates' ? 'bg-white/10 text-white border-purple-500/50' : 'text-gray-500 hover:text-white border-white/5'" class="px-8 py-3 rounded-xl border font-black uppercase tracking-widest text-[10px] transition-all">
            {{ __("Templates") }}
        </button>
    </div>

    <!-- Library View -->
    <div x-show="tab === 'documents'" x-transition>
        @if($documents->isEmpty())
            <div class="flex flex-col items-center justify-center py-32 bg-white/[0.02] border border-white/5 rounded-[3rem] border-dashed">
                <div class="w-20 h-20 bg-white/5 rounded-[2rem] flex items-center justify-center mb-6">
                    <i class="fas fa-file-alt text-3xl text-gray-700"></i>
                </div>
                <h3 class="text-xl font-black text-gray-500 uppercase tracking-tight mb-2">{{ __("No Knowledge Artifacts") }}</h3>
                <p class="text-gray-600 text-xs uppercase tracking-widest mb-8">{{ __("Initialize your first sovereign record.") }}</p>
                <form action="{{ route('documents.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="title" value="Untitled Document">
                    <button type="submit" class="px-10 py-4 bg-white/5 border border-white/10 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all">
                        {{ __("Create Document") }}
                    </button>
                </form>
            </div>
        @else
            <div :class="view === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8' : 'space-y-4'">
                @foreach($documents as $doc)
                    <div :class="view === 'grid' ? 'bg-white/[0.02] border border-white/5 rounded-[3rem] overflow-hidden group hover:border-blue-500/30 transition-all hover:-translate-y-2' : 'bg-white/[0.02] border border-white/5 rounded-2xl p-6 flex items-center justify-between group'">
                        <a href="{{ route('documents.show', $doc->id) }}" class="flex-1">
                            <template x-if="view === 'grid'">
                                <div class="h-48 bg-gradient-to-br from-blue-500/5 to-purple-500/5 flex items-center justify-center border-b border-white/5">
                                    <i class="fas fa-file-lines text-5xl text-blue-500/20 group-hover:text-blue-500/40 transition-all"></i>
                                </div>
                            </template>
                            <div :class="view === 'grid' ? 'p-8' : ''">
                                <div class="flex items-center gap-4 mb-3">
                                    <i class="fas fa-file-word text-blue-500"></i>
                                    <h3 class="font-black text-white truncate text-sm uppercase tracking-tight">{{ $doc->title }}</h3>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-[10px] text-gray-600 font-black uppercase tracking-widest">{{ $doc->updated_at->diffForHumans() }}</p>
                                    <span class="text-[8px] px-3 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-full font-black uppercase">{{ $doc->status }}</span>
                                </div>
                            </div>
                        </a>
                        <div :class="view === 'list' ? 'flex items-center gap-4' : 'hidden group-hover:flex absolute top-6 right-6'">
                            <form action="{{ route('documents.delete', $doc->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="w-10 h-10 bg-black/40 backdrop-blur-xl border border-white/10 rounded-xl flex items-center justify-center text-gray-500 hover:text-red-400 transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Template Gallery -->
    <div x-show="tab === 'templates'" x-transition>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($templates as $template)
                <div class="bg-white/[0.02] border border-white/5 rounded-[3rem] overflow-hidden group hover:border-purple-500/30 transition-all hover:-translate-y-2 cursor-pointer">
                    <div class="h-48 bg-gradient-to-br from-purple-500/5 to-blue-500/5 flex items-center justify-center border-b border-white/5">
                        <i class="fas fa-shapes text-5xl text-purple-500/20"></i>
                    </div>
                    <div class="p-8 text-center">
                        <h3 class="font-black text-white text-sm uppercase tracking-tight mb-2">{{ $template->name }}</h3>
                        <p class="text-[10px] text-gray-600 font-black uppercase tracking-widest">{{ $template->category ?? 'General' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
