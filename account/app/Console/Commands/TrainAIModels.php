<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YGAIPredictor;

class TrainAIModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yg-ai:train 
                            {--predict : Show predictions after training}
                            {--insights : Show AI insights summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train YG AI machine learning models on historical cron job data';

    protected YGAIPredictor $predictor;

    public function __construct(YGAIPredictor $predictor)
    {
        parent::__construct();
        $this->predictor = $predictor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 YG AI - Machine Learning Model Training');
        $this->newLine();
        $this->info('Training models on 90 days of historical data...');
        $this->newLine();

        $startTime = microtime(true);
        
        $result = $this->predictor->trainModels();
        
        $duration = round(microtime(true) - $startTime, 2);

        if ($result['success']) {
            $this->info("✅ Model training completed successfully!");
            $this->newLine();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Models Trained', $result['models_trained']],
                    ['Training Duration', "{$duration}s"],
                    ['Model Version', $result['model_version']],
                    ['Trained At', now()->format('Y-m-d H:i:s')],
                ]
            );

            // Show predictions if requested
            if ($this->option('predict')) {
                $this->showPredictions();
            }

            // Show insights if requested
            if ($this->option('insights')) {
                $this->showInsights();
            }

            $this->newLine();
            $this->info('💡 Schedule regular retraining via cron:');
            $this->line('   0 2 * * 0 /usr/bin/php /path/to/artisan yg-ai:train >> /dev/null 2>&1');
            $this->line('   (Every Sunday at 2 AM)');
        } else {
            $this->error('❌ Model training failed');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function showPredictions(): void
    {
        $this->newLine();
        $this->info('📊 AI Predictions for All Jobs');
        $this->newLine();

        $predictions = $this->predictor->getAllPredictions();

        foreach ($predictions as $jobName => $prediction) {
            $failurePred = $prediction['failure_prediction'];
            
            $riskEmoji = match($failurePred['risk_level']) {
                'critical' => '🚨',
                'high' => '⚠️',
                'medium' => '⚡',
                'low' => '✅',
                default => 'ℹ️',
            };

            $this->warn("Job: {$jobName}");
            $this->line("  {$riskEmoji} Failure Probability: {$failurePred['failure_probability']}%");
            $this->line("  🎯 Confidence: " . ($failurePred['confidence'] * 100) . "%");
            $this->line("  ⏰ Optimal Time Improvement: {$prediction['optimal_time']['expected_improvement']}");
            
            if (!empty($failurePred['recommendations'])) {
                $this->line("  💡 Recommendations:");
                foreach ($failurePred['recommendations'] as $rec) {
                    $this->line("     - {$rec}");
                }
            }
            
            $this->newLine();
        }
    }

    protected function showInsights(): void
    {
        $this->newLine();
        $this->info('🧠 YG AI Insights Summary');
        $this->newLine();

        $insights = $this->predictor->getInsights();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Jobs Analyzed', $insights['total_jobs_analyzed']],
                ['High Risk Jobs', count($insights['high_risk_jobs'])],
                ['Optimizable Jobs', count($insights['optimizable_jobs'])],
                ['Avg Failure Probability', $insights['average_failure_probability'] . '%'],
                ['Model Version', $insights['model_version']],
                ['Last Trained', $insights['last_trained']],
            ]
        );

        if (!empty($insights['high_risk_jobs'])) {
            $this->newLine();
            $this->warn('🚨 High Risk Jobs (Immediate Attention Required):');
            foreach ($insights['high_risk_jobs'] as $job) {
                $this->line("  - {$job}");
            }
        }

        if (!empty($insights['optimizable_jobs'])) {
            $this->newLine();
            $this->info('⚡ Jobs That Can Be Optimized:');
            foreach ($insights['optimizable_jobs'] as $job) {
                $this->line("  - {$job}");
            }
        }
    }
}
