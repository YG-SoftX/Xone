<?php

namespace App\Services;

use App\Models\Project;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;

class AiService
{
    protected $ygAiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->ygAiUrl = config('services.yg_ai.url', 'https://ai.ygxone.com');
        $this->apiKey = config('services.yg_ai.api_key');
    }

    /**
     * Make AI request via YG AI
     */
    public function makeRequest(Project $project, array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Project-ID' => $project->id,
            ])->post($this->ygAiUrl . '/api/v1/chat/completions', [
                'model' => $params['model'] ?? 'gpt-3.5-turbo',
                'messages' => $params['messages'],
                'max_tokens' => $params['max_tokens'] ?? 1000,
                'temperature' => $params['temperature'] ?? 0.7,
            ]);

            if ($response->failed()) {
                throw new \Exception('YG AI API error: ' . $response->body());
            }

            $data = $response->json();

            // Log usage
            $this->logUsage($project, $params['model'], $data);

            return $data;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Log AI usage for billing
     */
    protected function logUsage(Project $project, string $model, array $response): void
    {
        $usage = $response['usage'] ?? [];
        
        AiUsageLog::logUsage(
            $project,
            $model,
            $usage['prompt_tokens'] ?? 0,
            $usage['completion_tokens'] ?? 0
        );
    }

    /**
     * Get available AI models
     */
    public function getAvailableModels(): array
    {
        return [
            'gpt-4' => [
                'name' => 'GPT-4',
                'provider' => 'OpenAI',
                'max_tokens' => 8192,
                'pricing' => config('ai.pricing.gpt-4'),
            ],
            'gpt-4-turbo' => [
                'name' => 'GPT-4 Turbo',
                'provider' => 'OpenAI',
                'max_tokens' => 128000,
                'pricing' => config('ai.pricing.gpt-4-turbo'),
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'provider' => 'OpenAI',
                'max_tokens' => 16385,
                'pricing' => config('ai.pricing.gpt-3.5-turbo'),
            ],
            'claude-3-opus' => [
                'name' => 'Claude 3 Opus',
                'provider' => 'Anthropic',
                'max_tokens' => 200000,
                'pricing' => config('ai.pricing.claude-3-opus'),
            ],
            'claude-3-sonnet' => [
                'name' => 'Claude 3 Sonnet',
                'provider' => 'Anthropic',
                'max_tokens' => 200000,
                'pricing' => config('ai.pricing.claude-3-sonnet'),
            ],
            'gemini-pro' => [
                'name' => 'Gemini Pro',
                'provider' => 'Google',
                'max_tokens' => 32768,
                'pricing' => config('ai.pricing.gemini-pro'),
            ],
        ];
    }

    /**
     * Get project AI usage statistics
     */
    public function getUsageStats(Project $project, ?string $period = 'month'): array
    {
        $query = $project->aiUsageLogs();

        if ($period === 'week') {
            $query->whereDate('created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month);
        } elseif ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $logs = $query->get();

        return [
            'total_requests' => $logs->count(),
            'total_prompt_tokens' => $logs->sum('prompt_tokens'),
            'total_completion_tokens' => $logs->sum('completion_tokens'),
            'total_cost' => $logs->sum('cost'),
            'models_used' => $logs->groupBy('model')->map->count()->toArray(),
            'average_cost_per_request' => $logs->count() > 0 ? $logs->sum('cost') / $logs->count() : 0,
        ];
    }

    /**
     * Check if project has exceeded quota
     */
    public function hasExceededQuota(Project $project): bool
    {
        $monthlyLimit = config('ai.monthly_limits.projects', 10000);
        
        $currentUsage = $project->aiUsageLogs()
            ->whereMonth('created_at', now()->month)
            ->count();

        return $currentUsage >= $monthlyLimit;
    }

    /**
     * Get remaining quota
     */
    public function getRemainingQuota(Project $project): int
    {
        $monthlyLimit = config('ai.monthly_limits.projects', 10000);
        
        $currentUsage = $project->aiUsageLogs()
            ->whereMonth('created_at', now()->month)
            ->count();

        return max(0, $monthlyLimit - $currentUsage);
    }

    /**
     * Create prompt template
     */
    public function createPromptTemplate(Project $project, array $data): array
    {
        // Store in project settings or dedicated table
        $templates = $project->settings['prompt_templates'] ?? [];
        
        $templateId = 'tpl_' . bin2hex(random_bytes(8));
        
        $templates[$templateId] = [
            'name' => $data['name'],
            'content' => $data['content'],
            'model' => $data['model'] ?? 'gpt-3.5-turbo',
            'created_at' => now()->toISOString(),
        ];

        $project->update([
            'settings' => array_merge($project->settings ?? [], [
                'prompt_templates' => $templates,
            ]),
        ]);

        return ['id' => $templateId, ...$templates[$templateId]];
    }

    /**
     * Get prompt templates
     */
    public function getPromptTemplates(Project $project): array
    {
        return $project->settings['prompt_templates'] ?? [];
    }
}
