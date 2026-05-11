<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'user_id',
        'signature',
        'vacation_mode',
        'vacation_message',
        'vacation_start',
        'vacation_end',
        'primary_color',
        'sidebar_type',
        'brand_name',
        'hero_title',
        'hero_subtitle',
        'cta_text',
        'show_landing_page',
    ];

    protected $casts = [
        'vacation_mode' => 'boolean',
        'show_landing_page' => 'boolean',
        'vacation_start' => 'date',
        'vacation_end' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
