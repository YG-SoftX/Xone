@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-600 rounded-2xl mb-6 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
            <p class="mt-2 text-gray-500">Sign in to your mail account</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="block w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none transition text-sm @error('email') border-red-300 @enderror"
                           placeholder="you@example.com">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="block w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none transition text-sm @error('password') border-red-300 @enderror"
                           placeholder="••••••••">
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-red-600 focus:ring-red-500" {{ old('remember') ? 'checked' : '' }}>
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-red-600 hover:text-red-700 transition">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 rounded-xl bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold text-sm shadow-sm hover:shadow-md hover:scale-[1.01] active:scale-[0.99] transition-all focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Sign in
                </button>
            </form>

            <!-- Status Messages -->
            @if (session('status'))
                <div class="mt-4 p-4 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        <!-- Register Link -->
        <p class="mt-8 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-red-600 hover:text-red-700 transition">Create one</a>
        </p>
    </div>
</div>
@endsection
