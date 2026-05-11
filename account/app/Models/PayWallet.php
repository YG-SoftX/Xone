<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayWallet extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            if (empty($wallet->wallet_number)) {
                $wallet->wallet_number = 'YG' . strtoupper(bin2hex(random_bytes(6)));
            }
        });
    }
}
