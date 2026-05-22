@extends('layouts.app')

@section('title', 'YGXONE Downloads')

@section('content')
<div class="min-h-screen flex flex-col">
    {{-- ── Header ── --}}
    <header class="px-4 sm:px-8 lg:px-12 py-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('search.home') }}" class="flex items-center gap-2 no-underline hover:opacity-90 transition-opacity">
                <div class="text-2xl font-bold">
                    <span style="color: var(--yg-primary)">YG</span>
                    <span style="color: var(--yg-secondary)">XONE</span>
                </div>
            </a>
        </div>
    </header>

    {{-- ── Downloads Content ── --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-4xl">
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-bold text-[var(--yg-text)] mb-4">
                    Download YGXONE Browser
                </h1>
                <p class="text-lg text-[var(--yg-text-dim)] max-w-2xl mx-auto">
                    Get the full YG ecosystem experience with our desktop browser. Access all services in one integrated application with enhanced privacy and security features.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-2xl p-6 border border-[var(--yg-border)] shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-500 text-white mb-4">
                        <i class="fab fa-windows text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--yg-text)] mb-2">Windows</h3>
                    <p class="text-[var(--yg-text-dim)] mb-4">For Windows 10 and later</p>
                    <a href="{{ route('download.desktop.windows') }}" 
                       class="w-full py-3 px-4 rounded-xl bg-blue-500 text-white font-semibold hover:bg-blue-600 transition-colors inline-block text-center">
                        Download
                    </a>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-[var(--yg-border)] shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gray-800 text-white mb-4">
                        <i class="fab fa-apple text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--yg-text)] mb-2">macOS</h3>
                    <p class="text-[var(--yg-text-dim)] mb-4">For macOS 10.15 and later</p>
                    <a href="{{ route('download.desktop.macos') }}" 
                       class="w-full py-3 px-4 rounded-xl bg-gray-800 text-white font-semibold hover:bg-gray-900 transition-colors inline-block text-center">
                        Download
                    </a>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-[var(--yg-border)] shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-orange-500 text-white mb-4">
                        <i class="fab fa-linux text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--yg-text)] mb-2">Linux</h3>
                    <p class="text-[var(--yg-text-dim)] mb-4">Universal AppImage</p>
                    <a href="{{ route('download.desktop.linux') }}" 
                       class="w-full py-3 px-4 rounded-xl bg-orange-500 text-white font-semibold hover:bg-orange-600 transition-colors inline-block text-center">
                        Download
                    </a>
                </div>
            </div>

            <div class="bg-gradient-to-r from-[var(--yg-primary)]/10 to-[var(--yg-secondary)]/10 rounded-2xl p-6 border border-[var(--yg-primary)]/20">
                <h3 class="text-lg font-bold text-[var(--yg-text)] mb-3">Features</h3>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[var(--yg-text-dim)]">
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Full YG Ecosystem Integration</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Enhanced Privacy Controls</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Ad & Tracker Blocking</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> AI-Powered Assistance</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Offline Capabilities</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Single Sign-On Support</li>
                </ul>
            </div>
        </div>
    </main>
</div>
@endsection