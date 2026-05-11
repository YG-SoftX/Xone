<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'cost' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public static function logUsage(Project $project, string $model, int $promptTokens, int $completionTokens): self
    {
        $pricing = config('ai.pricing', [
            'gpt-4' => ['prompt' => 0.00003, 'completion' => 0.00006],
            'gpt-3.5-turbo' => ['prompt' => 0.0000015, 'completion' => 0.000002],
        ]);

        $cost = ($promptTokens * ($pricing[$model]['prompt'] ?? 0.00001)) +
                ($completionTokens * ($pricing[$model]['completion'] ?? 0.00002));

        return self::create([
            'project_id' => $project->id,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'cost' => $cost,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
