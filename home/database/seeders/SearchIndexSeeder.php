<?php

namespace Database\Seeders;

use App\Services\SearchIndexerService;
use Illuminate\Database\Seeder;

class SearchIndexSeeder extends Seeder
{
    /**
     * Run the seeder — sync all ecosystem module data into the search index.
     *
     * This queries each module's MySQL tables (mails, drive_files, documents, notes)
     * and populates the `indexed_items` table so the search engine has data to return.
     */
    public function run(SearchIndexerService $indexer): void
    {
        $this->command?->info('Syncing ecosystem data into search index...');

        $results = $indexer->syncAll();

        foreach ($results as $service => $count) {
            $this->command?->line("- {$service}: indexed {$count} items");
        }

        $total = array_sum($results);
        $this->command?->info("Search index seeded successfully with {$total} total items across " . count($results) . " services.");

        if ($total === 0) {
            $this->command?->warn('No items were indexed. The module tables (mails, drive_files, documents, notes) may be empty.');
        }
    }
}
