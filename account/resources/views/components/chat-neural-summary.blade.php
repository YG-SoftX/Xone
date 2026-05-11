<div class="absolute inset-x-0 top-0 z-[60] animate-fade-down">
    <div class="google-card !p-6 bg-white/95 backdrop-blur-2xl border-brand/20 shadow-2xl rounded-b-3xl">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand/10 rounded-xl flex items-center justify-center text-brand">
                    <div class="w-5 h-5 relative">
                        <div class="absolute inset-0 bg-brand/40 blur-md rounded-full animate-pulse"></div>
                        <i class="fas fa-robot relative z-10"></i>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-900 leading-tight">Neural Thread Summary</h4>
                    <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest">Powered by YG Neural Node</p>
                </div>
            </div>
            <button onclick="this.closest('.animate-fade-down').remove()" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Summary Content -->
        <div class="space-y-6">
            <div class="p-4 bg-brand/[0.02] rounded-2xl border border-brand/5">
                <p class="text-[13px] text-gray-600 leading-relaxed italic">
                    "The team discussed the upcoming 16-node expansion. General consensus is to prioritize the **Sovereign Search Hub** optimization before the official grand launch."
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Key Decisions</h5>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-xs text-gray-700">
                            <i class="fas fa-check text-brand"></i> Deployment scheduled for 2026-05-01.
                        </li>
                        <li class="flex items-center gap-2 text-xs text-gray-700">
                            <i class="fas fa-check text-brand"></i> Use Imperial Brand Card as default.
                        </li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Action Items</h5>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-xs text-gray-700">
                            <i class="fas fa-plus text-gray-400"></i> Provision Node 17 (IT Team).
                        </li>
                        <li class="flex items-center gap-2 text-xs text-gray-700">
                            <i class="fas fa-plus text-gray-400"></i> Review AdSense thresholds.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-50 flex justify-end gap-4">
            <button class="px-6 py-2 bg-gray-50 text-gray-500 rounded-full text-[10px] font-bold uppercase tracking-widest border border-gray-100 hover:bg-gray-100 transition-all">Copy to DocX</button>
            <button class="px-6 py-2 bg-brand text-white rounded-full text-[10px] font-bold uppercase tracking-widest shadow-md hover:bg-opacity-90 transition-all">Convert to Meeting</button>
        </div>
    </div>
</div>
