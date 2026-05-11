<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'client_id',
        'client_secret',
        'redirect_uris',
        'scopes',
        'is_confidential',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'scopes' => 'array',
        'is_confidential' => 'boolean',
    ];

    protected $hidden = [
        'client_secret',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
