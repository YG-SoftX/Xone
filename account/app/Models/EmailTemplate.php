<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'body',
        'is_html',
        'category',
        'variables',
    ];

    protected $casts = [
        'is_html' => 'boolean',
        'variables' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function render(array $data = []): string
    {
        $body = $this->body;
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        return $body;
    }
}
