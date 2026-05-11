@extends('layouts.app')
@section('title', 'Sign in - YG Accounts')

@section('content')
<div class="google-card" x-data="{ step: 1, email: '', password: '', showPass: false }">
    <div class="logo-box">
        <div class="logo-text">YG<span>ONE</span></div>
    </div>

    <div class="text-center mb-8">
        <h1 style="font-size: 24px; font-weight: 400; margin: 0 0 8px 0;" x-text="step === 1 ? 'Sign in' : 'Welcome'"></h1>
        
        <!-- Step 1 Text -->
        <div x-show="step === 1" style="font-size: 16px;">Use your YG Account</div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div style="margin-top: 16px; padding: 10px; border-radius: 4px; background-color: #fce8e6; color: #d93025; font-size: 14px; display: flex; align-items: center; text-align: left;">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if (session('status'))
            <div style="margin-top: 16px; padding: 10px; border-radius: 4px; background-color: #e8f0fe; color: #1967d2; font-size: 14px;">
                {{ session('status') }}
            </div>
        @endif
        
        <!-- Step 2 Account Switcher -->
        <div x-show="step === 2" x-cloak style="margin-top: 8px; display: inline-flex; align-items: center; border: 1px solid #dadce0; border-radius: 100px; padding: 5px 12px; font-size: 14px; font-weight: 500; cursor: pointer;" @click="step = 1">
            <i class="fas fa-user-circle" style="margin-right: 8px; font-size: 18px; color: #5f6368;"></i>
            <span x-text="email"></span>
            <i class="fas fa-chevron-down" style="margin-left: 8px; font-size: 10px; color: #5f6368;"></i>
        </div>
    </div>

    <form action="{{ route('login') }}" method="POST">
        @csrf
        
        <!-- Step 1: Email -->
        <div x-show="step === 1">
            <div class="google-input-wrapper">
                <input type="text" name="email" x-model="email" id="email" placeholder=" " class="google-input" required autofocus @keydown.enter.prevent="if(email) step = 2">
                <label for="email" class="google-label">Email or username</label>
            </div>
            
            <div style="font-size: 14px; color: #5f6368; line-height: 1.4; margin-bottom: 40px;">
                Not your computer? Use Guest mode to sign in privately. <a href="#" style="color: #1a73e8; font-weight: 500; text-decoration: none;">Learn more</a>
            </div>
        </div>

        <!-- Step 2: Password -->
        <div x-show="step === 2" x-cloak>
            <div class="google-input-wrapper">
                <input :type="showPass ? 'text' : 'password'" name="password" x-model="password" id="password" placeholder=" " class="google-input" required autofocus>
                <label for="password" class="google-label">Enter your password</label>
            </div>
            
            <div style="display: flex; align-items: center; margin-bottom: 40px;">
                <input type="checkbox" id="show-pass" x-model="showPass" style="width: 18px; height: 18px; cursor: pointer; margin-right: 10px;">
                <label for="show-pass" style="font-size: 14px; cursor: pointer; user-select: none;">Show password</label>
            </div>
        </div>

        <div class="btn-container">
            <div style="position: relative;" x-data="{ open: false }" @click.away="open = false">
                <button type="button" x-show="step === 1" @click="open = !open" class="btn-secondary">
                    Create account
                </button>
                <div x-show="open" x-cloak style="position: absolute; bottom: 100%; left: 0; margin-bottom: 8px; width: 220px; background: #fff; border: 1px solid #dadce0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 100; padding: 8px 0;">
                    <a href="{{ route('register.individual') }}" style="display: block; padding: 12px 16px; color: #3c4043; text-decoration: none; font-size: 14px;">For my personal use</a>
                    <a href="{{ route('register.business') }}" style="display: block; padding: 12px 16px; color: #3c4043; text-decoration: none; font-size: 14px;">For work or my business</a>
                </div>
                
                <a x-show="step === 2" x-cloak href="{{ route('password.request') }}" class="btn-secondary">
                    Forgot password?
                </a>
            </div>

            <button type="button" x-show="step === 1" @click="if(email) step = 2" class="btn-primary">
                Next
            </button>
            <button type="submit" x-show="step === 2" x-cloak class="btn-primary">
                Sign in
            </button>
        </div>
    </form>
</div>
@endsection
