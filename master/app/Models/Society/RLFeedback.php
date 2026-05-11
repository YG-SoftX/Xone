<?php

namespace App\Models\Society;

use Illuminate\Database\Eloquent\Model;

class RLFeedback extends Model
{
    protected $table = 'society_rl_feedback';

    protected $fillable = [
        'post_id', 'signal', 'reason', 'confidence'
    ];

    public static function logSignal($postId, $signal, $reason, $confidence = 1.0)
    {
        return self::create([
            'post_id' => $postId,
            'signal' => $signal,
            'reason' => $reason,
            'confidence' => $confidence
        ]);
    }
}
