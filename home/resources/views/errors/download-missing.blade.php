@extends('layouts.app')

@section('title', 'Download Unavailable')

@section('content')
<div class="min-h-screen flex flex-col">
    {{-- ── Header ── --}}
    <header class="px-4 sm:px-8 lg:px-12 py-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('browser.home') }}" class="flex items-center gap-2 no-underline hover:opacity-90 transition-opacity">
                <div class="text-2xl font-bold">
                    <span style="color: var(--yg-primary)">YG</span>
                    <span style="color: var(--yg-secondary)">XONE</span>
                </div>
            </a>
        </div>
    </header>

    {{-- ── Error Content ── --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl text-center">
            <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-download text-4xl text-red-500"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-[var(--yg-text)] mb-4">
                {{ $platform }} Download Unavailable
            </h1>
            
            <p class="text-lg text-[var(--yg-text-dim)] mb-8">
                The {{ $platform }} version of YGXONE Browser is not currently available for download. 
                Please check back later or try another platform.
            </p>
            
            <div class="space-y-4">
                <a href="{{ route('downloads.page') }}" 
                   class="inline-block px-6 py-3 rounded-xl bg-[var(--yg-primary)] text-white font-semibold hover:bg-[var(--yg-primary)]/90 transition-colors">
                    View All Downloads
                </a>
                
                <a href="{{ route('browser.home') }}" 
                   class="inline-block px-6 py-3 rounded-xl bg-[var(--yg-surface)] text-[var(--yg-text)] font-semibold hover:bg-[var(--yg-border)] transition-colors ml-4">
                    Back to Home
                </a>
            </div>
        </div>
    </main>
</div>
@endsection