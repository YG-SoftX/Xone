<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_revenue',
        'total_payouts',
        'platform_profit_balance',
        'last_payout_at',
    ];

    /**
     * Get the available profit for withdrawal
     */
    public static function getAvailableProfit()
    {
        $balance = self::firstOrCreate([], [
            'total_revenue' => 0,
            'total_payouts' => 0,
            'platform_profit_balance' => 0
        ]);
        
        return $balance->platform_profit_balance;
    }
}
