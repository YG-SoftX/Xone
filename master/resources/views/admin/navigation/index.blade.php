@extends('layouts.admin')

@section('title', 'Navigation Architect - Universal Authority')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ showAdd: false }">
    
    <!-- Header: Universal Navigation Authority -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
        <div>
            <h1 class="text-4xl font-black text-white leading-tight mb-2 tracking-tighter">{{ __("NAVIGATION ARCHITECT") }}</h1>
            <p class="text-gray-400 text-sm tracking-widest uppercase font-bold">{{ __("Master Authority Over Ecosystem Flow") }}</p>
        </div>
        <button @click="showAdd = true" class="group relative px-10 py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all">
            <span class="relative z-10 flex items-center uppercase tracking-widest text-sm">
                <i class="fas fa-plus-circle mr-3 group-hover:rotate-90 transition-transform"></i>
                {{ __("New Authority Link") }}
            </span>
        </button>
    </div>

    <!-- Service Switcher: Targeted Management -->
    <div class="flex flex-wrap gap-4 mb-12">
        @foreach(['account', 'pay', 'mail', 'ai', 'drive'] as $s)
            <a href="{{ route('admin.navigation.index', ['service' => $s]) }}" 
               class="px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all {{ $service == $s ? 'bg-blue-600 text-white shadow-2xl shadow-blue-600/30' : 'bg-white/5 text-gray-500 hover:bg-white/10 hover:text-white border border-white/5' }}">
                {{ strtoupper($s) }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        <!-- Header Menu: Top-Level Authority -->
        <div class="space-y-6">
            <h3 class="text-xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                <i class="fas fa-heading text-blue-400"></i> {{ __("Header Navigation") }}
            </h3>
            
            @forelse($items->where('position', 'header') as $item)
                <div class="p-6 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl flex items-center justify-between group hover:border-blue-500/30 transition-all shadow-2xl">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:text-blue-400">
                            <i class="{{ $item->icon ?? 'fas fa-link' }} text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-white text-sm uppercase tracking-tight">{{ $item->label }}</h4>
                            <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">{{ $item->url }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.navigation.destroy', $item->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="text-gray-600 hover:text-red-400 transition-colors"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-12 border-2 border-dashed border-white/10 rounded-3xl text-center bg-white/2">
                    <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">No header links configured for {{ strtoupper($service) }}</p>
                </div>
            @endforelse
        </div>

        <!-- Footer Menu: Base Authority -->
        <div class="space-y-6">
            <h3 class="text-xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                <i class="fas fa-shoe-prints text-purple-400"></i> {{ __("Footer Links") }}
            </h3>
            
            @forelse($items->where('position', 'footer') as $item)
                <div class="p-6 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl flex items-center justify-between group hover:border-purple-500/30 transition-all shadow-2xl">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:text-purple-400">
                            <i class="{{ $item->icon ?? 'fas fa-link' }} text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-white text-sm uppercase tracking-tight">{{ $item->label }}</h4>
                            <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">{{ $item->url }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.navigation.destroy', $item->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="text-gray-600 hover:text-red-400 transition-colors"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-12 border-2 border-dashed border-white/10 rounded-3xl text-center bg-white/2">
                    <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">No footer links configured for {{ strtoupper($service) }}</p>
                </div>
            @endforelse
        </div>

    </div>

    <!-- Add Link Modal -->
    <template x-if="showAdd">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-2xl">
            <div class="bg-[#020617] w-full max-w-xl rounded-[3.5rem] border border-white/10 shadow-3xl p-10 md:p-14 relative overflow-hidden" @click.away="showAdd = false">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-purple-600"></div>
                
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-black text-white mb-2 uppercase tracking-tighter">Establish Authority</h2>
                    <p class="text-gray-400 text-sm">Define a new dynamic navigation link for the {{ strtoupper($service) }} service.</p>
                </div>

                <form method="POST" action="{{ route('admin.navigation.store') }}" class="space-y-8">
                    @csrf
                    <input type="hidden" name="service_key" value="{{ $service }}">
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Position</label>
                            <select name="position" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition">
                                <option value="header">Header Menu</option>
                                <option value="footer">Footer Section</option>
                                <option value="sidebar">Sovereign Sidebar</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Order</label>
                            <input type="number" name="order" value="0" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Label (Display Text)</label>
                        <input type="text" name="label" required class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="e.g. My Ledger">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Target URL</label>
                        <input type="text" name="url" required class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="https://...">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Icon (FontAwesome Class)</label>
                        <input type="text" name="icon" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="fas fa-link">
                    </div>

                    <div class="pt-6 grid grid-cols-2 gap-6">
                        <button type="button" @click="showAdd = false" class="py-5 text-gray-500 font-black uppercase tracking-widest text-[10px] hover:text-white transition-colors">Abort</button>
                        <button type="submit" class="py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-xs">
                            Broadcast Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<style>
    body { background-color: #020617 !important; color: #f8fafc; }
</style>
@endsection
