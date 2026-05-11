<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->brand_name }} - Sovereign Communication</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #050505; color: white; overflow-x: hidden; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .crimson-gradient { background: radial-gradient(circle at 50% 50%, {{ $settings->primary_color }} 0%, #000 100%); }
        .hero-animate { animation: float 6s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .glow { box-shadow: 0 0 50px {{ $settings->primary_color }}44; }
    </style>
</head>
<body class="crimson-gradient min-h-screen flex items-center justify-center p-6">

    <!-- Top Navigation -->
    <nav class="absolute top-0 w-full p-8 flex justify-between items-center max-w-7xl">
        <div class="text-2xl font-bold tracking-tighter flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg" style="background: {{ $settings->primary_color }}"></div>
            {{ $settings->brand_name }}
        </div>
        <a href="/admin" class="glass px-6 py-2 rounded-full text-sm font-semibold hover:bg-white/10 transition">
            {{ $settings->cta_text }}
        </a>
    </nav>

    <!-- Hero Content -->
    <main class="text-center max-w-4xl space-y-8 relative">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-64 h-64 opacity-20 blur-[120px]" style="background: {{ $settings->primary_color }}"></div>
        
        <h1 class="text-6xl md:text-8xl font-bold tracking-tight leading-tight">
            {{ $settings->hero_title }}
        </h1>
        
        <p class="text-lg md:text-xl text-gray-400 max-w-2xl mx-auto leading-relaxed">
            {{ $settings->hero_subtitle ?? 'The next generation of encrypted, sovereign, and intelligent communication. Built for those who value privacy and power.' }}
        </p>

        <div class="pt-10 flex flex-col md:flex-row gap-4 justify-center items-center">
            <a href="/admin" class="px-10 py-5 rounded-2xl text-xl font-bold transition-all hover:scale-105 active:scale-95 glow flex items-center gap-3" style="background: {{ $settings->primary_color }}">
                {{ $settings->cta_text }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
            <div class="text-gray-500 text-sm italic">
                Secured by YGXONE SSO &bull; Encrypted End-to-End
            </div>
        </div>

        <!-- Floating UI Elements (Decorative) -->
        <div class="hidden lg:block absolute -left-48 top-0 glass p-6 rounded-3xl w-64 hero-animate opacity-50">
            <div class="h-2 w-12 rounded bg-gray-700 mb-4"></div>
            <div class="space-y-2">
                <div class="h-2 w-full rounded bg-gray-800"></div>
                <div class="h-2 w-3/4 rounded bg-gray-800"></div>
            </div>
        </div>
        <div class="hidden lg:block absolute -right-48 bottom-0 glass p-6 rounded-3xl w-64 hero-animate delay-75 opacity-50">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-full bg-gray-700"></div>
                <div class="h-2 w-24 rounded bg-gray-700"></div>
            </div>
            <div class="h-2 w-full rounded bg-gray-800"></div>
        </div>
    </main>

    <footer class="absolute bottom-8 text-gray-600 text-xs tracking-widest uppercase">
        &copy; 2026 YGXONE Systems &bull; Built with Laravel & Filament
    </footer>

</body>
</html>
