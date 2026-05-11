@extends('layouts.app')
@section('title', 'Account recovery - YG Accounts')

@section('content')
<div class="google-card">
    <div class="logo-box">
        <div class="logo-text">YG<span>ONE</span></div>
    </div>

    <div class="text-center mb-8">
        <h1 style="font-size: 24px; font-weight: 400; margin: 0 0 12px 0;">Account recovery</h1>
        <p style="font-size: 14px; color: #3c4043; line-height: 1.5;">
            To help keep your account secure, YGXONE wants to make sure it's really you trying to sign in. Enter your email to receive a password reset link.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        
        <div class="google-input-wrapper mb-8">
            <input type="email" name="email" id="email" placeholder=" " class="google-input" required autofocus>
            <label for="email" class="google-label">Email Address</label>
        </div>

        @if (session('status'))
            <div style="padding: 12px; background: #e6f4ea; color: #1e8e3e; border-radius: 4px; font-size: 14px; margin-bottom: 24px;">
                {{ session('status') }}
            </div>
        @endif

        <div class="btn-container" style="justify-content: flex-end; gap: 12px;">
            <a href="{{ route('login') }}" class="btn-secondary">Back to Sign in</a>
            <button type="submit" class="btn-primary">
                Send Link
            </button>
        </div>
    </form>
</div>
@endsection
