<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowSearchAnalytics extends Command
{
    protected $signature = 'search:analytics {--days=7 : Number of days to analyze}';
    protected $description = 'Display search analytics and insights';

    public function handle()
    {
        $days = (int) $this->option('days');
        $since = now()->subDays($days);
        
        $this->info("📊 YG Home Search Analytics (Last {$days} Days)");
        $this->newLine();
        
        // Total searches
        $totalSearches = DB::table('search_training_data')
            ->where('created_at', '>=', $since)
            ->count();
        
        $this->line("🔍 Total Searches: <fg=cyan>{$totalSearches}</>");
        $this->newLine();
        
        // Zero-result searches
        $zeroResults = DB::table('search_training_data')
            ->where('created_at', '>=', $since)
            ->where('result_count', 0)
            ->count();
        
        $zeroResultRate = $totalSearches > 0 ? round(($zeroResults / $totalSearches) * 100, 2) : 0;
        $this->line("❌ Zero-Result Searches: <fg=red>{$zeroResults} ({$zeroResultRate}%)</>");
        $this->newLine();
        
        // Searches with clicks
        $withClicks = DB::table('search_training_data')
            ->where('created_at', '>=', $since)
            ->where('had_clicks', true)
            ->count();
        
        $clickRate = $totalSearches > 0 ? round(($withClicks / $totalSearches) * 100, 2) : 0;
        $this->line("✅ Click-Through Rate: <fg=green>{$withClicks} ({$clickRate}%)</>");
        $this->newLine();
        
        // Top trending queries
        $this->warn("🔥 Top 10 Trending Queries:");
        $trending = DB::table('search_training_data')
            ->select('query', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        $headers = ['Rank', 'Query', 'Count'];
        $rows = $trending->map(function ($item, $index) {
            return [$index + 1, $item->query, $item->count];
        })->toArray();
        
        $this->table($headers, $rows);
        $this->newLine();
        
        // Top zero-result queries (improvement opportunities)
        if ($zeroResults > 0) {
            $this->error("⚠️  Top 10 Zero-Result Queries (Add Content):");
            $zeroQueries = DB::table('search_training_data')
                ->select('query', DB::raw('COUNT(*) as occurrences'))
                ->where('created_at', '>=', $since)
                ->where('result_count', 0)
                ->groupBy('query')
                ->orderBy('occurrences', 'desc')
                ->limit(10)
                ->get();
            
            $headers = ['Rank', 'Query', 'Occurrences'];
            $rows = $zeroQueries->map(function ($item, $index) {
                return [$index + 1, $item->query, $item->occurrences];
            })->toArray();
            
            $this->table($headers, $rows);
            $this->newLine();
        }
        
        // Most clicked results
        $this->info("🎯 Top 10 Most Clicked Results:");
        $clicked = DB::table('search_result_rankings')
            ->orderBy('click_count', 'desc')
            ->limit(10)
            ->get(['url', 'result_type', 'click_count']);
        
        $headers = ['URL', 'Type', 'Clicks'];
        $rows = $clicked->map(function ($item) {
            $shortUrl = strlen($item->url) > 50 ? substr($item->url, 0, 47) . '...' : $item->url;
            return [$shortUrl, $item->result_type, $item->click_count];
        })->toArray();
        
        $this->table($headers, $rows);
        $this->newLine();
        
        // Index statistics
        $this->info("📦 Index Statistics:");
        $indexedItems = DB::table('indexed_items')
            ->select('service', DB::raw('COUNT(*) as count'))
            ->groupBy('service')
            ->get();
        
        $totalIndexed = $indexedItems->sum('count');
        $this->line("   Total Indexed Items: <fg=cyan>{$totalIndexed}</>");
        
        foreach ($indexedItems as $item) {
            $percentage = $totalIndexed > 0 ? round(($item->count / $totalIndexed) * 100, 2) : 0;
            $this->line("   - {$item->service}: {$item->count} ({$percentage}%)");
        }
        
        $this->newLine();
        $this->info("✨ Analytics complete!");
        
        return Command::SUCCESS;
    }
}
