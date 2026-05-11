<?php

namespace App\Models\Society;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RLFeedback extends Model
{
    protected $table = 'society_rl_feedback';

    protected $fillable = [
        'post_id',
        'initial_safety_score',
        'initial_verdict',
        'reward_signal',
        'feedback_type',
        'is_processed',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Log a new Reinforcement Learning signal autonomously
     */
    public static function logSignal(int $postId, float $reward, string $type, ?float $initialScore = null)
    {
        return self::create([
            'post_id' => $postId,
            'reward_signal' => $reward,
            'feedback_type' => $type,
            'initial_safety_score' => $initialScore,
            'is_processed' => false,
        ]);
    }
}
