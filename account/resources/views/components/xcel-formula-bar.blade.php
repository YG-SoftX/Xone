<div class="bg-white border-b border-gray-100 flex flex-col shadow-sm relative z-50">
    <!-- Formula Toolbar -->
    <div class="flex items-center gap-4 px-6 py-2 bg-gray-50/50 border-b border-gray-50">
        <div class="flex items-center gap-2">
            <button class="p-1.5 text-gray-400 hover:text-green-600 transition-all"><i class="fas fa-bold text-xs"></i></button>
            <button class="p-1.5 text-gray-400 hover:text-green-600 transition-all"><i class="fas fa-italic text-xs"></i></button>
            <div class="w-px h-4 bg-gray-200 mx-1"></div>
            <button class="p-1.5 text-gray-400 hover:text-green-600 transition-all"><i class="fas fa-chart-bar text-xs"></i></button>
            <button class="p-1.5 text-gray-400 hover:text-green-600 transition-all"><i class="fas fa-filter text-xs"></i></button>
        </div>
        
        <!-- Imperial Sync Status -->
        <div class="ml-auto flex items-center gap-4">
            <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-full border border-green-100">
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-[9px] font-bold text-green-600 uppercase tracking-widest">YG Pay: Connected</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-full border border-blue-100">
                <i class="fas fa-bullseye text-blue-600 text-[9px]"></i>
                <span class="text-[9px] font-bold text-blue-600 uppercase tracking-widest">YG Ads: Live Sync</span>
            </div>
        </div>
    </div>

    <!-- The Imperial Formula Input -->
    <div class="flex items-center gap-4 px-6 py-3">
        <div class="flex items-center gap-2 text-gray-400 italic font-serif text-lg min-w-[40px]">
            fx
        </div>
        <div class="flex-1 relative group">
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 opacity-0 group-focus-within:opacity-100 transition-opacity">
                <i class="fas fa-crown text-[10px]"></i>
            </div>
            <input type="text" value="=YG_REVENUE_TODAY(node='01', type='adsense')" 
                class="w-full pl-4 pr-12 py-2.5 bg-white border border-gray-100 rounded-xl text-[15px] font-mono text-gray-800 focus:ring-4 focus:ring-green-500/5 focus:border-green-500/30 outline-none shadow-inner transition-all"
                placeholder="Enter formula or =YG_ to use Imperial Functions...">
            
            <!-- Imperial Suggestions Dropdown (Simulated) -->
            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-gray-300 uppercase tracking-widest">
                Imperial Formula Mode
            </div>
        </div>
        <div class="shrink-0 flex gap-2">
            <button class="w-10 h-10 bg-green-600 text-white rounded-xl flex items-center justify-center shadow-lg hover:bg-green-700 transition-all">
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>
