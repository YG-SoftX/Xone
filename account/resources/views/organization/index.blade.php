@extends('layouts.dashboard')
@section('title', 'People & sharing')

@section('dashboard-content')
<div class="max-w-6xl mx-auto px-6 py-10">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-[28px] font-normal text-[#202124] mb-2">People & sharing</h1>
            <p class="text-sm text-[#5f6368]">Manage the people who can access your organization's services and data.</p>
        </div>
        <button @click="$dispatch('open-invite-modal')" class="bg-[#1a73e8] text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-blue-700 shadow-md flex items-center gap-2">
            <i class="fas fa-plus"></i> Add staff member
        </button>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="google-card">
            <div class="text-[11px] uppercase font-bold text-[#5f6368] mb-2">Total Staff</div>
            <div class="text-3xl font-normal text-[#202124]">{{ count($members) }}</div>
        </div>
        <div class="google-card">
            <div class="text-[11px] uppercase font-bold text-[#5f6368] mb-2">Pending Invites</div>
            <div class="text-3xl font-normal text-[#202124]">2</div>
        </div>
        <div class="google-card">
            <div class="text-[11px] uppercase font-bold text-[#5f6368] mb-2">Available Seats</div>
            <div class="text-3xl font-normal text-[#202124]">Unlimited</div>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="google-card !p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-sm font-medium text-gray-700">Members</h3>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" placeholder="Search staff" class="pl-9 pr-4 py-1.5 bg-white border border-gray-300 rounded-md text-xs focus:ring-2 focus:ring-blue-500 outline-none w-64">
            </div>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-[11px] uppercase font-bold text-gray-500 tracking-wider">
                    <th class="px-6 py-4 border-b border-gray-200">Name</th>
                    <th class="px-6 py-4 border-b border-gray-200">Email</th>
                    <th class="px-6 py-4 border-b border-gray-200">Role</th>
                    <th class="px-6 py-4 border-b border-gray-200">Status</th>
                    <th class="px-6 py-4 border-b border-gray-200 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700">
                @foreach($members as $member)
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&background=random&color=fff" class="w-8 h-8 rounded-full" alt="">
                            <span class="font-medium text-gray-900">{{ $member->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 border-b border-gray-100 text-gray-500">{{ $member->email }}</td>
                    <td class="px-6 py-4 border-b border-gray-100">
                        <span class="text-[11px] font-bold uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Staff</span>
                    </td>
                    <td class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs">Active</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 border-b border-gray-100 text-right">
                        <button class="p-2 text-gray-400 hover:text-blue-600 transition opacity-0 group-hover:opacity-100">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="p-2 text-gray-400 hover:text-red-600 transition opacity-0 group-hover:opacity-100">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Placeholder for Empty State -->
    @if(count($members) === 0)
    <div class="text-center py-20 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
        <h4 class="text-lg font-medium text-gray-900">No staff members yet</h4>
        <p class="text-sm text-gray-500 mb-6">Start building your empire by adding your team.</p>
        <button class="bg-[#1a73e8] text-white px-8 py-2 rounded-full text-sm font-medium hover:bg-blue-700">Add first member</button>
    </div>
    @endif

    <!-- YG Ads & Monetization (Economic Authority) -->
    <div class="mt-16 mb-12">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center shadow-sm">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-normal text-gray-900 tracking-tight">YG Ads & Monetization</h3>
                    <p class="text-sm text-gray-500">Scale your imperial influence and track ecosystem revenue</p>
                </div>
            </div>
            <a href="https://pay.ygxone.com/ads/create" class="bg-brand text-white px-8 py-3 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all flex items-center gap-2 shadow-md">
                <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-4 h-4 brightness-0 invert" alt="">
                Create via YG Pay
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Active Campaigns</div>
                <div class="text-2xl font-normal text-gray-900">4</div>
                <div class="mt-2 text-[10px] text-green-600 font-bold">+1 this week</div>
            </div>
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Total Impressions</div>
                <div class="text-2xl font-normal text-gray-900">1.2M</div>
                <div class="mt-2 text-[10px] text-brand font-bold">Real-time sync</div>
            </div>
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Total Clicks</div>
                <div class="text-2xl font-normal text-gray-900">45.2K</div>
                <div class="mt-2 text-[10px] text-gray-500 font-medium">3.7% Avg. CTR</div>
            </div>
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Earning (AdSense)</div>
                <div class="text-2xl font-normal text-gray-900">$1,450.00</div>
                <div class="mt-2 text-[10px] font-bold flex items-center gap-1">
                    @if(auth()->user()->kyc_status === 'verified')
                        <span class="text-green-600"><i class="fas fa-check-circle"></i> Ready to pay</span>
                    @else
                        <span class="text-red-500"><i class="fas fa-lock"></i> KYC Required</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Campaigns Table -->
        <div class="google-card !p-0 overflow-hidden border-green-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                        <th class="px-6 py-4">Campaign Name</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Engagement</th>
                        <th class="px-6 py-4 text-right">Budget Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-bullseye"></i>
                                </div>
                                <span class="text-sm font-bold text-gray-900">Imperial Product Launch</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="w-full bg-gray-100 rounded-full h-1.5 w-32">
                                <div class="bg-brand h-1.5 rounded-full" style="width: 65%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 block">850K Impressions</span>
                        </td>
                        <td class="px-6 py-5 text-right font-bold text-sm text-gray-900">$850.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Invite Modal (Simplified) -->
<div x-data="{ open: false }" 
     @open-invite-modal.window="open = true"
     x-show="open" 
     x-cloak 
     class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md p-8 shadow-2xl" @click.away="open = false">
        <h2 class="text-xl font-normal text-[#202124] mb-6">Add staff member</h2>
        <form action="{{ route('organization.invite') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex justify-end gap-4 pt-4">
                <button type="button" @click="open = false" class="px-6 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="bg-[#1a73e8] text-white px-8 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 shadow-md">Send Invite</button>
            </div>
        </form>
    </div>
</div>

@endsection
