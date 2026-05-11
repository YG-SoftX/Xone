<?php

namespace App\Http\Controllers;

use App\Models\NfcToken;
use App\Models\NfcTransaction;
use App\Models\PlatformFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Inertia\Inertia;

class NfcController extends Controller
{
    public function index(Request $request)
    {
        if (!PlatformFeature::isEnabled('nfc')) {
            return back()->with('error', 'NFC payments are currently disabled.');
        }

        $user = $request->user();
        $tokens = NfcToken::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        $transactions = NfcTransaction::where('user_id', $user->id)
            ->with('nfcToken')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        return Inertia::render('NfcPayments', [
            'tokens' => $tokens,
            'transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'merchant' => $t->merchant,
                'status' => $t->status,
                'reference' => $t->reference,
                'date' => $t->created_at?->format('d M Y, H:i'),
            ]),
        ]);
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:phone,card,ring,wristband',
        ]);

        $token = NfcToken::generateToken(
            $request->user()->id,
            $request->device_name,
            $request->device_type
        );

        return redirect()->back()->with('success', 'NFC token generated: ' . $token->token_uid);
    }

    public function toggleToken(Request $request, $id)
    {
        $token = NfcToken::where('user_id', $request->user()->id)->findOrFail($id);
        $token->update(['is_active' => !$token->is_active]);

        return redirect()->back()->with('success', 'Token ' . ($token->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function deleteToken(Request $request, $id)
    {
        NfcToken::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Token deleted.');
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'token_uid' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'merchant' => 'nullable|string',
        ]);

        $token = NfcToken::where('token_uid', $request->token_uid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$token->is_active) {
            return response()->json(['error' => 'Token is not active'], 400);
        }

        try {
            $transaction = NfcTransaction::createTransaction(
                $token->id,
                $request->user()->id,
                $request->amount,
                'payment',
                $request->merchant
            );

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
            ]);
        } catch (Exception $e) {
            Log::error('NFC payment failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
