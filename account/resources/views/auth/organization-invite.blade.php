@extends('layouts.guest')
@section('title', 'Join Organization')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-[#f8f9fa]">
    <div class="w-full max-w-md animate-fade-in-up">
        
        <!-- Welcome Card -->
        <div class="bg-white rounded-3xl p-10 border border-gray-200 shadow-xl text-center">
            
            <!-- Org Identity -->
            <div class="mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4 shadow-lg">
                    {{ strtoupper(substr($organizationName ?? 'O', 0, 1)) }}
                </div>
                <h1 class="text-2xl font-normal text-[#202124]">Join {{ $organizationName ?? 'Your Business' }}</h1>
                <p class="text-sm text-gray-500 mt-2">You've been invited to join the YGXone ecosystem as a staff member.</p>
            </div>

            <!-- Perks Summary -->
            <div class="bg-blue-50/50 rounded-2xl p-6 text-left mb-8 space-y-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-blue-600 mt-1"></i>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Shared Storage</div>
                        <div class="text-[10px] text-gray-500">Access your organization's Drive and shared files.</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-blue-600 mt-1"></i>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Unified Mail</div>
                        <div class="text-[10px] text-gray-500">Get your official @company.ygxone.com email address.</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-blue-600 mt-1"></i>
                    <div>
                        <div class="text-xs font-bold text-gray-900">Empire Treasury</div>
                        <div class="text-[10px] text-gray-500">Receive payments and rewards directly to your wallet.</div>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <form action="{{ route('register') }}" method="GET">
                <input type="hidden" name="type" value="member">
                <input type="hidden" name="org" value="{{ $organizationId ?? 1 }}">
                <button type="submit" class="w-full py-3 bg-[#1a73e8] text-white rounded-full text-sm font-bold shadow-md hover:bg-blue-700 mb-6 transition-all">
                    Accept Invitation & Create Account
                </button>
            </form>

            <div class="text-[11px] text-gray-400">
                Already have a YG Account? <a href="{{ route('login') }}" class="text-[#1a73e8] font-bold hover:underline">Sign in to join</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 flex justify-center gap-6 text-[10px] text-gray-400 font-bold uppercase tracking-widest">
            <a href="#" class="hover:text-gray-600">Privacy</a>
            <a href="#" class="hover:text-gray-600">Terms</a>
            <a href="#" class="hover:text-gray-600">Help</a>
        </div>

    </div>
</div>

<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.6s ease-out; }
</style>
@endsection
