@extends('layouts.app')
@section('title', 'Verify Your Identity')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-50 rounded-3xl text-blue-600 mb-6 shadow-xl shadow-blue-600/5">
                <i class="fas fa-paper-plane text-3xl"></i>
            </div>
            <h1 class="text-4xl font-black text-gray-900 font-google tracking-tight mb-3">Check Your Inbox</h1>
            <p class="text-gray-500 text-lg">We've sent a verification link to <span class="font-bold text-gray-900">{{ auth()->user()->email }}</span>. Please click the link to activate your YG Ecosystem access.</p>
        </div>

        <div class="bg-white p-10 rounded-[3rem] shadow-2xl shadow-blue-900/5 border border-gray-100">
            @if (session('status') == 'verification-link-sent')
                <div class="mb-8 p-4 bg-green-50 rounded-2xl border border-green-100 text-green-700 text-sm font-bold flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    A new link has been dispatched to your email!
                </div>
            @endif

            <div class="space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full py-5 bg-gradient-to-r from-blue-600 to-purple-700 text-white rounded-2xl font-bold text-lg shadow-xl shadow-blue-600/20 hover:scale-[1.02] active:scale-95 transition">
                        Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full py-4 text-gray-500 font-bold hover:text-gray-900 transition">
                        Log Out & Exit
                    </button>
                </form>
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-400 font-medium uppercase tracking-[0.2em]">
            Secured by YG Guard Security System
        </p>
    </div>
</div>
@endsection
