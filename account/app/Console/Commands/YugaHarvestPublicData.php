<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Society\Post;
use App\Models\Society\Comment;
use Illuminate\Support\Facades\Storage;

class YugaHarvestPublicData extends Command
{
    protected $signature = 'yuga:harvest {--quarantine : Harvest flagged content for negative training}';
    protected $description = 'Securely harvest public society data for Yuga 1.0 LLM training';

    public function handle()
    {
        $this->info('🚀 Yuga 1.0 Knowledge Harvester Activated...');

        $harvestType = $this->option('quarantine') ? 'Quarantined (Negative)' : 'Public (Positive)';
        $this->warn("Current Mode: {$harvestType}");

        $postsQuery = Post::query();
        $commentsQuery = Comment::query();

        if ($this->option('quarantine')) {
            // Harvest only bad content for safety training
            $postsQuery->where('is_hidden', true);
        } else {
            // Harvest only good public content for knowledge
            $postsQuery->where('is_public', true)->where('is_hidden', false);
        }

        $posts = $postsQuery->get()->map(fn($p) => [
            'instruction' => 'Analyze this community post',
            'input' => $p->content,
            'metadata' => [
                'flair' => $p->flair,
                'media_type' => $p->media_type,
                'timestamp' => $p->created_at->toIso8601String(),
            ]
        ]);

        $comments = $commentsQuery->get()->map(fn($c) => [
            'instruction' => 'Analyze this community thread comment',
            'input' => $c->content,
        ]);

        $dataset = [
            'model' => 'Yuga 1.0',
            'harvested_at' => now()->toIso8601String(),
            'type' => $harvestType,
            'data' => $posts->merge($comments)
        ];

        $filename = 'yuga_training_' . ($this->option('quarantine') ? 'negative_' : 'positive_') . now()->format('Y_m_d_His') . '.json';
        Storage::disk('local')->put('yuga/training/' . $filename, json_empty_string($dataset) ? '' : json_encode($dataset, JSON_PRETTY_PRINT));

        $this->info("💎 Successfully harvested " . count($dataset['data']) . " samples.");
        $this->info("📂 Data saved to: storage/app/yuga/training/{$filename}");
        $this->warn("⚠️ REMINDER: This file contains public community data. Treat with care.");
    }
}
