<!-- YG Ad Unit (Google-standard Ad Placement) -->
<div class="google-card group cursor-pointer overflow-hidden p-6 border-brand/20 bg-brand/5 relative">
    <div class="absolute top-3 right-4 flex items-center gap-1.5 opacity-50">
        <span class="text-[8px] font-black uppercase tracking-widest text-brand">YG Ads</span>
        <i class="fas fa-info-circle text-[8px] text-brand"></i>
    </div>

    <div class="flex gap-6 items-start">
        <div class="w-32 h-32 rounded-[1.5rem] overflow-hidden shrink-0 shadow-sm border border-white">
            <img src="{{ $campaign->image_url ?? 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300' }}" class="w-full h-full object-cover transition-transform group-hover:scale-110" alt="">
        </div>
        <div class="flex-1 min-w-0">
            <h5 class="text-sm font-bold text-gray-900 mb-2 truncate group-hover:text-brand transition-colors">{{ $campaign->title ?? 'Scale Your Imperial Influence' }}</h5>
            <p class="text-[12px] text-gray-500 line-clamp-3 leading-relaxed mb-4">
                {{ $campaign->content ?? 'Discover the new standard of sovereign productivity. Provision neural nodes and scale your staff across the YGXONE nodes today.' }}
            </p>
            <div class="flex items-center justify-between">
                <a href="{{ $campaign->target_url ?? '#' }}" class="text-[10px] font-bold text-brand uppercase tracking-widest hover:underline">Learn More</a>
                <span class="text-[9px] font-medium text-gray-400">Sponsored by {{ $campaign->organization->name ?? 'Imperial Core' }}</span>
            </div>
        </div>
    </div>
</div>
