<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Drive\File;
use Illuminate\Support\Facades\Storage;

class YugaHarvestPrivateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yuga:harvest-private {user_id? : Optional User ID to harvest specifically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest private YG Drive files opted-in for Yuga 1.0 Neural Training';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $this->info('Starting Private Knowledge Harvest...');

        $query = \App\Models\Drive\File::where('is_ai_training', true);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            $this->warn('No private files opted-in for AI training found.');
            return 0;
        }

        foreach ($files as $file) {
            $this->info("Harvesting: {$file->name} (User: {$file->user_id})");
            
            // Prepare the destination path in the Yuga Training Vault
            // Format: yuga/private/{user_id}/{filename}
            $destPath = "yuga/private/{$file->user_id}/" . $file->name;
            
            if (Storage::disk('local')->exists($file->storage_path)) {
                $content = Storage::disk('local')->get($file->storage_path);
                Storage::disk('local')->put($destPath, $content);
                $this->line(" -> Staged for Neural Ingestion: {$destPath}");
            } else {
                $this->error(" -> File missing in storage: {$file->storage_path}");
            }
        }

        $this->info('Private Knowledge Harvest Complete.');
        return 0;
    }
}
