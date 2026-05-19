{{-- 
  YGXONE Theme Preview Page
  Renders the current theme CSS variables as a visual preview.
  Used by the master panel via EcosystemController::themePreview().
--}}
@extends('layouts.app')

@section('title', 'Theme Preview — YGXONE')

@section('content')
<div class="min-h-screen bg-[var(--yg-surface)]">
    <div class="max-w-4xl mx-auto px-4 sm:px-8 py-10">
        
        {{-- Header --}}
        <div class="mb-10">
            <h1 class="text-3xl font-heading font-black text-[var(--yg-text)]">Theme Preview</h1>
            <p class="text-sm text-[var(--yg-text-dim)] mt-1">
                Preview of <strong>{{ $themeService?->getTheme()['name'] ?? 'Current' }}</strong> theme
            </p>
        </div>

        {{-- Font Preview --}}
        @php $theme = $themeService?->getTheme() ?? []; @endphp
        <div class="rounded-2xl bg-white border border-[var(--yg-border)] p-8 mb-6">
            <h2 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-6">Typography</h2>
            <div class="space-y-4">
                <div>
                    <span class="text-[10px] font-mono text-[var(--yg-text-dim)]">Heading Font</span>
                    <div class="font-heading text-4xl font-black text-[var(--yg-text)]">
                        The quick brown fox jumps over the lazy dog
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-mono text-[var(--yg-text-dim)]">Body Font</span>
                    <div class="font-body text-base leading-relaxed text-[var(--yg-text)]">
                        The quick brown fox jumps over the lazy dog. Pack my box with five dozen liquor jugs. How vexingly quick daft zebras jump!
                    </div>
                </div>
            </div>
        </div>

        {{-- Color Palette --}}
        <div class="rounded-2xl bg-white border border-[var(--yg-border)] p-8 mb-6">
            <h2 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-6">Color Palette</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach(($theme['colors'] ?? []) as $key => $value)
                <div class="flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)]">
                    <div class="w-12 h-12 rounded-full shadow-lg" style="background: {{ $value }}"></div>
                    <span class="text-xs font-mono font-bold text-[var(--yg-text)]">--yg-{{ $key }}</span>
                    <span class="text-[10px] font-mono text-[var(--yg-text-dim)]">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Component Samples --}}
        <div class="rounded-2xl bg-white border border-[var(--yg-border)] p-8 mb-6">
            <h2 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-6">Components</h2>
            
            {{-- Buttons --}}
            <div class="flex flex-wrap gap-3 mb-8">
                <button class="px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg transition-all hover:opacity-90" 
                        style="background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary))">
                    Primary Action
                </button>
                <button class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all hover:bg-[var(--yg-surface)]" 
                        style="border-color: var(--yg-border); color: var(--yg-text)">
                    Secondary
                </button>
                <button class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90" 
                        style="background: var(--yg-primary); color: white">
                    Solid Primary
                </button>
                <button class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90" 
                        style="background: var(--yg-accent); color: white">
                    Accent
                </button>
            </div>

            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-xl border" style="border-color: var(--yg-border); background: var(--yg-surface)">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3"
                         style="background: var(--yg-primary)15; color: var(--yg-primary)">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="text-sm font-bold text-[var(--yg-text)] mb-1">Service Card</h3>
                    <p class="text-xs text-[var(--yg-text-dim)]">A standard ecosystem service card.</p>
                </div>
                <div class="p-5 rounded-xl border" style="border-color: var(--yg-border); background: white">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3"
                         style="background: var(--yg-secondary)15; color: var(--yg-secondary)">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h3 class="text-sm font-bold text-[var(--yg-text)] mb-1">White Card</h3>
                    <p class="text-xs text-[var(--yg-text-dim)]">Default white background card.</p>
                </div>
                <div class="p-5 rounded-xl" style="background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary))">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 bg-white/20 text-white">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">Gradient Card</h3>
                    <p class="text-xs text-white/80">Primary-secondary gradient variant.</p>
                </div>
            </div>

            {{-- Search Bar Mockup --}}
            <div class="mt-8 p-4 rounded-2xl border" style="border-color: var(--yg-border); background: rgba(255,255,255,0.8)">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl" 
                     style="border: 1px solid var(--yg-border); box-shadow: var(--yg-shadow-sm)">
                    <i class="fas fa-search" style="color: var(--yg-text-dim)"></i>
                    <span style="color: var(--yg-text-dim)" class="text-sm">Search the ecosystem...</span>
                    <span class="ml-auto px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider text-white"
                          style="background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary))">
                        YUGA
                    </span>
                </div>
            </div>
        </div>

        {{-- CSS Variables Dump --}}
        <div class="rounded-2xl bg-white border border-[var(--yg-border)] p-8">
            <h2 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-4">Generated CSS Variables</h2>
            <pre class="p-4 rounded-xl text-xs font-mono overflow-x-auto" style="background: #0f172a; color: #e2e8f0;">{{ $themeService?->getCssVariables() ?? ':root { /* No theme loaded */ }' }}</pre>
        </div>

        {{-- Footer Action --}}
        <div class="mt-6 text-center">
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold hover:underline" style="color: var(--yg-primary)">
                ← Back to Dashboard
            </a>
        </div>

    </div>
</div>
@endsection
