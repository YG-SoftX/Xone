@php
    $userAgent = request()->header('User-Agent');
    $isXoneBrowser = str_contains($userAgent, 'YGXoneBrowser');
@endphp

@if(!$isXoneBrowser)
<div id="browser-promo" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] w-full max-w-2xl px-6 animate-fade-up">
    <div class="google-card !p-4 bg-white/90 backdrop-blur-xl border-brand/20 shadow-2xl flex items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-brand rounded-2xl flex items-center justify-center shadow-lg shrink-0">
                <i class="fas fa-globe text-white text-xl"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-900 leading-tight">Upgrade to YG Xone Browser</h4>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest">For Total Privacy & Imperial Speed</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- OS Specific Download Link (Simulated logic) -->
            <div id="os-download-link" class="flex items-center gap-3 px-6 py-2.5 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all cursor-pointer shadow-md">
                <i class="fab fa-windows text-sm mr-1" id="os-icon"></i>
                Download for <span id="os-name">Windows</span>
            </div>
            
            <button onclick="document.getElementById('browser-promo').remove()" class="text-gray-400 hover:text-gray-600 p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        const ua = navigator.userAgent;
        let osName = "Windows";
        let osIcon = "fa-windows";

        if (ua.indexOf("Win") !== -1) { osName = "Windows"; osIcon = "fa-windows"; }
        else if (ua.indexOf("Mac") !== -1) { osName = "macOS"; osIcon = "fa-apple"; }
        else if (ua.indexOf("Android") !== -1) { osName = "Android"; osIcon = "fa-android"; }
        else if (ua.indexOf("iPhone") !== -1 || ua.indexOf("iPad") !== -1) { osName = "iOS"; osIcon = "fa-apple"; }

        document.getElementById('os-name').innerText = osName;
        document.getElementById('os-icon').className = `fab ${osIcon} text-sm mr-1`;
    })();
</script>
@endif
