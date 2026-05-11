<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemporaryData;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class PaymentGatewayController extends Controller
{
    /**
     * Initiate a payment request from an external ecosystem app.
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_key' => 'required|string',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|size:3',
            'gateway' => 'required|string',
            'return_url' => 'required|url',
            'cancel_url' => 'required|url',
            'callback_url' => 'nullable|url',
            'customer_email' => 'required|email',
            'external_ref' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // 1. Validate Client Key (Simplified for now, should check against registered projects)
        if ($request->client_key !== config('services.yg_account.client_key', 'yg-pay-unified-secret-key')) {
            return response()->json(['error' => 'Invalid client key'], 403);
        }

        try {
            $token = Str::random(60);
            
            // 2. Store payment intent data
            TemporaryData::create([
                'type' => 'ecosystem_payment',
                'identifier' => $token,
                'data' => [
                    'amount' => $request->amount,
                    'currency' => strtoupper($request->currency),
                    'gateway' => $request->gateway,
                    'return_url' => $request->return_url,
                    'cancel_url' => $request->cancel_url,
                    'callback_url' => $request->callback_url,
                    'customer_email' => $request->customer_email,
                    'external_ref' => $request->external_ref,
                ],
            ]);

            // 3. Return the redirect URL to our unified checkout
            $redirectUrl = route('payment.checkout', ['token' => $token]);

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectUrl,
                'token' => $token,
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
