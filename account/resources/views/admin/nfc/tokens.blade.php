@extends('admin.master-layout')
@section('title', 'NFC Terminal Management')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">📡 NFC Terminal Management</h1>
            <p class="text-indigo-200">Monitor and manage physical payment devices and tokens across the YG network.</p>
        </div>
        <div class="flex gap-4">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-plus"></i> Provision New Terminal
            </button>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        @foreach([
            ['label' => 'Registered Terminals', 'value' => $stats['total'], 'icon' => 'fa-microchip', 'gradient' => 'from-blue-600 to-cyan-500'],
            ['label' => 'Active Devices', 'value' => $stats['active'], 'icon' => 'fa-signal', 'gradient' => 'from-emerald-600 to-teal-500'],
            ['label' => '24h Transactions', 'value' => $stats['total_transactions'], 'icon' => 'fa-exchange-alt', 'gradient' => 'from-indigo-600 to-purple-500'],
            ['label' => 'Processing Volume', 'value' => '$' . number_format($stats['total_volume'], 2), 'icon' => 'fa-dollar-sign', 'gradient' => 'from-amber-600 to-orange-500'],
        ] as $stat)
        <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $stat['gradient'] }} flex items-center justify-center shadow-lg">
                    <i class="fas {{ $stat['icon'] }} text-white text-xl"></i>
                </div>
            </div>
            <p class="text-slate-400 text-sm font-medium">{{ $stat['label'] }}</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ $stat['value'] }}</h3>
        </div>
        @endforeach
    </div>

    {{-- Main Table Section --}}
    <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/30">
            <h3 class="text-xl font-bold text-white">Device Registry</h3>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input type="text" placeholder="Search token UID..." 
                           class="bg-slate-900/50 border border-slate-700 text-white text-sm rounded-xl px-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                    <i class="fas fa-search absolute left-4 top-3 text-slate-500 text-xs"></i>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-900/30 text-slate-400 text-xs uppercase tracking-widest font-semibold">
                        <th class="px-8 py-5">Terminal Identity</th>
                        <th class="px-8 py-5">Owner / Account</th>
                        <th class="px-8 py-5">Hardware Specs</th>
                        <th class="px-8 py-5">Transaction Limits</th>
                        <th class="px-8 py-5">Network Status</th>
                        <th class="px-8 py-5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    @foreach($tokens as $token)
                    <tr class="hover:bg-slate-700/20 transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <span class="text-white font-mono font-medium text-sm">{{ $token->token_uid }}</span>
                                <span class="text-indigo-400 text-[10px] font-bold uppercase mt-1 tracking-tighter">SECURE CHIP V2</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-indigo-100 text-sm font-medium">
                            {{ $token->user->name }}
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <i class="fas {{ $token->device_type == 'mobile' ? 'fa-mobile-alt' : 'fa-cash-register' }} text-slate-500"></i>
                                <span class="text-slate-300 text-sm">{{ $token->device_name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-xs text-slate-400">
                                <div class="flex justify-between mb-1"><span>TX:</span> <span class="text-white ml-2">${{ number_format($token->transaction_limit, 2) }}</span></div>
                                <div class="flex justify-between"><span>Day:</span> <span class="text-white ml-2">${{ number_format($token->daily_limit, 2) }}</span></div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            @if($token->is_active)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-bold uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Blocked
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('admin.nfc.toggle', $token->id) }}">
                                    @csrf
                                    <button class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-indigo-600 text-slate-300 hover:text-white transition-all duration-200 flex items-center justify-center">
                                        <i class="fas fa-power-off text-xs"></i>
                                    </button>
                                </form>
                                <button class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600 text-slate-300 hover:text-white transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-cog text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($tokens->hasPages())
        <div class="px-8 py-6 bg-slate-900/20 border-t border-slate-700/50">
            {{ $tokens->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
