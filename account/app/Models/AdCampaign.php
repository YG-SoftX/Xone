<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'advertiser_id',
        'title',
        'content',
        'image_url',
        'target_url',
        'budget',
        'daily_budget',
        'bidding_strategy', // cpc, cpm
        'bid_amount',
        'status', // active, paused, completed, pending_review
        'targeting_criteria', // JSON: location, language, demographics, interests
        'total_impressions',
        'total_clicks',
    ];

    protected $casts = [
        'targeting_criteria' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function advertiser()
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function getCtrAttribute()
    {
        if ($this->total_impressions == 0) return 0;
        return ($this->total_clicks / $this->total_impressions) * 100;
    }
}
