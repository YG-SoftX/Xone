@extends('layouts.dashboard')
@section('title', 'Account Storage')

@section('dashboard-content')
<div class="max-w-5xl mx-auto px-6 py-12">
    
    <!-- Header -->
    <div class="text-center mb-16">
        <h1 class="text-[32px] font-normal text-[#202124] mb-4">You have 15 GB of storage</h1>
        <p class="text-sm text-[#5f6368] max-w-xl mx-auto">Your storage is shared across YG Drive, YG Mail, and YG Photos. If you run out, you can't save new files or send emails.</p>
    </div>

    <!-- Storage Distribution Chart -->
    <div class="google-card mb-12">
        <div class="flex flex-col md:flex-row items-center gap-12">
            <!-- Visual Circle Chart (CSS) -->
            <div class="relative w-48 h-48 flex items-center justify-center shrink-0">
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="96" cy="96" r="88" stroke="#f1f3f4" stroke-width="16" fill="transparent" />
                    <circle cx="96" cy="96" r="88" stroke="#1a73e8" stroke-width="16" fill="transparent" stroke-dasharray="552" stroke-dashoffset="480" />
                    <circle cx="96" cy="96" r="88" stroke="#34a853" stroke-width="16" fill="transparent" stroke-dasharray="552" stroke-dashoffset="530" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold text-gray-900">12%</span>
                    <span class="text-[10px] uppercase font-bold text-gray-400">Used</span>
                </div>
            </div>

            <!-- Legends -->
            <div class="flex-1 space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-3 h-3 rounded-full bg-[#1a73e8]"></div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700">YG Drive</span>
                            <span class="font-bold text-gray-900">1.2 GB</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full"><div class="bg-[#1a73e8] h-1.5 rounded-full" style="width: 8%"></div></div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-3 h-3 rounded-full bg-[#34a853]"></div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700">YG Mail</span>
                            <span class="font-bold text-gray-900">0.4 GB</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full"><div class="bg-[#34a853] h-1.5 rounded-full" style="width: 3%"></div></div>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-gray-400">
                    <div class="w-3 h-3 rounded-full bg-gray-200"></div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm">
                            <span>Free Space</span>
                            <span>13.4 GB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage Tiers -->
    <h2 class="text-xl font-normal text-[#202124] mb-8">Recommended Imperial Plans</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Basic -->
        <div class="google-card border-[#1a73e8]/30">
            <h3 class="text-lg font-normal text-[#202124] mb-2">100 GB</h3>
            <div class="text-2xl font-bold text-gray-900 mb-4">$1.99 <span class="text-xs text-gray-500 font-normal">/ month</span></div>
            <ul class="space-y-3 mb-8">
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    100 GB of storage
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    YG Experts support
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    Share with up to 5 people
                </li>
            </ul>
            <button class="w-full py-2 bg-[#1a73e8] text-white rounded-full text-sm font-medium hover:bg-blue-700 shadow-md">Get started</button>
        </div>

        <!-- Standard (Recommended) -->
        <div class="google-card ring-2 ring-blue-600 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 bg-blue-600 text-white text-[10px] font-bold uppercase py-1 text-center">Recommended</div>
            <h3 class="text-lg font-normal text-[#202124] mt-4 mb-2">200 GB</h3>
            <div class="text-2xl font-bold text-gray-900 mb-4">$2.99 <span class="text-xs text-gray-500 font-normal">/ month</span></div>
            <ul class="space-y-3 mb-8">
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    200 GB of storage
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    3% back in YG Pay
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    Imperial Dark Web monitor
                </li>
            </ul>
            <button class="w-full py-2 bg-[#1a73e8] text-white rounded-full text-sm font-medium hover:bg-blue-700 shadow-md">Get started</button>
        </div>

        <!-- Premium -->
        <div class="google-card">
            <h3 class="text-lg font-normal text-[#202124] mb-2">2 TB</h3>
            <div class="text-2xl font-bold text-gray-900 mb-4">$9.99 <span class="text-xs text-gray-500 font-normal">/ month</span></div>
            <ul class="space-y-3 mb-8">
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    2 TB of storage
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    10% back in YG Pay
                </li>
                <li class="flex items-start gap-2 text-xs text-gray-600">
                    <i class="fas fa-check text-blue-600 mt-0.5"></i>
                    Imperial AI advanced features
                </li>
            </ul>
            <button class="w-full py-2 border border-gray-300 text-[#1a73e8] rounded-full text-sm font-medium hover:bg-blue-50">Get started</button>
        </div>
    </div>

</div>
@endsection
