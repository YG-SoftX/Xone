<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable — YG Account</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @keyframes pulse-slow { 0%,100%{opacity:1} 50%{opacity:.4} }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        .animate-pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }
        .animate-float { animation: float 4s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center px-4">

    <div class="text-center max-w-xl">
        <!-- Animated Icon -->
        <div class="animate-float inline-block mb-8">
            <div class="w-28 h-28 mx-auto bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl flex items-center justify-center shadow-2xl shadow-blue-900/50">
                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="flex justify-center mb-4">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-yellow-900/40 border border-yellow-700/50 rounded-full">
                <span class="w-2 h-2 bg-yellow-400 rounded-full animate-pulse-slow"></span>
                <span class="text-yellow-400 text-xs font-semibold uppercase tracking-widest">Maintenance Mode</span>
            </span>
        </div>

        <!-- Heading -->
        <h1 class="text-5xl font-black text-white mb-4 leading-tight">
            We'll be right<br>
            <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">back soon.</span>
        </h1>

        <!-- Description -->
        <p class="text-gray-400 text-lg leading-relaxed mb-8">
            YG Account is currently undergoing scheduled maintenance.<br>
            We're working hard to bring you an even better experience.
        </p>

        <!-- Estimated Time (optional — uncomment when known) -->
        {{-- <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 mb-8 inline-block">
            <p class="text-gray-400 text-sm mb-1">Estimated completion</p>
            <p class="text-white font-bold text-xl">~30 minutes</p>
        </div> --}}

        <!-- Divider -->
        <div class="border-t border-gray-800 mb-8"></div>

        <!-- Contact / Status -->
        <p class="text-gray-500 text-sm">
            Questions? Contact support at
            <a href="mailto:support@ygsoft.com" class="text-blue-400 hover:text-blue-300 underline underline-offset-2 transition">
                support@ygsoft.com
            </a>
        </p>

        <!-- Brand -->
        <div class="mt-10">
            <p class="text-gray-700 text-xs font-medium uppercase tracking-widest">YG Soft — Platform Services</p>
        </div>
    </div>

    <!-- Subtle background blobs -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-900/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-900/20 rounded-full blur-3xl"></div>
    </div>
</body>
</html>
