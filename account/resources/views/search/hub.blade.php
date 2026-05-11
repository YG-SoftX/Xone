@extends('layouts.dashboard')
@section('title', 'Sovereign Search')

@section('dashboard-content')
<div class="min-h-[80vh] flex flex-col items-center justify-center px-6">
    
    <!-- Branding -->
    <div class="mb-12 text-center">
        <h1 class="text-7xl font-normal tracking-tighter text-gray-900 mb-4">
            <span class="text-brand">YG</span> Search
        </h1>
        <p class="text-sm text-gray-500 font-medium tracking-widest uppercase">The Sovereign Gateway to the Global Web</p>
    </div>

    <!-- Search Bar (Google-style) -->
    <div class="w-full max-w-2xl group">
        <div class="relative flex items-center">
            <div class="absolute left-6 text-gray-400 group-focus-within:text-brand transition-colors">
                <i class="fas fa-search text-lg"></i>
            </div>
            <input type="text" placeholder="Search the global web or type a URL" 
                class="w-full pl-16 pr-14 py-5 bg-white border border-gray-200 rounded-[2rem] text-lg focus:ring-4 focus:ring-brand/5 focus:border-brand/30 outline-none shadow-sm group-hover:shadow-md transition-all duration-300">
            <div class="absolute right-6 flex items-center gap-4 text-gray-400">
                <i class="fas fa-microphone hover:text-brand cursor-pointer"></i>
                <i class="fas fa-camera hover:text-brand cursor-pointer"></i>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-12 flex gap-4">
        <button class="px-6 py-2.5 bg-gray-50 text-gray-600 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-100">
            Imperial Journal
        </button>
        <button class="px-6 py-2.5 bg-gray-50 text-gray-600 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-100">
            Trending Nodes
        </button>
    </div>

    <!-- Privacy Badge -->
    <div class="mt-20 flex items-center gap-2 px-4 py-2 bg-green-50 text-green-600 rounded-full border border-green-100">
        <i class="fas fa-shield-alt text-xs"></i>
        <span class="text-[10px] font-bold uppercase tracking-widest">Privacy Sovereign Search Enabled</span>
    </div>

</div>
@endsection
