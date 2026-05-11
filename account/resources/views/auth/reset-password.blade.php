@extends('layouts.app')
@section('title', 'Change password - YG Accounts')

@section('content')
<div class="google-card">
    <div class="logo-box">
        <div class="logo-text">YG<span>ONE</span></div>
    </div>

    <div class="text-center mb-8">
        <h1 style="font-size: 24px; font-weight: 400; margin: 0 0 12px 0;">Create a strong password</h1>
        <p style="font-size: 14px; color: #3c4043;">Create a new, strong password that you don't use for other websites</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="google-input-wrapper">
            <input type="email" name="email" id="email" value="{{ old('email', $request->email) }}" class="google-input" required readonly>
            <label for="email" class="google-label">Email Address</label>
        </div>

        <div class="google-input-wrapper">
            <input type="password" name="password" id="password" placeholder=" " class="google-input" required autofocus>
            <label for="password" class="google-label">Create password</label>
        </div>

        <div class="google-input-wrapper mb-8">
            <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " class="google-input" required>
            <label for="password_confirmation" class="google-label">Confirm password</label>
        </div>

        <div class="btn-container" style="justify-content: flex-end;">
            <button type="submit" class="btn-primary">
                Change password
            </button>
        </div>
    </form>
</div>
@endsection
