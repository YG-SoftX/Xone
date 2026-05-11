<!-- YG Ecosystem Global Launcher -->
<div class="yg-launcher-container" x-data="{ open: false }">
    <!-- The Grid Trigger (Google Style) -->
    <button @click="open = !open" class="yg-grid-trigger" title="YG Ecosystem">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM6 4c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 12c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
    </button>

    <!-- The Hub Panel -->
    <div x-show="open" 
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="yg-hub-panel">
        
        <div class="yg-hub-grid">
            @php
                $activeServices = \App\Models\YgService::where('is_active', true)->orderBy('sort_order')->get();
            @endphp
            
            @foreach($activeServices as $service)
                <a href="{{ $service->url }}" class="yg-service-card group">
                    <div class="yg-icon-box" style="background: {{ $service->color ?? '#f1f5f9' }}15;">
                        <i class="{{ $service->icon ?? 'fas fa-cube' }}" style="color: {{ $service->color ?? '#64748b' }};"></i>
                    </div>
                    <span class="yg-service-name">{{ $service->service_name }}</span>
                </a>
            @endforeach

            <!-- Admin Shortcut -->
            @if(auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin'))
                <a href="https://account.ygxone.com/admin" class="yg-service-card group">
                    <div class="yg-icon-box bg-amber-50">
                        <i class="fas fa-shield-halved text-amber-600"></i>
                    </div>
                    <span class="yg-service-name">Sovereign Admin</span>
                </a>
            @endif
        </div>

        <div class="yg-hub-footer">
            <a href="https://account.ygxone.com/dashboard" class="yg-more-link">
                More from YG Ecosystem
            </a>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    
    .yg-launcher-container {
        position: relative;
        display: inline-block;
    }

    .yg-grid-trigger {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #5f6368;
        transition: background 0.2s;
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .yg-grid-trigger:hover {
        background: rgba(60, 64, 67, 0.08);
        color: #202124;
    }

    .yg-grid-trigger svg {
        width: 24px;
        height: 24px;
    }

    .yg-hub-panel {
        position: absolute;
        top: 50px;
        right: 0;
        width: 320px;
        background: #ffffff;
        border-radius: 28px;
        box-shadow: 0 1px 3px rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
        padding: 16px;
        z-index: 99999;
        max-height: 480px;
        overflow-y: auto;
    }

    .yg-hub-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .yg-service-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px 4px;
        border-radius: 12px;
        transition: background 0.2s;
        text-decoration: none;
    }

    .yg-service-card:hover {
        background: #f1f5f9;
    }

    .yg-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        font-size: 1.5rem;
        transition: transform 0.2s;
    }

    .yg-service-card:hover .yg-icon-box {
        transform: scale(1.1);
    }

    .yg-service-name {
        font-size: 11px;
        font-weight: 500;
        color: #3c4043;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
    }

    .yg-hub-footer {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e8eaed;
        text-align: center;
    }

    .yg-more-link {
        display: inline-block;
        padding: 10px 24px;
        border: 1px solid #dadce0;
        border-radius: 24px;
        color: #1a73e8;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.2s;
    }

    .yg-more-link:hover {
        background: rgba(26, 115, 232, 0.04);
        border-color: #d2e3fc;
    }
</style>
