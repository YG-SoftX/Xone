@extends('layouts.dashboard')
@section('title', 'Global Monetization Commander')

@section('dashboard-content')
<div class="max-w-7xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-brand/5 rounded-2xl flex items-center justify-center shadow-sm">
                <i class="fas fa-project-diagram text-brand text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Global Monetization Commander</h1>
                <p class="text-[14px] text-gray-500">Supreme oversight of the Imperial Ads & AdSense ecosystem</p>
            </div>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.monetization.moderation') }}" class="px-6 py-2.5 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-md hover:bg-opacity-90 transition-all">
                Moderation Queue
            </a>
        </div>
    </div>

    <!-- Global Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="google-card border-brand/20">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Total Empire Revenue</div>
            <div class="text-3xl font-normal text-gray-900">$142,500.00</div>
            <div class="mt-2 text-[10px] text-green-600 font-bold flex items-center gap-1">
                <i class="fas fa-arrow-up"></i> 12.5% vs last month
            </div>
        </div>
        <div class="google-card border-blue-200">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Active Advertisers</div>
            <div class="text-3xl font-normal text-gray-900">1,240</div>
            <div class="mt-2 text-[10px] text-blue-600 font-bold">16 Unique Nodes</div>
        </div>
        <div class="google-card border-purple-200">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Global Impressions</div>
            <div class="text-3xl font-normal text-gray-900">45.2M</div>
            <div class="mt-2 text-[10px] text-purple-600 font-bold">4.2% Global CTR</div>
        </div>
        <div class="google-card border-green-200">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Verified Publishers</div>
            <div class="text-3xl font-normal text-gray-900">842</div>
            <div class="mt-2 text-[10px] text-green-600 font-bold">YG AdSense Verified</div>
        </div>
    </div>

    <!-- Management Tabs -->
    <div class="google-card !p-0 overflow-hidden">
        <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest">All Imperial Campaigns</h3>
            <div class="flex gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Search campaigns or orgs" class="pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-brand outline-none w-64">
                </div>
            </div>
        </div>
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                    <th class="px-8 py-4">Organization</th>
                    <th class="px-8 py-4">Campaign Name</th>
                    <th class="px-8 py-4">Status</th>
                    <th class="px-8 py-4">Daily Budget</th>
                    <th class="px-8 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <!-- Sample Rows -->
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-8 py-6">
                        <p class="text-sm font-bold text-gray-900">Imperial Core</p>
                        <p class="text-[10px] text-gray-400 uppercase">Node 01</p>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-sm font-normal text-gray-900">Neural Node Expansion</p>
                        <p class="text-[10px] text-gray-400">ID: CAM-4921</p>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest rounded-full">Active</span>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-sm font-bold text-gray-900">$500.00</p>
                        <p class="text-[10px] text-gray-400">CPM Bidding</p>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <button class="p-2 text-gray-400 hover:text-brand transition-all"><i class="fas fa-edit"></i></button>
                        <button class="p-2 text-gray-400 hover:text-red-600 transition-all"><i class="fas fa-ban"></i></button>
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-8 py-6">
                        <p class="text-sm font-bold text-gray-900">Sovereign Logistics</p>
                        <p class="text-[10px] text-gray-400 uppercase">Node 05</p>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-sm font-normal text-gray-900">Global Supply Chain AI</p>
                        <p class="text-[10px] text-gray-400">ID: CAM-8832</p>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 bg-yellow-50 text-yellow-600 text-[9px] font-black uppercase tracking-widest rounded-full">Paused</span>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-sm font-bold text-gray-900">$250.00</p>
                        <p class="text-[10px] text-gray-400">CPC Bidding</p>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <button class="p-2 text-gray-400 hover:text-brand transition-all"><i class="fas fa-edit"></i></button>
                        <button class="p-2 text-gray-400 hover:text-red-600 transition-all"><i class="fas fa-ban"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
