@extends('layouts.app')

@section('title', 'Ecosystem Pulse - YG Guard')
@section('page-title', 'Social Growth Analytics')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    <div class="flex items-center justify-between mb-12">
        <div>
            <h1 class="text-4xl font-black text-gray-900 font-google tracking-tight mb-2">Ecosystem Pulse</h1>
            <p class="text-gray-500 text-lg">Detailed intelligence on your social growth and community authority.</p>
        </div>
        <div class="hidden md:block">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-2xl shadow-blue-600/20">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        {{-- Merit Card: Stones --}}
        <div class="bg-gradient-to-br from-gray-900 to-blue-900 p-10 rounded-[3.5rem] text-white shadow-2xl shadow-blue-900/20 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition">
                <i class="fas fa-gem text-8xl transform rotate-12"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-50 mb-6">Total Accumulated Merit</p>
            <h2 class="text-6xl font-black mb-2">{{ number_format($user->stones) }}</h2>
            <p class="text-sm font-bold text-blue-300">YG Stones</p>
        </div>

        {{-- Authority Card: Rank --}}
        <div class="bg-white p-10 rounded-[3.5rem] border border-gray-100 shadow-2xl shadow-gray-200/10">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 mb-6">Current Standing</p>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-2xl shadow-sm">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ $user->rank }}</h3>
                    <p class="text-[10px] font-black uppercase tracking-widest text-blue-500">Global Authority</p>
                </div>
            </div>
            <div class="pt-6 border-t border-gray-50">
                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                    <span>Rank Progress</span>
                    <span>85%</span>
                </div>
                <div class="h-2 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                    <div class="h-full bg-blue-600 w-[85%] shadow-glow"></div>
                </div>
            </div>
        </div>

        {{-- Position Card --}}
        <div class="bg-white p-10 rounded-[3.5rem] border border-gray-100 shadow-2xl shadow-gray-200/10">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 mb-6">Global Percentile</p>
            <h2 class="text-5xl font-black text-gray-900 mb-2">Top {{ $rankPercentage }}%</h2>
            <p class="text-sm font-bold text-gray-400 leading-relaxed">You are currently ranked <strong>#{{ $rankPosition }}</strong> out of {{ $totalUsers }} members in the empire.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        {{-- Activity Breakdown --}}
        <div class="bg-white p-10 rounded-[3.5rem] border border-gray-100 shadow-2xl shadow-gray-200/10">
            <h3 class="text-2xl font-black text-gray-900 mb-8 tracking-tight">Social Footprint</h3>
            <div class="space-y-6">
                <div class="p-6 bg-gray-50 rounded-[2rem] border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-purple-600 shadow-sm">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Total Contributions</h4>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Posts Shared</p>
                        </div>
                    </div>
                    <span class="text-2xl font-black text-gray-900">{{ $totalPosts }}</span>
                </div>

                <div class="p-6 bg-gray-50 rounded-[2rem] border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-red-500 shadow-sm">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Total Influence</h4>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Echoes Received</p>
                        </div>
                    </div>
                    <span class="text-2xl font-black text-gray-900">{{ $totalLikesReceived }}</span>
                </div>
            </div>
        </div>

        {{-- Growth Tips --}}
        <div class="p-10 bg-blue-600 rounded-[3.5rem] text-white shadow-2xl shadow-blue-600/20 relative overflow-hidden">
            <div class="relative z-10">
                <h3 class="text-2xl font-black mb-4 tracking-tight">Ascend to Legend</h3>
                <p class="text-blue-100 mb-8 leading-relaxed">The path to the High Council requires consistent value. Share high-quality media and engage in meaningful threads to boost your Stones.</p>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-4 text-xs font-bold bg-white/10 p-4 rounded-2xl border border-white/10">
                        <i class="fas fa-check-circle text-blue-300"></i>
                        Share a Video (+10 Stones)
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold bg-white/10 p-4 rounded-2xl border border-white/10">
                        <i class="fas fa-check-circle text-blue-300"></i>
                        Get 5 Echoes (+10 Stones)
                    </div>
                </div>
            </div>
            <div class="absolute bottom-[-50px] right-[-50px] opacity-10">
                <i class="fas fa-rocket text-[200px]"></i>
            </div>
        </div>
    </div>

</div>
@endsection
