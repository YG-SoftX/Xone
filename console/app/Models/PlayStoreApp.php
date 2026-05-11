<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayStoreApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'package_name',
        'app_name',
        'version',
        'status',
        'metadata',
        'price',
        'is_paid',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
