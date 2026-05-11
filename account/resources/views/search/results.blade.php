@extends('layouts.dashboard')
@section('title', 'Search Results')

@section('dashboard-content')
<div class="max-w-4xl mx-auto px-6 py-10">
    
    <!-- Header / New Search -->
    <div class="flex items-center gap-8 mb-12">
        <h1 class="text-3xl font-normal text-gray-900 tracking-tighter">
            <span class="text-brand">YG</span> Search
        </h1>
        <div class="flex-1 relative flex items-center group">
            <input type="text" value="Future of Neural Sovereignty" 
                class="w-full pl-6 pr-14 py-3.5 bg-white border border-gray-200 rounded-full text-sm focus:ring-4 focus:ring-brand/5 focus:border-brand/30 outline-none shadow-sm transition-all">
            <div class="absolute right-6 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>

    <!-- Filter Chips -->
    <div class="flex gap-3 mb-10 overflow-x-auto pb-2">
        <button class="px-6 py-2 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-sm">All Results</button>
        <button class="px-6 py-2 bg-white text-gray-500 border border-gray-100 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">Images</button>
        <button class="px-6 py-2 bg-white text-gray-500 border border-gray-100 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">News</button>
        <button class="px-6 py-2 bg-white text-gray-500 border border-gray-100 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">Imperial Journal</button>
    </div>

    <!-- BRAND AUTHORITY CARD (KNOWLEDGE PANEL) -->
    <x-search-brand-card />

    <!-- SPONSORED RESULTS (YG ADS) -->
    <div class="mb-12 space-y-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Sponsored by YG Ads</span>
            <div class="flex-1 h-px bg-gray-100"></div>
        </div>
        
        <!-- High-Fidelity Search Ad -->
        <div class="group cursor-pointer">
            <a href="#" class="text-[11px] text-gray-500 mb-1 block hover:underline">https://node-expansion.ygxone.com</a>
            <h3 class="text-xl font-normal text-brand mb-2 group-hover:underline">Scale Your Imperial Node Infrastructure Today</h3>
            <p class="text-[14px] text-gray-600 leading-relaxed line-clamp-2">Provision new neural nodes instantly across the 16-node ecosystem. Secure, sovereign, and fully manageable via the YG Master Admin.</p>
        </div>

        <div class="group cursor-pointer">
            <a href="#" class="text-[11px] text-gray-500 mb-1 block hover:underline">https://pay.ygxone.com/business</a>
            <h3 class="text-xl font-normal text-brand mb-2 group-hover:underline">YG Pay: The Future of Sovereign Enterprise Finance</h3>
            <p class="text-[14px] text-gray-600 leading-relaxed line-clamp-2">Integrated payouts, global ad budgeting, and 100% financial sovereignty for your private organization.</p>
        </div>
    </div>

    <!-- WEB RESULTS (SIMULATED) -->
    <div class="space-y-10">
        <div class="group cursor-pointer">
            <a href="#" class="text-[11px] text-green-700 mb-1 block truncate">https://en.wikipedia.org/wiki/Sovereignty</a>
            <h3 class="text-xl font-normal text-blue-800 mb-2 group-hover:underline">Sovereignty - Wikipedia</h3>
            <p class="text-[14px] text-gray-600 leading-relaxed">Sovereignty is the defining authority within individual consciousness, social construct or territory. In political theory, sovereignty is a substantive term...</p>
        </div>

        <div class="group cursor-pointer">
            <a href="#" class="text-[11px] text-green-700 mb-1 block truncate">https://www.wired.com/story/sovereign-ai-clouds</a>
            <h3 class="text-xl font-normal text-blue-800 mb-2 group-hover:underline">The Rise of Sovereign AI Clouds | WIRED</h3>
            <p class="text-[14px] text-gray-600 leading-relaxed">Countries and organizations are increasingly looking to build their own "Sovereign AI" clouds to protect their data and maintain technological authority.</p>
        </div>
    </div>

</div>
@endsection
