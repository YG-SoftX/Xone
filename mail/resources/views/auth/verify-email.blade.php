@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-50 rounded-2xl mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-yellow-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Verify your email</h2>
            <p class="mt-2 text-gray-500">Thanks for signing up! Please verify your email address.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            @if (session('status') == 'verification-link-sent')
                <div class="p-4 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700 mb-6">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Before getting started, could you verify your email address by clicking the link we just emailed to you? If you didn't receive the email, we can send another.
            </p>

            <div class="space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 rounded-xl bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold text-sm shadow-sm hover:shadow-md hover:scale-[1.01] active:scale-[0.99] transition-all focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Resend verification email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-center py-3 px-4 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
