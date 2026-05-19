@extends('layouts.app')
@section('title', 'Sign In')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center text-2xl font-black"
                 style="background:var(--crimson)">
                <i class="fas fa-headset"></i>
            </div>
            <h1 class="text-2xl font-bold">YG Support</h1>
            <p class="text-sm mt-1" style="color:#9b8e90">Sign in with your YG Account</p>
        </div>

        <div class="rounded-2xl p-8" style="background:rgba(255,255,255,0.02);border:1px solid var(--border)">
            @if($errors->any())
                <div class="mb-6 px-4 py-3 rounded-lg text-sm" style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
                    {{ $errors->first() }}
                </div>
            @endif

            <a href="{{ route('sso.redirect') }}"
               class="flex items-center justify-center gap-3 w-full py-3 px-6 rounded-xl font-semibold transition-all hover:opacity-90"
               style="background:var(--crimson);color:#fff">
                <i class="fas fa-key"></i>
                Sign in with YG Account
            </a>

            <p class="text-xs text-center mt-4" style="color:#4a4044">
                By signing in, you agree to the YGXONE Terms of Service.
            </p>
        </div>
    </div>
</div>
@endsection
