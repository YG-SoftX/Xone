@extends('layouts.app')
@section('title', 'Upgrade Your Plan')
@section('page-title', 'Plans & Storage')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Upgrade reason banner --}}
    @if(session('upgrade_reason'))
    <div class="mb-6 flex items-center gap-3 bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-4 text-sm text-yellow-800">
        <i class="fas fa-lock text-yellow-500"></i>
        {{ session('upgrade_reason') }}
    </div>
    @endif

    {{-- Current plan + storage --}}
    <div class="mb-10 bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fas fa-{{ $current === 'personal' ? 'user' : ($current === 'business' ? 'building' : 'crown') }}"></i>
            </div>
            <div>
                <p class="font-bold text-gray-900 text-lg">{{ ucfirst($current) }} Plan</p>
                <p class="text-sm text-gray-500">
                    {{ $current === 'personal' ? 'Free forever' : '$' . number_format($plans[$current]['price_monthly'] ?? 0, 2) . '/user/month' }}
                </p>
            </div>
        </div>

        {{-- Storage bar --}}
        <div class="w-full sm:w-72">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Storage used</span>
                <span>{{ $quota->used_for_humans }} / {{ $quota->quota_for_humans }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ $quota->used_percent > 90 ? 'bg-red-500' : ($quota->used_percent > 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                     style="width: {{ min($quota->used_percent, 100) }}%"></div>
            </div>
            @if($quota->used_percent > 80)
            <p class="text-xs text-red-500 mt-1">{{ round($quota->used_percent) }}% full — consider upgrading</p>
            @endif
        </div>
    </div>

    {{-- Plan comparison --}}
    <h2 class="text-xl font-bold text-gray-900 mb-6">Choose Your Plan</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        @foreach(['personal', 'business', 'enterprise'] as $planKey)
        @php $plan = $plans[$planKey]; $isCurrent = $current === $planKey; @endphp
        <div class="relative bg-white rounded-2xl border-2 {{ $planKey === 'business' ? 'border-blue-500 shadow-lg' : 'border-gray-200' }} p-6 flex flex-col">
            @if($planKey === 'business')
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most Popular</div>
            @endif

            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-900">{{ $plan['name'] }}</h3>
                <div class="mt-1 flex items-baseline gap-1">
                    @if($plan['price_monthly'] === 0)
                    <span class="text-3xl font-bold text-gray-900">Free</span>
                    @else
                    <span class="text-3xl font-bold text-gray-900">${{ $plan['price_monthly'] }}</span>
                    <span class="text-sm text-gray-500">/user/mo</span>
                    @endif
                </div>
            </div>

            {{-- Key limits --}}
            <div class="space-y-2 mb-6 text-sm text-gray-600 flex-1">
                <div class="flex items-center gap-2">
                    <i class="fas fa-hdd text-gray-400 w-4"></i>
                    {{ \App\Models\StorageQuota::formatBytes($plan['storage_bytes']) }} storage
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-envelope text-gray-400 w-4"></i>
                    {{ number_format($plan['mail_daily_limit']) }} emails/day
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-robot text-gray-400 w-4"></i>
                    {{ number_format($plan['ai_queries_daily']) }} AI queries/day
                </div>
                <hr class="my-3">
                @foreach($plan['features'] as $feature => $enabled)
                @php $label = str_replace('_', ' ', ucfirst($feature)); @endphp
                <div class="flex items-center gap-2 {{ $enabled ? '' : 'opacity-40' }}">
                    <i class="fas fa-{{ $enabled ? 'check text-green-500' : 'times text-gray-300' }} w-4"></i>
                    {{ $label }}
                </div>
                @endforeach
            </div>

            {{-- CTA --}}
            @if($isCurrent)
            <div class="text-center py-2.5 rounded-xl border-2 border-green-200 text-green-700 text-sm font-semibold">
                <i class="fas fa-check mr-2"></i>Current Plan
            </div>
            @elseif($planKey === 'personal')
            @if($current !== 'personal')
            <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('Downgrade to Free plan?')">
                @csrf
                <button class="w-full py-2.5 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">
                    Downgrade to Free
                </button>
            </form>
            @endif
            @else
            <form method="POST" action="{{ route('billing.subscribe') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $planKey }}">
                <button class="w-full py-2.5 rounded-xl {{ $planKey === 'business' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-900 text-white hover:bg-gray-800' }} text-sm font-semibold transition">
                    Upgrade to {{ $plan['name'] }}
                </button>
            </form>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Storage add-ons --}}
    <h2 class="text-xl font-bold text-gray-900 mb-4">Storage Add-ons</h2>
    <p class="text-sm text-gray-500 mb-6">Need more space without upgrading your plan? Add storage packs anytime.</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach($plans['storage_packs'] as $packKey => $pack)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 flex flex-col">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i class="fas fa-hdd"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-900">{{ $pack['label'] }}</p>
                    <p class="text-sm text-gray-500">${{ $pack['price_monthly'] }}/month</p>
                </div>
            </div>
            <form method="POST" action="{{ route('billing.storage-pack') }}" class="mt-auto">
                @csrf
                <input type="hidden" name="storage_pack" value="{{ $packKey }}">
                <button class="w-full bg-indigo-600 text-white text-sm font-semibold py-2 rounded-xl hover:bg-indigo-700 transition">
                    Add {{ $pack['label'] }}
                </button>
            </form>
        </div>
        @endforeach
    </div>

</div>
@endsection
