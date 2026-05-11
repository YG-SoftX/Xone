@extends('layouts.app')

@section('content')
<div class="relative min-h-screen overflow-hidden bg-[#020617]">
    <!-- Background Cinematic Glows -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600 rounded-full blur-[150px] opacity-20"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-600 rounded-full blur-[150px] opacity-20"></div>

    <!-- Navigation Overlay -->
    <nav class="relative z-20 max-w-7xl mx-auto px-6 py-8 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-cube text-white"></i>
            </div>
            <span class="text-2xl font-black text-white tracking-tighter">YGXONE</span>
        </div>
        <div class="flex items-center space-x-8">
            <a href="{{ route('login') }}" class="text-gray-400 font-bold hover:text-white transition-colors uppercase text-sm tracking-widest">{{ __("Login") }}</a>
            <a href="{{ route('register.individual') }}" class="px-6 py-3 bg-white text-black font-black rounded-xl hover:bg-blue-400 transition-all uppercase text-sm tracking-widest shadow-xl">{{ __("Join Empire") }}</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 pt-20 pb-32 flex flex-col items-center text-center">
        <div class="inline-flex items-center space-x-2 px-4 py-2 bg-white/5 border border-white/10 rounded-full mb-8 backdrop-blur-md">
            <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
            <span class="text-blue-400 text-xs font-bold tracking-widest uppercase">{{ __("Sovereign Financial Society") }}</span>
        </div>
        
        <h1 class="text-6xl md:text-8xl font-black text-white leading-tight mb-8 tracking-tighter">
            One Empire. <br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Universal Authority.</span>
        </h1>
        
        <p class="max-w-2xl text-xl text-gray-400 mb-12 leading-relaxed">
            Welcome to the YGXONE ecosystem. A unified sanctuary for high-fidelity finance, secure communication, and neural intelligence. Built for those who demand prestige.
        </p>

        <div class="flex flex-col md:flex-row space-y-6 md:space-y-0 md:space-x-8 mt-4">
            <!-- Individual Path -->
            <a href="{{ route('register.individual') }}" class="group relative px-10 py-6 bg-white/5 border border-white/10 rounded-3xl overflow-hidden transition-all hover:border-blue-500/50 hover:bg-blue-500/5 backdrop-blur-md shadow-2xl text-left w-full md:w-80">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4 border border-blue-500/30">
                        <i class="fas fa-user text-blue-400"></i>
                    </div>
                    <h4 class="text-xl font-black text-white mb-2 uppercase tracking-tight">{{ __("Individual") }}</h4>
                    <p class="text-gray-400 text-xs leading-tight mb-4">{{ __("For personal productivity, secure mail, and social wealth prestige.") }}</p>
                    <span class="text-blue-400 text-xs font-bold uppercase tracking-widest">{{ __("Start Free") }} →</span>
                </div>
            </a>

            <!-- Business Path -->
            @if(\App\Models\PlatformFeature::isEnabled('business_registration'))
            <a href="{{ route('register.business') }}" class="group relative px-10 py-6 bg-white/5 border border-white/10 rounded-3xl overflow-hidden transition-all hover:border-purple-500/50 hover:bg-purple-500/5 backdrop-blur-md shadow-2xl text-left w-full md:w-80">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4 border border-purple-500/30">
                        <i class="fas fa-building text-purple-400"></i>
                    </div>
                    <h4 class="text-xl font-black text-white mb-2 uppercase tracking-tight">{{ __("Business") }}</h4>
                    <p class="text-gray-400 text-xs leading-tight mb-4">{{ __("For teams, global liquidation, and enterprise-grade authority.") }}</p>
                    <span class="text-purple-400 text-xs font-bold uppercase tracking-widest">{{ __("Scale Up") }} →</span>
                </div>
            </a>
            @endif
        </div>
    </div>

    <!-- Service Feature Grid -->
    <div id="services" class="relative z-10 max-w-7xl mx-auto px-6 py-24 border-t border-white/5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <!-- YG Pay -->
            <div class="group">
                <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-6 border border-blue-500/30 group-hover:bg-blue-500/40 transition-all">
                    <i class="fas fa-wallet text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black text-white mb-4">YG Pay</h3>
                <p class="text-gray-400 leading-relaxed">{{ __("Global liquidity, social wealth prestige, and real-time cross-border bridge. The heartbeat of your financial authority.") }}</p>
            </div>

            <!-- YG Mail -->
            <div class="group">
                <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-6 border border-purple-500/30 group-hover:bg-purple-500/40 transition-all">
                    <i class="fas fa-envelope text-purple-400 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black text-white mb-4">YG Mail</h3>
                <p class="text-gray-400 leading-relaxed">{{ __("End-to-end encrypted triage for a private communication fortress. Unified inbox for the elite.") }}</p>
            </div>

            <!-- YG AI -->
            <div class="group">
                <div class="w-14 h-14 bg-teal-500/20 rounded-2xl flex items-center justify-center mb-6 border border-teal-500/30 group-hover:bg-teal-500/40 transition-all">
                    <i class="fas fa-brain text-teal-400 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black text-white mb-4">YG AI</h3>
                <p class="text-gray-400 leading-relaxed">{{ __("Neural intelligence that evolves with your ecosystem. Nightly distillation of data into optimized models.") }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
    body {
        font-family: 'Inter', sans-serif;
    }
</style>
@endsection
