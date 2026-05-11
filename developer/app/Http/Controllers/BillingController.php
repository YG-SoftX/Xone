<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(): View
    {
        $overview = $this->api->get('developer-console/billing');
        $account  = $this->api->get('developer-console/billing/account');
        $usage    = $this->api->get('developer-console/billing/usage');
        $invoices = $this->api->get('developer-console/billing/invoices', ['per_page' => 5]);

        return view('billing.index', compact('overview', 'account', 'usage', 'invoices'));
    }

    public function invoices(): View
    {
        $invoices = $this->api->get('developer-console/billing/invoices', ['per_page' => 25]);
        return view('billing.invoices', compact('invoices'));
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'nullable|string|max:200',
            'tax_id'       => 'nullable|string|max:50',
            'address'      => 'nullable|string|max:300',
            'city'         => 'nullable|string|max:100',
            'country'      => 'nullable|string|size:2',
            'postal_code'  => 'nullable|string|max:20',
        ]);

        $result = $this->api->put('developer-console/billing/account', array_filter($data));

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Billing account updated.');
    }

    public function payInvoice(int $id): RedirectResponse
    {
        $result = $this->api->post("developer-console/billing/invoices/{$id}/pay");

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Payment processed.');
    }

    public function addPaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type'       => 'required|in:card,bank_account',
            'last_four'  => 'required|string|size:4',
            'brand'      => 'nullable|string|max:30',
            'exp_month'  => 'nullable|integer|between:1,12',
            'exp_year'   => 'nullable|integer|min:' . date('Y'),
            'set_default'=> 'boolean',
        ]);

        $result = $this->api->post('developer-console/billing/payment-methods', $data);

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Payment method added.');
    }

    public function removePaymentMethod(string $methodId): RedirectResponse
    {
        $result = $this->api->delete("developer-console/billing/payment-methods/{$methodId}");

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Payment method removed.');
    }
}
