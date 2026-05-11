@extends('layouts.app')
@section('title', 'Complete Payment')
@section('page-title', 'Complete Payment')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Order summary --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>{{ $plan['name'] }} Plan</span>
                <span>${{ number_format($amount, 2) }}/month</span>
            </div>
            <div class="border-t border-gray-200 pt-3 flex justify-between font-bold text-gray-900">
                <span>Total today</span>
                <span>${{ number_format($amount, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Stripe payment element placeholder --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-lock text-gray-400 mr-2"></i>Payment Details
        </h2>

        @if($provider === 'stripe')
        {{-- Stripe Elements will mount here --}}
        <div id="stripe-payment-element" class="mb-4">
            <div class="border border-gray-300 rounded-xl p-4 bg-gray-50 text-sm text-gray-500 text-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>Loading payment form...
            </div>
        </div>

        <button id="stripe-submit"
                class="w-full bg-blue-600 text-white font-semibold py-3 rounded-xl hover:bg-blue-700 transition flex items-center justify-center gap-2">
            <i class="fas fa-lock text-xs"></i>
            Pay ${{ number_format($amount, 2) }} Securely
        </button>

        <div id="stripe-error" class="hidden mt-3 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3"></div>
        @else
        {{-- Fallback for other providers --}}
        <div class="text-center py-6">
            <a href="{{ route('billing.confirm') }}"
               class="inline-block bg-blue-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-blue-700 transition">
                Confirm Payment →
            </a>
        </div>
        @endif
    </div>

    {{-- Trust indicators --}}
    <div class="text-center text-xs text-gray-400 space-y-1">
        <p><i class="fas fa-shield-alt mr-1 text-green-500"></i> Payments are encrypted and secured</p>
        <p>Cancel anytime · No hidden fees</p>
    </div>

</div>

@if($provider === 'stripe' && isset($client_secret))
@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(async () => {
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const clientSecret = '{{ $client_secret }}';

    const elements = stripe.elements({ clientSecret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#stripe-payment-element');

    const submitBtn = document.getElementById('stripe-submit');
    const errorDiv = document.getElementById('stripe-error');

    submitBtn.addEventListener('click', async () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: '{{ route('billing.confirm') }}',
            },
        });

        if (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock text-xs"></i> Pay ${{ number_format($amount, 2) }} Securely';
        }
    });
})();
</script>
@endpush
@endif
@endsection
