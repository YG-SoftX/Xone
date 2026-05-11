<?php

namespace App\Modules\Pay\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayWallet;
use App\Models\PayTransaction;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display payment dashboard
     */
    public function index()
    {
        $wallet = auth()->user()->wallets()->first();
        
        if (!$wallet) {
            // Create default wallet
            $wallet = PayWallet::create([
                'user_id' => auth()->id(),
                'wallet_number' => 'YGW-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
                'balance' => 0.00,
                'currency' => 'USD',
            ]);
        }

        $recentTransactions = PayTransaction::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('pay.index', compact('wallet', 'recentTransactions'));
    }

    /**
     * Send money.
     *
     * payment_type = 'p2p'      → personal transfer, FREE (no fee).
     * payment_type = 'merchant' → business payment, 1.5% fee charged to sender.
     *
     * Personal users sending to friends pay zero. Merchants receive payments
     * with a 1.5% fee — they are the ones benefiting commercially.
     */
    public function sendMoney(Request $request)
    {
        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'amount'          => 'required|numeric|min:0.01',
            'description'     => 'nullable|string|max:500',
            'payment_type'    => 'nullable|string|in:p2p,merchant',
        ]);

        $paymentType  = $validated['payment_type'] ?? 'p2p';
        $grossAmount  = (float) $validated['amount'];

        // Merchant payments: 1.5% fee deducted from the amount the recipient gets.
        // Sender pays gross; recipient gets net; YG keeps the fee.
        $feeRate     = ($paymentType === 'merchant') ? 0.015 : 0.0;
        $fee         = round($grossAmount * $feeRate, 4);
        $netAmount   = round($grossAmount - $fee, 2);  // what recipient actually receives

        $senderWallet = auth()->user()->wallets()->firstOrFail();

        // Sender must have the full gross amount
        abort_unless($senderWallet->balance >= $grossAmount, 400, 'Insufficient balance');

        // Find recipient
        $recipient = \App\Models\User::where('email', $validated['recipient_email'])->first();
        abort_unless($recipient, 404, 'Recipient not found');

        $recipientWallet = $recipient->wallets()->first();
        if (!$recipientWallet) {
            $recipientWallet = PayWallet::create([
                'user_id'       => $recipient->id,
                'wallet_number' => 'YGW-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
                'balance'       => 0.00,
                'currency'      => 'USD',
            ]);
        }

        $transactionId = 'txn_' . Str::random(10);

        $transaction = PayTransaction::create([
            'transaction_id'      => $transactionId,
            'wallet_id'           => $senderWallet->id,
            'user_id'             => auth()->id(),
            'type'                => 'transfer',
            'payment_type'        => $paymentType,
            'amount'              => $grossAmount,
            'fee'                 => $fee,
            'fee_rate'            => $feeRate,
            'currency'            => 'USD',
            'status'              => 'completed',
            'recipient_wallet_id' => $recipientWallet->id,
            'recipient_email'     => $validated['recipient_email'],
            'description'         => $validated['description'],
            'ip_address'          => $request->ip(),
            'user_agent'          => $request->userAgent(),
            'processed_at'        => now(),
        ]);

        // Sender pays gross amount; recipient gets net (gross - fee)
        $senderWallet->decrement('balance', $grossAmount);
        $recipientWallet->increment('balance', $netAmount);
        // The $fee stays in the platform — it is never credited anywhere (platform revenue)

        $this->eventService->publish('pay', 'payment_completed', [
            'transaction_id' => $transactionId,
            'amount'         => $grossAmount,
            'net_amount'     => $netAmount,
            'fee'            => $fee,
            'payment_type'   => $paymentType,
            'recipient'      => $validated['recipient_email'],
            'type'           => 'transfer',
        ], auth()->id());

        $message = $paymentType === 'merchant'
            ? "Sent \${$grossAmount} USD to {$validated['recipient_email']} (fee: \${$fee})"
            : "Sent \${$grossAmount} USD to {$validated['recipient_email']}";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Add money to wallet (deposit)
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:stripe,paypal,razorpay',
        ]);

        $wallet = auth()->user()->wallets()->firstOrFail();

        // Process payment through provider (simplified - in production use actual payment gateway)
        $transactionId = 'dep_' . Str::random(10);
        
        $transaction = PayTransaction::create([
            'transaction_id' => $transactionId,
            'wallet_id' => $wallet->id,
            'user_id' => auth()->id(),
            'type' => 'deposit',
            'amount' => $validated['amount'],
            'currency' => 'USD',
            'status' => 'completed',
            'provider' => $validated['payment_method'],
            'description' => 'Wallet deposit',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'processed_at' => now(),
        ]);

        // Update wallet balance
        $wallet->increment('balance', $validated['amount']);

        // Publish event
        $this->eventService->publish(
            'pay',
            'payment_completed',
            [
                'transaction_id' => $transactionId,
                'amount' => $validated['amount'],
                'type' => 'deposit',
            ],
            auth()->id()
        );

        return redirect()->back()->with('success', "Added {$validated['amount']} USD to wallet");
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_account' => 'required|string',
        ]);

        $wallet = auth()->user()->wallets()->firstOrFail();
        
        // Check sufficient balance
        abort_unless($wallet->balance >= $validated['amount'], 400, 'Insufficient balance');

        $transactionId = 'wth_' . Str::random(10);
        
        $transaction = PayTransaction::create([
            'transaction_id' => $transactionId,
            'wallet_id' => $wallet->id,
            'user_id' => auth()->id(),
            'type' => 'withdrawal',
            'amount' => $validated['amount'],
            'currency' => 'USD',
            'status' => 'pending',
            'description' => 'Withdrawal to bank account',
            'metadata' => json_encode(['bank_account' => $validated['bank_account']]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Deduct from wallet immediately
        $wallet->decrement('balance', $validated['amount']);

        // Publish event
        $this->eventService->publish(
            'pay',
            'withdrawal_initiated',
            [
                'transaction_id' => $transactionId,
                'amount' => $validated['amount'],
            ],
            auth()->id()
        );

        return redirect()->back()->with('success', "Withdrawal of {$validated['amount']} USD initiated");
    }

    /**
     * View transaction details
     */
    public function showTransaction($transactionId)
    {
        $transaction = PayTransaction::where('transaction_id', $transactionId)->firstOrFail();
        
        // Verify ownership
        abort_unless($transaction->user_id === auth()->id(), 403);

        return view('pay.transaction', compact('transaction'));
    }
}
