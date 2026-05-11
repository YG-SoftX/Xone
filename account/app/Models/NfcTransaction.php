<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NfcTransaction extends Model
{
    protected $fillable = [
        'nfc_token_id',
        'user_id',
        'transaction_type',
        'amount',
        'currency',
        'status',
        'merchant',
        'merchant_id',
        'reference',
        'notes',
        'nfc_reader_location',
    ];

    public function nfcToken()
    {
        return $this->belongsTo(NfcToken::class, 'nfc_token_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createTransaction($nfcTokenId, $userId, $amount, $type = 'payment', $merchant = null)
    {
        $token = NfcToken::findOrFail($nfcTokenId);
        $wallet = Wallet::forUser($userId);

        // Check limits
        if ($amount > $token->transaction_limit) {
            throw new \Exception('Amount exceeds transaction limit');
        }

        $todayTotal = static::where('nfc_token_id', $nfcTokenId)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('amount');

        if ($todayTotal + $amount > $token->daily_limit) {
            throw new \Exception('Amount exceeds daily limit');
        }

        if ($wallet->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        return \DB::transaction(function () use ($token, $userId, $amount, $type, $merchant) {
            $wallet = Wallet::forUser($userId);
            $wallet->decrement('balance', $amount);

            $transaction = static::create([
                'nfc_token_id' => $token->id,
                'user_id' => $userId,
                'transaction_type' => $type,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'merchant' => $merchant,
                'reference' => 'NFC-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8)),
            ]);

            $token->update(['last_used_at' => now()]);

            Transaction::create([
                'user_id' => $userId,
                'description' => 'NFC Payment' . ($merchant ? ' at ' . $merchant : ''),
                'amount' => $amount,
                'type' => 'debit',
                'category' => 'NFC',
                'status' => 'completed',
            ]);

            return $transaction;
        });
    }
}
