namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PayTransaction;
use App\Models\PayWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get authoritative YG Pay Wallet
            $wallet = PayWallet::where('user_id', $user->id)->first() ?? new PayWallet(['balance' => 0, 'currency' => 'USD']);

            $transactions = PayTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'description' => $t->description,
                    'amount' => (float) $t->amount,
                    'type' => $t->type,
                    'status' => $t->status,
                    'date' => $t->created_at?->format('d M Y'),
                ]);

            // Subscriptions are now centrally managed in YG Pay
            $subscriptions = DB::table('pay_subscriptions')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return Inertia::render('Payments', [
                'wallet' => [
                    'balance' => (float) $wallet->balance,
                    'currency' => $wallet->currency,
                    'wallet_number' => $wallet->wallet_number ?? 'Not Initialized',
                ],
                'transactions' => $transactions,
                'subscriptions' => $subscriptions,
                'pay_url' => config('services.yg_pay.url', env('VITE_YG_PAY_URL', 'http://localhost:3001')),
            ]);
        } catch (Exception $e) {
            Log::error('Payments index error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load payment information.');
        }
    }

    /**
     * Redirect to YG Pay for wallet reloading.
     */
    public function reloadWallet(Request $request)
    {
        $payUrl = config('services.yg_pay.url', env('VITE_YG_PAY_URL', 'http://localhost:3001'));
        return redirect($payUrl . '/reload?amount=' . $request->amount);
    }

    /**
     * Redirect to YG Pay for subscription management.
     */
    public function subscribe(Request $request)
    {
        $payUrl = config('services.yg_pay.url', env('VITE_YG_PAY_URL', 'http://localhost:3001'));
        return redirect($payUrl . '/plans');
    }

    /**
     * Redirect to YG Pay for cancellation.
     */
    public function cancelSubscription(Request $request, $id)
    {
        $payUrl = config('services.yg_pay.url', env('VITE_YG_PAY_URL', 'http://localhost:3001'));
        return redirect($payUrl . '/subscriptions/' . $id . '/cancel');
    }
}
