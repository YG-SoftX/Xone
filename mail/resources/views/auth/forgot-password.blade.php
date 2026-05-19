@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-2xl mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Reset your password</h2>
            <p class="mt-2 text-gray-500">Enter your email and we'll send you a reset link</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                @if (session('status'))
                    <div class="p-4 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="block w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none transition text-sm @error('email') border-red-300 @enderror"
                           placeholder="you@example.com">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 rounded-xl bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold text-sm shadow-sm hover:shadow-md hover:scale-[1.01] active:scale-[0.99] transition-all focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Send reset link
                </button>
            </form>

            <p class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                    Back to sign in
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
