<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'color',
        'icon',
    ];

    /**
     * Get the user that owns the folder.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder.
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get all child folders.
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get all documents in the folder.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the total number of documents in this folder and all subfolders.
     *
     * @return int
     */
    public function getDocumentCount()
    {
        $count = $this->documents()->count();

        foreach ($this->children as $child) {
            $count += $child->getDocumentCount();
        }

        return $count;
    }

    /**
     * Get the full path of the folder (e.g., "Root / Subfolder / Current").
     *
     * @return string
     */
    public function getFullPath()
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent) {
            $path[] = $current->parent->name;
            $current = $current->parent;
        }

        return implode(' / ', array_reverse($path));
    }
}
