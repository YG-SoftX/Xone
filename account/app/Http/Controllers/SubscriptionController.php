<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\PlanService;
use App\Services\StorageQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PlanService         $plans,
        private readonly PaymentService      $payments,
        private readonly StorageQuotaService $storage
    ) {}

    /**
     * Pricing / upgrade page.
     */
    public function upgrade(Request $request)
    {
        $user    = $request->user();
        $current = $this->plans->getPlan($user);
        $quota   = $this->storage->getQuota($user);

        return view('settings.billing.upgrade', [
            'plans'       => $this->plans->allPlans(),
            'current'     => $current,
            'quota'       => $quota,
            'upgrade_reason' => session('upgrade_reason'),
        ]);
    }

    /**
     * Start a plan subscription via YG Pay.
     * Personal plan is always free — this endpoint is only for business/enterprise + storage packs.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan'         => ['required', 'string', 'in:business,enterprise'],
            'billing_cycle'=> ['nullable', 'string', 'in:monthly,annual'],
            'storage_pack' => ['nullable', 'string', 'in:100gb,200gb,2tb'],
        ]);

        $user         = $request->user();
        $plan         = $validated['plan'];
        $storagePack  = $validated['storage_pack'] ?? null;
        $payUrl       = config('services.yg_pay.url', 'https://pay.ygxone.com');

        // Hand off to YG Pay for central processing
        return redirect()->away("{$payUrl}/checkout?" . http_build_query([
            'product'      => $plan,
            'storage_pack' => $storagePack,
            'user_id'      => $user->id,
            'callback_url' => route('billing.confirm'),
        ]));
    }

    /**
     * Add a storage pack to an existing plan via YG Pay.
     */
    public function addStoragePack(Request $request)
    {
        $validated = $request->validate([
            'storage_pack' => ['required', 'string', 'in:100gb,200gb,2tb'],
        ]);

        $user    = $request->user();
        $pack    = $validated['storage_pack'];
        $payUrl  = config('services.yg_pay.url', 'https://pay.ygxone.com');

        // Hand off to YG Pay for central processing
        return redirect()->away("{$payUrl}/checkout?" . http_build_query([
            'product'      => 'storage_pack',
            'variant'      => $pack,
            'user_id'      => $user->id,
            'callback_url' => route('billing.confirm'),
        ]));
    }

    /**
     * Cancel a paid plan — user reverts to Personal (free).
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($this->plans->getPlan($user) === 'personal') {
            return redirect()->back()->with('info', 'You are already on the free Personal plan.');
        }

        $this->plans->cancel($user);

        return redirect()->route('billing.upgrade')
            ->with('success', 'Plan cancelled. You have been moved to the free Personal plan. Your data is safe.');
    }
}
