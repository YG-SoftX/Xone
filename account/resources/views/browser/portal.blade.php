@extends('layouts.dashboard')
@section('title', 'Imperial Browser')

@section('dashboard-content')
<div class="h-screen flex flex-col bg-gray-50 -m-8 overflow-hidden">
    
    <!-- Browser Chrome (Top Bar) -->
    <div class="bg-white border-b border-gray-200 p-3 flex items-center gap-4 shadow-sm">
        <!-- Navigation Controls -->
        <div class="flex items-center gap-2 px-2">
            <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-arrow-left"></i></button>
            <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-arrow-right"></i></button>
            <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-redo"></i></button>
        </div>

        <!-- Imperial Omnibox -->
        <div class="flex-1 relative group">
            <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                <i class="fas fa-shield-alt text-green-500 text-xs"></i>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Sovereign</span>
            </div>
            <input type="text" value="https://search.ygxone.com/results?q=Neural+Sovereignty" 
                class="w-full pl-24 pr-12 py-2 bg-gray-50 border border-gray-100 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-brand/20 outline-none transition-all">
            <div class="absolute right-4 top-1/2 -translate-y-1/2">
                <i class="fas fa-star text-gray-300 hover:text-brand cursor-pointer"></i>
            </div>
        </div>

        <!-- Imperial Extensions (Toolbar) -->
        <div class="flex items-center gap-3 px-2">
            <div class="w-8 h-8 bg-brand/5 rounded-lg flex items-center justify-center text-brand cursor-pointer hover:bg-brand hover:text-white transition-all shadow-sm">
                <i class="fas fa-robot text-xs"></i>
            </div>
            <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center text-green-600 cursor-pointer hover:bg-green-600 hover:text-white transition-all shadow-sm">
                <i class="fas fa-wallet text-xs"></i>
            </div>
            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 cursor-pointer hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                <i class="fas fa-user-shield text-xs"></i>
            </div>
            <div class="w-px h-6 bg-gray-200 mx-1"></div>
            <img src="{{ auth()->user()->user_image }}" class="w-8 h-8 rounded-full border border-gray-200 shadow-sm" alt="">
        </div>
    </div>

    <!-- Browser Main Content Area -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Imperial Sidebar (Pinned Nodes) -->
        <div class="w-16 bg-white border-r border-gray-200 flex flex-col items-center py-6 gap-6 shadow-sm">
            <a href="/home" title="YG Home" class="w-10 h-10 rounded-xl hover:bg-brand/5 flex items-center justify-center text-gray-400 hover:text-brand transition-all"><i class="fas fa-home text-lg"></i></a>
            <a href="/mail" title="YG Mail" class="w-10 h-10 rounded-xl hover:bg-red-50 flex items-center justify-center text-gray-400 hover:text-red-600 transition-all"><i class="fas fa-envelope text-lg"></i></a>
            <a href="/drive" title="YG Drive" class="w-10 h-10 rounded-xl hover:bg-blue-50 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-all"><i class="fas fa-cloud text-lg"></i></a>
            <a href="/docs" title="YG DocX" class="w-10 h-10 rounded-xl hover:bg-emerald-50 flex items-center justify-center text-gray-400 hover:text-emerald-600 transition-all"><i class="fas fa-file-alt text-lg"></i></a>
            <div class="mt-auto">
                <button class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-cog text-lg"></i></button>
            </div>
        </div>

        <!-- Web Content Simulation -->
        <div class="flex-1 bg-white relative">
            <!-- Simulated Page: YG Search Results -->
            <div class="absolute inset-0 overflow-y-auto p-12">
                <div class="max-w-4xl">
                    <div class="flex items-center gap-2 mb-8">
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">Sponsored Result</span>
                        <div class="flex-1 h-px bg-gray-50"></div>
                    </div>
                    <h2 class="text-3xl font-normal text-brand mb-4">YG Xone: The Future of Neural Sovereignty</h2>
                    <p class="text-lg text-gray-600 leading-relaxed mb-8">
                        Experience the world's first fully private, 16-node ecosystem. Your data, your rules, your empire.
                    </p>
                    <div class="grid grid-cols-3 gap-6">
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm">
                            <h4 class="font-bold text-gray-900 mb-2">Private Mail</h4>
                            <p class="text-xs text-gray-500">Zero tracking, pure encryption.</p>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm">
                            <h4 class="font-bold text-gray-900 mb-2">Sovereign Ads</h4>
                            <p class="text-xs text-gray-500">High-yield monetization.</p>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm">
                            <h4 class="font-bold text-gray-900 mb-2">Neural Hub</h4>
                            <p class="text-xs text-gray-500">Private AI intelligence.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
