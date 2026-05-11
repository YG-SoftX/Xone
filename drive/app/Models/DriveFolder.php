<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriveFolder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'color',
        'is_starred',
        'is_trashed',
        'description',
    ];

    protected $casts = [
        'is_starred' => 'boolean',
        'is_trashed' => 'boolean',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DriveFile::class, 'folder_id');
    }

    public function getTotalSizeAttribute(): int
    {
        return $this->files()->sum('size') +
               $this->children()->with('files')->get()->sum(fn($child) => $child->files->sum('size'));
    }

    public function getPathAttribute(): string
    {
        $parts = [$this->name];
        $parent = $this->parent;
        while ($parent) {
            array_unshift($parts, $parent->name);
            $parent = $parent->parent;
        }
        return implode(' / ', $parts);
    }
}
