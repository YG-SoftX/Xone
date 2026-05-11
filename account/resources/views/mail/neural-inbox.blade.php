@extends('layouts.dashboard')
@section('title', 'Neural Inbox')

@section('dashboard-content')
<div class="h-[calc(100vh-64px)] flex flex-col bg-white">
    
    <!-- Neural Category Tabs -->
    <div class="px-4 border-b border-gray-100 flex gap-1 pt-2 bg-gray-50/30">
        <button class="flex items-center gap-3 px-6 py-3 border-b-4 border-blue-600 text-blue-600 font-medium text-sm transition-all">
            <i class="fas fa-inbox"></i>
            Primary
        </button>
        <button class="flex items-center gap-3 px-6 py-3 border-b-4 border-transparent text-gray-500 hover:bg-gray-100 font-medium text-sm transition-all group">
            <i class="fas fa-users group-hover:text-green-600"></i>
            Social
            <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full">12 new</span>
        </button>
        <button class="flex items-center gap-3 px-6 py-3 border-b-4 border-transparent text-gray-500 hover:bg-gray-100 font-medium text-sm transition-all group">
            <i class="fas fa-tag group-hover:text-yellow-600"></i>
            Promotions
            <span class="bg-yellow-100 text-yellow-700 text-[10px] px-2 py-0.5 rounded-full">45 new</span>
        </button>
        <button class="flex items-center gap-3 px-6 py-3 border-b-4 border-transparent text-gray-500 hover:bg-gray-100 font-medium text-sm transition-all group">
            <i class="fas fa-info-circle group-hover:text-purple-600"></i>
            Updates
        </button>
    </div>

    <!-- Toolbar -->
    <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between bg-white">
        <div class="flex items-center gap-2">
            <div class="p-2 hover:bg-gray-100 rounded-lg cursor-pointer transition"><input type="checkbox" class="rounded border-gray-300"></div>
            <div class="p-2 hover:bg-gray-100 rounded-lg cursor-pointer transition text-gray-500"><i class="fas fa-redo-alt text-xs"></i></div>
            <div class="p-2 hover:bg-gray-100 rounded-lg cursor-pointer transition text-gray-500"><i class="fas fa-ellipsis-v text-xs"></i></div>
        </div>
        <div class="text-xs text-gray-400">1-50 of 1,245</div>
    </div>

    <!-- Email List -->
    <div class="flex-1 overflow-y-auto">
        <div class="divide-y divide-gray-100">
            
            <!-- Important Email (AI Flagged) -->
            <div class="flex items-center gap-4 px-4 py-3 hover:shadow-md transition-shadow cursor-pointer bg-blue-50/30 group">
                <div class="flex items-center gap-3 shrink-0">
                    <input type="checkbox" class="rounded border-gray-300">
                    <i class="far fa-star text-gray-300 hover:text-yellow-400 transition"></i>
                    <i class="fas fa-caret-right text-blue-600" title="Neural Priority"></i>
                </div>
                <div class="w-48 font-bold text-gray-900 truncate">YG Treasury</div>
                <div class="flex-1 min-w-0">
                    <span class="font-bold text-gray-900">May Statement Ready</span>
                    <span class="text-gray-500 mx-2">—</span>
                    <span class="text-gray-500 truncate">Your imperial statement for the month of May is now available for download in the billing hub...</span>
                </div>
                <div class="text-xs font-bold text-gray-900 shrink-0">2:45 PM</div>
            </div>

            <!-- Standard Email -->
            @foreach(range(1, 10) as $i)
            <div class="flex items-center gap-4 px-4 py-3 hover:shadow-md transition-shadow cursor-pointer group">
                <div class="flex items-center gap-3 shrink-0">
                    <input type="checkbox" class="rounded border-gray-300">
                    <i class="far fa-star text-gray-300 hover:text-yellow-400 transition"></i>
                    <i class="fas fa-caret-right text-gray-100 group-hover:text-gray-300 transition"></i>
                </div>
                <div class="w-48 font-medium text-gray-700 truncate">Sovereign Partner #{{ $i }}</div>
                <div class="flex-1 min-w-0">
                    <span class="font-medium text-gray-900">Project Update: Imperial Expansion Phase {{ $i }}</span>
                    <span class="text-gray-500 mx-2">—</span>
                    <span class="text-gray-500 truncate">We have completed the core deployment of the neural nodes and are ready to begin the synchronization...</span>
                </div>
                <div class="text-xs font-medium text-gray-400 shrink-0">Yesterday</div>
            </div>
            @endforeach

        </div>
    </div>

    <!-- Floating Action Button (Google Style) -->
    <div class="fixed bottom-8 right-8">
        <button class="bg-white hover:shadow-xl transition-shadow shadow-lg border border-gray-100 rounded-2xl flex items-center gap-4 px-6 py-4 group">
            <i class="fas fa-pen text-xl text-[#1a73e8]"></i>
            <span class="font-bold text-gray-700">Compose</span>
        </button>
    </div>

</div>

<style>
    /* Custom Scrollbar for Google feel */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #dadce0; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #bdc1c6; }
</style>
@endsection
