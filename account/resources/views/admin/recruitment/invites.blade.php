@extends('layouts.dashboard')
@section('title', 'Sovereign Invitation Hub')

@section('dashboard-content')
<div class="max-w-5xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-gray-900 rounded-2xl flex items-center justify-center shadow-xl">
                <i class="fas fa-envelope-open-text text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Sovereign Invitation Hub</h1>
                <p class="text-[14px] text-gray-500">Recruit elite partners and influencers into the YG Xone empire</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-10 p-6 bg-gray-900 text-white rounded-2xl flex items-center justify-between animate-fade-in shadow-2xl">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-brand rounded-full flex items-center justify-center">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <p class="text-sm font-bold">Imperial Recruitment Code Generated</p>
                    <p class="text-xs text-gray-400">{{ session('success') }}</p>
                </div>
            </div>
            <button class="px-6 py-2 bg-white text-gray-900 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-gray-100 transition-all">Copy Link</button>
        </div>
    @endif

    <!-- Invite Composer -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="col-span-1 md:col-span-2">
            <div class="google-card border-gray-200 !p-8">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-8">Compose Elite Invitation</h3>
                <form action="{{ route('admin.recruitment.invite.generate') }}" method="POST" class="space-y-8">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Recipient Name / Channel</label>
                        <input type="text" name="recipient_name" placeholder="e.g. Marques Brownlee or TechCrunch" 
                            class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-4 focus:ring-brand/5 outline-none font-bold text-gray-900">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Reward Tier</label>
                            <select name="reward_tier" class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-4 focus:ring-brand/5 outline-none font-bold text-gray-700">
                                <option value="platinum">Platinum (75% Revenue Share)</option>
                                <option value="gold">Gold (70% Revenue Share)</option>
                                <option value="silver">Silver (68% Revenue Share)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Expiration</label>
                            <select class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-4 focus:ring-brand/5 outline-none font-bold text-gray-700">
                                <option>7 Days (Standard)</option>
                                <option>24 Hours (Urgent)</option>
                                <option>Indefinite (Forever)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-50 text-right">
                        <button class="px-10 py-4 bg-gray-900 text-white rounded-full text-sm font-bold uppercase tracking-widest shadow-xl hover:bg-black transition-all flex items-center gap-3 ml-auto">
                            <i class="fas fa-gem text-xs text-brand"></i> Generate Sovereign Invite
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recruitment Stats -->
        <div class="space-y-6">
            <div class="google-card border-brand/20 bg-brand/5">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Active Invites</div>
                <div class="text-3xl font-normal text-gray-900">12</div>
                <div class="mt-2 text-[10px] text-brand font-bold">4 Accepted Today</div>
            </div>
            <div class="google-card border-gray-200">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Conversion Rate</div>
                <div class="text-3xl font-normal text-gray-900">85%</div>
                <div class="mt-2 text-[10px] text-green-600 font-bold">Elite Performance</div>
            </div>
        </div>
    </div>

</div>
@endsection
