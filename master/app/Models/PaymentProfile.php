<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProfile extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_verified' => 'boolean',
    ];
}
