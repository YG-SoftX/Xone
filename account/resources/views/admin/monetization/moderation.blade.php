@extends('layouts.dashboard')
@section('title', 'Imperial Monetization Sentinel')

@section('dashboard-content')
<div class="max-w-6xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center shadow-sm">
                <i class="fas fa-hand-holding-usd text-green-600 text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Imperial Monetization Sentinel</h1>
                <p class="text-[14px] text-gray-500">Govern the imperial ad network and vet publisher sites</p>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
        <div class="google-card border-brand/20">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Pending Campaigns</div>
            <div class="text-3xl font-normal text-gray-900">{{ $pendingCampaigns->count() }}</div>
        </div>
        <div class="google-card border-green-200">
            <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Pending Site Verifications</div>
            <div class="text-3xl font-normal text-gray-900">{{ $pendingSites->count() }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 text-sm font-medium flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <!-- YG Ads Moderation -->
    <div class="mb-12">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-6 flex items-center gap-2">
            <i class="fas fa-bullseye text-brand"></i> Pending YG Ads Campaigns
        </h3>
        <div class="google-card !p-0 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                        <th class="px-6 py-4">Organization / Advertiser</th>
                        <th class="px-6 py-4">Ad Details</th>
                        <th class="px-6 py-4">Budget</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingCampaigns as $campaign)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-6">
                            <p class="text-sm font-bold text-gray-900">{{ $campaign->organization->name }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">Advertiser: {{ $campaign->advertiser->name }}</p>
                        </td>
                        <td class="px-6 py-6 min-w-[300px]">
                            <div class="flex gap-4">
                                <img src="{{ \Storage::url($campaign->image_url) }}" class="w-12 h-12 rounded-lg object-cover" alt="">
                                <div class="min-w-0">
                                    <h5 class="text-xs font-bold text-gray-900 truncate">{{ $campaign->title }}</h5>
                                    <p class="text-[11px] text-gray-500 line-clamp-2 leading-relaxed">{{ $campaign->content }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <p class="text-sm font-bold text-gray-900">${{ number_format($campaign->budget, 2) }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">Total Budget</p>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex justify-end gap-2">
                                <form action="{{ route('admin.ads.approve', $campaign) }}" method="POST">
                                    @csrf
                                    <button class="p-2.5 bg-brand/10 text-brand rounded-xl border border-brand/20 hover:bg-brand hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.monetization.reject', ['type' => 'campaign', 'id' => $campaign->id]) }}" method="POST">
                                    @csrf
                                    <button class="p-2.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No pending ad campaigns.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- YG AdSense Moderation -->
    <div>
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-6 flex items-center gap-2">
            <i class="fas fa-globe text-green-600"></i> Pending YG AdSense Sites
        </h3>
        <div class="google-card !p-0 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                        <th class="px-6 py-4">Domain</th>
                        <th class="px-6 py-4">Organization</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingSites as $site)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-6">
                            <p class="text-sm font-bold text-gray-900">{{ $site->domain }}</p>
                            <p class="text-[10px] text-gray-400">Token: {{ $site->verification_token }}</p>
                        </td>
                        <td class="px-6 py-6">
                            <p class="text-sm font-normal text-gray-900">{{ $site->organization->name }}</p>
                        </td>
                        <td class="px-6 py-6">
                            <span class="px-3 py-1 bg-yellow-50 text-yellow-600 text-[9px] font-black uppercase tracking-widest rounded-full">Pending Verification</span>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex justify-end gap-2">
                                <form action="{{ route('admin.adsense.approve', $site) }}" method="POST">
                                    @csrf
                                    <button class="p-2.5 bg-green-50 text-green-600 rounded-xl border border-green-100 hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.monetization.reject', ['type' => 'site', 'id' => $site->id]) }}" method="POST">
                                    @csrf
                                    <button class="p-2.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No pending site verifications.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
