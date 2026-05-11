<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactGroup extends Model
{
    protected $fillable = ['user_id', 'name', 'color'];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_group_pivot', 'group_id', 'contact_id');
    }
}
