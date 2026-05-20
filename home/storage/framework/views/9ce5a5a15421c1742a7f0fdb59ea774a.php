<?php
    $ecoService = app(\App\Services\EcosystemService::class);
    $themeService = app(\App\Services\HomeThemeService::class);
    $activeApps = $ecoService->getActiveApps();
?>



<?php $__env->startSection('title', e($query) . ' - YGXONE Search'); ?>

<?php $__env->startSection('content'); ?>
<div x-data="resultsState()" x-init="init()">
    
    
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-[var(--yg-border)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-8">
            
            <div class="flex items-center gap-4 py-3">
                <a href="<?php echo e(route('search.home')); ?>" class="flex-shrink-0 no-underline">
                    <span class="font-heading font-black text-xl tracking-tight">YGX<span class="gradient-text">ONE</span></span>
                </a>

                <div class="flex-1 max-w-2xl relative">
                    <form action="<?php echo e(route('search.index')); ?>" method="GET" class="relative">
                        <div class="search-glass rounded-full overflow-hidden transition-all duration-200">
                            <div class="flex items-center px-4 py-2 gap-2">
                                <i class="fas fa-search text-[var(--yg-text-dim)] text-sm opacity-50"></i>
                                <input type="text" name="q" value="<?php echo e(e($query)); ?>"
                                       x-model="searchQuery"
                                       @input.debounce.400ms="fetchSuggestions()"
                                       @keydown.escape="showSuggestions = false"
                                       class="flex-1 bg-transparent border-none outline-none text-sm placeholder:text-[var(--yg-text-dim)]/40 focus:ring-0 font-body"
                                       placeholder="Ask Yuga AI...">
                                <div class="flex items-center gap-2 text-xs text-[var(--yg-text-dim)]">
                                    <button type="button" x-show="searchQuery.length > 0" @click="clearSearch()" class="hover:text-[var(--yg-text)] transition-colors" x-cloak>
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <span class="w-px h-4 bg-[var(--yg-border)]"></span>
                                    <div class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)]">
                                        YUGA
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    
                    <div x-show="showSuggestions" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         class="absolute top-full left-0 right-0 mt-1 bg-white/95 backdrop-blur-xl border border-[var(--yg-border)] rounded-2xl shadow-xl overflow-hidden">
                        <template x-for="s in suggestions" :key="s">
                            <a :href="'?q=' + encodeURIComponent(s)" 
                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-[var(--yg-surface)] transition-colors">
                                <i class="fas fa-search text-[var(--yg-text-dim)] text-xs opacity-40"></i>
                                <span class="text-sm" x-text="s"></span>
                            </a>
                        </template>
                    </div>
                </div>

                
                <div class="flex items-center gap-3 flex-shrink-0">
                    <?php if(auth()->check()): ?>
                    <a href="<?php echo e(route('sso.logout')); ?>" 
                       class="w-8 h-8 rounded-lg bg-gradient-to-br from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white flex items-center justify-center text-xs hover:opacity-90 transition-all shadow-md"
                       title="Sign out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo e(route('sso.initiate')); ?>" 
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] hover:opacity-90 transition-all">
                        <i class="fas fa-key"></i> Login
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="flex gap-6 -mb-px overflow-x-auto scrollbar-none">
                <?php $tabs = ['all' => 'fa-search', 'ai' => 'fa-sparkles', 'images' => 'fa-image', 'news' => 'fa-newspaper', 'videos' => 'fa-play-circle', 'shopping' => 'fa-shopping-bag']; ?>
                <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tabKey => $tabIcon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="?q=<?php echo e(urlencode($query)); ?>&type=<?php echo e($tabKey); ?>" 
                   class="flex items-center gap-2 py-3 px-2 text-xs font-semibold border-b-2 transition-all whitespace-nowrap
                   <?php echo e(($type == $tabKey || ($type == 'web' && $tabKey == 'all')) ? 'border-[var(--yg-primary)] text-[var(--yg-primary)]' : 'border-transparent text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] hover:border-[var(--yg-border)]'); ?>">
                    <i class="fas <?php echo e($tabIcon); ?> text-[10px]"></i>
                    <span><?php echo e(ucfirst($tabKey)); ?></span>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="ml-auto flex items-center">
                    <a href="#" class="py-3 px-2 text-xs font-semibold text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] transition-colors">Tools</a>
                </div>
            </div>
        </div>
    </header>

    
    <div class="max-w-7xl mx-auto px-4 sm:px-8 py-6 flex flex-col lg:flex-row gap-8">
        
        
        <div class="flex-1 min-w-0">

            
            <p class="text-sm text-[var(--yg-text-dim)] mb-6">
                <?php if($total_results > 0): ?>
                    About <?php echo e(number_format($total_results)); ?> results for <strong class="text-[var(--yg-text)]">"<?php echo e(e($query)); ?>"</strong>
                    <span class="mx-2 opacity-30">—</span>
                    <span class="text-xs">Page <?php echo e($pagination['current_page'] ?? 1); ?> of <?php echo e($pagination['last_page'] ?? 1); ?></span>
                <?php else: ?>
                    No results found for <strong class="text-[var(--yg-text)]">"<?php echo e(e($query)); ?>"</strong>
                <?php endif; ?>
            </p>

            
            <?php if(isset($error)): ?>
            <div class="p-6 rounded-2xl bg-[var(--yg-danger)]/5 border border-[var(--yg-danger)]/10 text-sm text-[var(--yg-danger)] mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo e($error); ?>

            </div>
            <?php endif; ?>

            
            <?php if(isset($results['ai_enhanced']) && $results['ai_enhanced']): ?>
            <div class="mb-8 p-6 sm:p-8 rounded-2xl bg-gradient-to-br from-[var(--yg-primary)]/[0.04] to-[var(--yg-secondary)]/[0.04] border border-[var(--yg-primary)]/10">
                <div class="flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider mb-4" style="color: var(--yg-primary)">
                    <i class="fas fa-sparkles"></i>
                    Yuga AI Overview
                </div>
                <div class="text-base sm:text-lg leading-relaxed text-[var(--yg-text)] mb-4 font-medium">
                    <?php echo nl2br(e($results['ai_enhanced']['answer'])); ?>

                </div>
                <div class="flex items-center gap-4 text-xs text-[var(--yg-text-dim)]">
                    <span class="flex items-center gap-1">
                        <i class="fas fa-database"></i>
                        <?php echo e($results['ai_enhanced']['sources'] ?? 'AI generated'); ?>

                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-signal"></i>
                        <?php echo e(ucfirst($results['ai_enhanced']['confidence'] ?? 'medium')); ?> confidence
                    </span>
                </div>
            </div>
            <?php endif; ?>

            
            <?php if(isset($results['ecosystem']) && count($results['ecosystem']) > 0): ?>
            <div class="mb-8">
                <h3 class="text-base font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-cube text-[var(--yg-primary)] text-sm"></i>
                    Ecosystem Results
                </h3>
                <div class="space-y-3">
                    <?php $__currentLoopData = $results['ecosystem']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php 
                        $badgeStyle = match($result['type']) {
                            'mail' => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                            'drive' => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                            'docs' => ['bg' => '#eff6ff', 'text' => '#2563eb'],
                            'contacts' => ['bg' => '#fefce8', 'text' => '#ca8a04'],
                            'calendar' => ['bg' => '#fdf2f8', 'text' => '#db2777'],
                            default => ['bg' => '#f8fafc', 'text' => '#64748b'],
                        };
                    ?>
                    <div class="card-hover p-4 sm:p-5 rounded-xl bg-white border border-[var(--yg-border)]">
                        <div class="flex items-start gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                          style="background: <?php echo e($badgeStyle['bg']); ?>; color: <?php echo e($badgeStyle['text']); ?>">
                                        <i class="<?php echo e($result['icon'] ? 'fas fa-' . $result['icon'] : 'fas fa-cube'); ?> text-[9px]"></i>
                                        <?php echo e(ucfirst(e($result['type']))); ?>

                                    </span>
                                    <?php if(isset($result['metadata']['date'])): ?>
                                    <span class="text-[11px] text-[var(--yg-text-dim)]"><?php echo e(\Carbon\Carbon::parse($result['metadata']['date'])->diffForHumans()); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo e(e($result['url'] ?? '#')); ?>" target="_blank" 
                                   class="text-base font-semibold hover:underline mb-0.5 block"
                                   style="color: var(--yg-primary)">
                                    <?php echo e(e($result['title'])); ?>

                                </a>
                                <p class="text-sm text-[var(--yg-text-dim)] leading-relaxed line-clamp-2">
                                    <?php echo e(strip_tags($result['snippet'] ?? '')); ?>

                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="space-y-4">
                <?php $__currentLoopData = $results['web']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card-hover p-4 sm:p-5 rounded-xl bg-white border border-[var(--yg-border)]">
                    <div class="flex gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 text-xs text-[var(--yg-text-dim)] mb-1">
                                <?php if(isset($result['favicon'])): ?>
                                <img src="<?php echo e(e($result['favicon'])); ?>" class="w-4 h-4 rounded" alt="">
                                <?php endif; ?>
                                <span><?php echo e(e($result['domain'] ?? '')); ?></span>
                            </div>
                            <a href="<?php echo e(e($result['url'] ?? '#')); ?>" target="_blank" 
                               class="text-base sm:text-lg font-semibold hover:underline mb-1 block"
                               style="color: var(--yg-primary)">
                                <?php echo e(e($result['title'] ?? '')); ?>

                            </a>
                            <p class="text-sm text-[var(--yg-text-dim)] leading-relaxed">
                                <?php echo e(Str::limit(strip_tags($result['snippet'] ?? ''), 200)); ?>

                            </p>
                        </div>
                        <?php if(isset($result['image']) && $result['image']): ?>
                        <div class="hidden sm:block flex-shrink-0">
                            <img src="<?php echo e(e($result['image'])); ?>" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl border border-[var(--yg-border)]" alt="">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <?php if(isset($suggestions) && !empty($suggestions)): ?>
            <div class="mt-10 p-6 rounded-xl bg-white border border-[var(--yg-border)]">
                <h3 class="text-sm font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-search text-[var(--yg-text-dim)]"></i>
                    People also search for
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php $__currentLoopData = $suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(is_array($items)): ?>
                            <?php $__currentLoopData = array_slice($items, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="?q=<?php echo e(urlencode($item)); ?>" 
                               class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--yg-surface)] transition-colors group">
                                <i class="fas fa-arrow-right text-[10px] text-[var(--yg-text-dim)] opacity-30 group-hover:opacity-60 group-hover:text-[var(--yg-primary)] transition-all"></i>
                                <span class="text-sm" style="color: var(--yg-primary)"><?php echo e($item); ?></span>
                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>

            
            <?php if(isset($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
            <div class="mt-16 mb-8 flex flex-col items-center gap-4">
                <div class="flex items-center gap-2">
                    <?php if(($pagination['current_page'] ?? 1) > 1): ?>
                    <a href="?q=<?php echo e(urlencode($query)); ?>&type=<?php echo e($type); ?>&page=<?php echo e($pagination['current_page'] - 1); ?>" 
                       class="px-4 py-2 text-sm font-semibold rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all flex items-center gap-1.5">
                        <i class="fas fa-chevron-left text-xs"></i> Previous
                    </a>
                    <?php endif; ?>

                    <div class="flex items-center gap-1">
                        <?php for($i = max(1, ($pagination['current_page'] ?? 1) - 3); $i <= min($pagination['last_page'] ?? 1, ($pagination['current_page'] ?? 1) + 3); $i++): ?>
                            <a href="?q=<?php echo e(urlencode($query)); ?>&type=<?php echo e($type); ?>&page=<?php echo e($i); ?>" 
                               class="w-9 h-9 flex items-center justify-center rounded-full text-sm font-bold transition-all
                               <?php echo e($i == ($pagination['current_page'] ?? 1) ? 'text-white' : 'text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)]'); ?>"
                               style="<?php echo e($i == ($pagination['current_page'] ?? 1) ? 'background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary))' : ''); ?>">
                                <?php echo e($i); ?>

                            </a>
                        <?php endfor; ?>
                    </div>

                    <?php if(($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                    <a href="?q=<?php echo e(urlencode($query)); ?>&type=<?php echo e($type); ?>&page=<?php echo e($pagination['current_page'] + 1); ?>" 
                       class="px-4 py-2 text-sm font-semibold rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all flex items-center gap-1.5">
                        Next <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="text-xs font-medium tracking-wide uppercase text-[var(--yg-text-dim)]">
                    YGXONE Intelligence Index — Page <?php echo e($pagination['current_page'] ?? 1); ?>

                </div>
            </div>
            <?php endif; ?>

            
            <div class="mt-16 pt-8 border-t border-[var(--yg-border)] text-xs text-[var(--yg-text-dim)]">
                <div class="flex flex-wrap gap-6 font-medium">
                    <a href="#" class="hover:text-[var(--yg-text)] transition-colors">Help</a>
                    <a href="#" class="hover:text-[var(--yg-text)] transition-colors">Send feedback</a>
                    <a href="#" class="hover:text-[var(--yg-text)] transition-colors">Privacy</a>
                    <a href="#" class="hover:text-[var(--yg-text)] transition-colors">Terms</a>
                </div>
            </div>
        </div>

        
        <aside class="w-full lg:w-80 flex-shrink-0">
            <div class="lg:sticky lg:top-28 space-y-6">
                
                
                <div class="p-5 rounded-2xl bg-gradient-to-br from-white to-[var(--yg-surface)] border border-[var(--yg-border)]">
                    <h4 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-4">YG Ecosystem</h4>
                    <div class="space-y-2">
                        <?php $__currentLoopData = array_slice($activeApps, 0, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($app['url']); ?>" target="_blank"
                           class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[var(--yg-surface)] transition-all duration-200 group">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm"
                                 style="background: <?php echo e($app['icon_color']); ?>15;">
                                <i class="<?php echo e($app['icon']); ?>" style="color: <?php echo e($app['icon_color']); ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-[var(--yg-text)] group-hover:text-[var(--yg-primary)] transition-colors truncate">
                                    <?php echo e($app['name']); ?>

                                </div>
                                <div class="text-[10px] text-[var(--yg-text-dim)]"><?php echo e($app['description']); ?></div>
                            </div>
                            <i class="fas fa-chevron-right text-[10px] text-[var(--yg-text-dim)] opacity-0 group-hover:opacity-100 transition-all -translate-x-1 group-hover:translate-x-0"></i>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php if(count($activeApps) > 5): ?>
                    <a href="https://master.ygxone.com/admin" target="_blank"
                       class="mt-3 block text-center text-xs font-semibold py-2 rounded-lg border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all" style="color: var(--yg-primary)">
                        View all <?php echo e(count($activeApps)); ?> services →
                    </a>
                    <?php endif; ?>

                    
                    <div class="mt-4 pt-4 border-t border-[var(--yg-border)]">
                        <p class="text-[10px] text-[var(--yg-text-dim)] leading-relaxed italic">
                            <i class="fas fa-shield-alt text-[9px] mr-1"></i>
                            You are searching within the YGXONE Sovereign Intelligence Platform. Your data remains encrypted and sovereign.
                        </p>
                    </div>
                </div>

                
                <div class="p-5 rounded-2xl bg-white border border-[var(--yg-border)]">
                    <h4 class="text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-widest mb-3">Quick Links</h4>
                    <div class="space-y-2">
                        <a href="https://ai.ygxone.com" target="_blank" class="flex items-center gap-3 p-2 rounded-lg hover:bg-[var(--yg-surface)] transition-colors text-xs font-medium text-[var(--yg-text)]">
                            <i class="fas fa-sparkles" style="color: var(--yg-primary)"></i>
                            Yuga AI Intelligence
                        </a>
                        <a href="<?php echo e(route('sso.initiate')); ?>" class="flex items-center gap-3 p-2 rounded-lg hover:bg-[var(--yg-surface)] transition-colors text-xs font-medium text-[var(--yg-text)]">
                            <i class="fas fa-key" style="color: var(--yg-secondary)"></i>
                            Account Settings
                        </a>
                        <a href="https://developer.ygxone.com" target="_blank" class="flex items-center gap-3 p-2 rounded-lg hover:bg-[var(--yg-surface)] transition-colors text-xs font-medium text-[var(--yg-text)]">
                            <i class="fas fa-code"></i>
                            Developer API
                        </a>
                    </div>
                </div>

            </div>
        </aside>
    </div>
</div>

<script>
    function resultsState() {
        return {
            searchQuery: '<?php echo e(e($query)); ?>',
            suggestions: [],
            showSuggestions: false,
            loading: false,

            async fetchSuggestions() {
                if (this.searchQuery.length < 2) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }
                this.loading = true;
                try {
                    const res = await fetch(`/api/search/suggestions?q=${encodeURIComponent(this.searchQuery)}`);
                    const data = await res.json();
                    this.suggestions = data.suggestions || [];
                    this.showSuggestions = this.suggestions.length > 0;
                } catch(e) { this.suggestions = []; }
                finally { this.loading = false; }
            },

            clearSearch() {
                this.searchQuery = '';
                this.suggestions = [];
                this.showSuggestions = false;
            }
        }
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\YG SoftX\Xone\home\resources\views/search/results.blade.php ENDPATH**/ ?>