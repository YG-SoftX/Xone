@extends('layouts.app')
@section('title', 'Billing')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold">Billing</h2>
    <a href="{{ route('billing.invoices') }}"
       class="px-4 py-2 rounded-xl text-sm hover:bg-white/5"
       style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">View all invoices</a>
</div>

@php $acct = $account ?? []; @endphp

{{-- Balance card --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-xs mb-2" style="color:#9b8e90">Current Balance</div>
        <div class="text-3xl font-bold">${{ number_format($acct['current_balance']??0, 2) }}</div>
        <div class="text-xs mt-1" style="color:{{ ($acct['current_balance']??0)<0?'#ff6b6b':'#10b981' }}">
            {{ ($acct['status']??'active') === 'active' ? 'In good standing' : 'Account suspended' }}
        </div>
    </div>
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-xs mb-2" style="color:#9b8e90">Total Spent</div>
        <div class="text-3xl font-bold">${{ number_format($acct['total_spent']??0, 2) }}</div>
    </div>
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-xs mb-2" style="color:#9b8e90">This Period Estimated</div>
        <div class="text-3xl font-bold">${{ number_format(collect($usage['usage']??[])->sum('estimated_cost'), 2) }}</div>
    </div>
</div>

{{-- Billing account details --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <h3 class="text-sm font-semibold mb-4">Billing Details</h3>
        <form method="POST" action="{{ route('billing.update') }}" class="space-y-4">
            @csrf @method('PUT')
            @foreach(['company_name'=>'Company Name','tax_id'=>'Tax ID','address'=>'Address','city'=>'City','country'=>'Country (2-char)','postal_code'=>'Postal Code'] as $field=>$label)
            <div>
                <label class="block text-xs mb-1" style="color:#9b8e90">{{ $label }}</label>
                <input type="text" name="{{ $field }}" value="{{ $acct[$field]??'' }}"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>
            @endforeach
            <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold hover:opacity-90"
                    style="background:#ff003c;color:#fff">Save</button>
        </form>
    </div>

    {{-- Payment methods --}}
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <h3 class="text-sm font-semibold mb-4">Payment Methods</h3>
        @forelse($acct['payment_methods']??[] as $pm)
        <div class="flex items-center justify-between py-3 border-b" style="border-color:rgba(255,255,255,0.04)">
            <div class="flex items-center gap-3">
                <div class="text-lg">💳</div>
                <div>
                    <div class="text-sm">{{ ucfirst($pm['brand']??'Card') }} ···· {{ $pm['last_four'] }}</div>
                    @if($pm['exp_month']??null)
                    <div class="text-xs" style="color:#9b8e90">{{ $pm['exp_month'] }}/{{ $pm['exp_year'] }}</div>
                    @endif
                </div>
                @if($pm['id']===$acct['primary_payment_method_id']??null)
                <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgba(16,185,129,0.15);color:#10b981">Default</span>
                @endif
            </div>
            <form method="POST" action="{{ route('billing.payment.remove', $pm['id']) }}">
                @csrf @method('DELETE')
                <button class="text-xs hover:underline" style="color:#ff6b6b">Remove</button>
            </form>
        </div>
        @empty
        <p class="text-sm" style="color:#9b8e90">No payment methods.</p>
        @endforelse
        <form method="POST" action="{{ route('billing.payment.add') }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="type" value="card">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1" style="color:#9b8e90">Last 4 digits</label>
                    <input type="text" name="last_four" maxlength="4" placeholder="4242"
                           class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                           style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:#9b8e90">Brand</label>
                    <input type="text" name="brand" placeholder="Visa"
                           class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                           style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="set_default" value="1" style="accent-color:#ff003c">
                Set as default
            </label>
            <button type="submit" class="px-4 py-2 rounded-xl text-sm hover:bg-white/5"
                    style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">+ Add Card</button>
        </form>
    </div>
</div>

{{-- Recent invoices --}}
<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="px-6 py-4 text-sm font-semibold" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">Recent Invoices</div>
    @forelse($invoices['data']??[] as $inv)
    <div class="px-6 py-4 border-b last:border-0 flex items-center justify-between"
         style="border-color:rgba(255,255,255,0.04)">
        <div>
            <div class="text-sm font-medium">{{ $inv['number'] ?? '#'.$inv['id'] }}</div>
            <div class="text-xs mt-0.5" style="color:#9b8e90">{{ $inv['due_date']??'' }}</div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold">${{ number_format($inv['amount']??0,2) }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full"
                  style="{{ $inv['status']==='paid'?'background:rgba(16,185,129,0.15);color:#10b981':'background:rgba(245,158,11,0.15);color:#f59e0b' }}">
                {{ ucfirst($inv['status']??'open') }}
            </span>
            @if(($inv['status']??'')!=='paid')
            <form method="POST" action="{{ route('billing.pay', $inv['id']) }}">
                @csrf
                <button class="text-xs px-3 py-1.5 rounded-lg hover:opacity-90"
                        style="background:#ff003c;color:#fff">Pay</button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="px-6 py-8 text-center text-sm" style="color:#9b8e90">No invoices.</div>
    @endforelse
</div>

@endsection
