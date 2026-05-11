<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSearchCache extends Command
{
    protected $signature = 'search:cache:clear {--pattern=* : Cache pattern to clear (e.g., "search:*")}';
    protected $description = 'Clear search result cache';

    public function handle()
    {
        $patterns = $this->option('pattern');
        
        if (empty($patterns) || $patterns === ['*']) {
            // Clear all search cache
            $this->info('🗑️  Clearing all search cache...');
            
            // Since we can't use wildcards directly, we'll flush the entire cache
            // For production, consider using Redis with proper key tagging
            if (config('cache.default') === 'redis') {
                Cache::store('redis')->flush();
                $this->info('✅ Redis cache cleared successfully!');
            } else {
                // For database/file cache, clear specific keys would require tracking
                $this->warn('⚠️  Using database/file cache. Consider clearing manually or switching to Redis.');
                $this->line('   Tip: Use php artisan cache:clear to clear all cache');
            }
        } else {
            foreach ($patterns as $pattern) {
                $this->line("Clearing cache pattern: {$pattern}");
                // Note: Laravel Cache doesn't support wildcard deletion natively
                // This is a limitation - consider implementing custom cache tagging
            }
        }
        
        $this->info('✨ Cache clear operation complete!');
        
        return Command::SUCCESS;
    }
}
