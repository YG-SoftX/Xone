<?php

namespace App\Console\Commands;

use App\Services\SearchIndexerService;
use Illuminate\Console\Command;

class SyncSearchIndex extends Command
{
    protected $signature = 'search:sync';
    protected $description = 'Sync all ecosystem modules into the unified search index';

    public function handle(SearchIndexerService $indexer)
    {
        $this->info('Starting ecosystem search sync...');
        
        $results = $indexer->syncAll();
        
        foreach ($results as $service => $count) {
            $this->line("- {$service}: indexed {$count} items");
        }
        
        $this->info('Sync complete!');
    }
}
