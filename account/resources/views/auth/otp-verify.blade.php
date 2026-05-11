@extends('layouts.app')
@section('title', 'Security Checkpoint')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full google-card p-10">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-full text-blue-600 mb-6">
                <i class="fas fa-user-shield text-2xl"></i>
            </div>
            <h2 class="text-2xl font-normal text-gray-900 mb-2">Security Checkpoint</h2>
            <p class="text-sm text-gray-600">We've sent a 6-digit verification code to your email. Please enter it below to authorize this action.</p>
        </div>

        <form method="POST" action="{{ route('otp.verify.submit') }}" class="space-y-8">
            @csrf
            <input type="hidden" name="action_url" value="{{ request('action_url') }}">

            <div class="text-center">
                <div class="flex justify-center gap-2" x-data="{ 
                    otp: ['', '', '', '', '', ''],
                    handleInput(index, val) {
                        if (val.length > 1) val = val.slice(-1);
                        this.otp[index] = val;
                        if (val && index < 5) $refs['input' + (index + 1)].focus();
                    },
                    handleBackspace(index, e) {
                        if (e.key === 'Backspace' && !this.otp[index] && index > 0) $refs['input' + (index - 1)].focus();
                    }
                }">
                    @foreach(range(0, 5) as $i)
                        <input type="text" 
                               maxlength="1" 
                               x-ref="input{{ $i }}"
                               @input="handleInput({{ $i }}, $event.target.value)"
                               @keydown="handleBackspace({{ $i }}, $event)"
                               class="w-10 h-12 border border-gray-300 rounded text-center text-xl font-medium focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none" 
                               placeholder="0">
                    @endforeach
                    <input type="hidden" name="code" :value="otp.join('')">
                </div>
                @error('code')
                    <p class="mt-4 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-4">
                <button type="submit" class="w-full btn-primary">
                    Authorize Action
                </button>
                
                <div class="text-center">
                    <button type="button" class="text-sm text-blue-600 font-medium hover:underline" onclick="window.location.reload()">
                        Resend Code
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
