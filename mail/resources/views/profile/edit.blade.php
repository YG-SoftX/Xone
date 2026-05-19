@extends('layouts.mail')
@section('title', 'Profile')

@section('mail-content')
<div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Profile Settings</h1>

    {{-- Update Profile Information --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Profile Information</h2>
        <p class="text-sm text-gray-500 mb-6">Update your account's profile information and email address.</p>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            @if ($mustVerifyEmail ?? false)
                <div class="px-4 py-3 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl text-sm">
                    Your email address is unverified.
                    <a href="{{ route('verification.send') }}" class="text-yellow-800 font-medium underline ml-1"
                       onclick="event.preventDefault(); document.getElementById('send-verification').submit();">
                        Click here to re-send the verification email.
                    </a>
                    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
                        @csrf
                    </form>
                </div>
            @endif

            @if(session('status') === 'verification-link-sent')
                <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white rounded-xl text-sm font-medium transition shadow-md">
                    Save
                </button>
                @if(session('profile_updated'))
                    <span class="text-sm text-emerald-600 flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> Saved.
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Update Password</h2>
        <p class="text-sm text-gray-500 mb-6">Ensure your account is using a long, random password to stay secure.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">New Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white rounded-xl text-sm font-medium transition shadow-md">
                    Save
                </button>
                @if(session('password_updated'))
                    <span class="text-sm text-emerald-600 flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> Saved.
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- Delete Account --}}
    <div class="bg-white border border-red-200 rounded-xl p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Delete Account</h2>
        <p class="text-sm text-gray-500 mb-6">Once your account is deleted, all of its resources and data will be permanently deleted.</p>

        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-5"
              onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
            @csrf
            @method('DELETE')

            <div>
                <label for="delete_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                <input type="password" id="delete_password" name="password" required
                       placeholder="Enter your password to confirm"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="px-6 py-2.5 bg-white border border-red-300 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition">
                <i class="fas fa-trash mr-1.5"></i> Delete Account
            </button>
        </form>
    </div>
</div>
@endsection
