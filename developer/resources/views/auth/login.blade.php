<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — YG Developer</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="h-full font-sans antialiased flex items-center justify-center min-h-screen"
      style="background:#020202;color:#f5f0f1">

<div class="w-full max-w-sm px-6">
    {{-- Brand --}}
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5 text-xl font-black"
             style="background:#ff003c">YG</div>
        <h1 class="text-2xl font-bold mb-1">YG Developer</h1>
        <p class="text-sm" style="color:#9b8e90">Build on the YGXone ecosystem</p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl text-sm text-center"
             style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- SSO Card --}}
    <div class="rounded-2xl p-6 mb-4" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06)">
        <p class="text-sm mb-6 text-center" style="color:#9b8e90">
            Use your YG Account to access the developer console. No separate password needed.
        </p>
        <a href="{{ route('sso.redirect') }}"
           class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all hover:opacity-90 active:scale-95"
           style="background:#ff003c;color:#fff">
            <span>🔐</span>
            Continue with YG Account
        </a>
    </div>

    {{-- Features --}}
    <div class="grid grid-cols-3 gap-3 mt-8">
        @foreach([['⚡','Fast APIs'],['🔑','Secure keys'],['📊','Analytics']] as [$icon,$label])
        <div class="rounded-xl p-3 text-center" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04)">
            <div class="text-xl mb-1">{{ $icon }}</div>
            <div class="text-xs" style="color:#9b8e90">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    <p class="text-center mt-8 text-xs" style="color:#4a4044">
        © {{ date('Y') }} YGXone — All rights reserved
    </p>
</div>

</body>
</html>
