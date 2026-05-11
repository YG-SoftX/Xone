<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\UserSubscription;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\ActivityLog;
use App\Models\KYC;
use App\Models\NfcToken;
use App\Models\SmtpAccount;
use App\Models\PlatformFeature;
use App\Models\YgService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MasterDashboardController extends Controller
{
    /**
     * Master Dashboard - Aggregates stats from ALL YG services
     */
    public function index()
    {
        // YG Account Stats
        $accountStats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'total_wallet_balance' => Wallet::sum('balance'),
            'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
            'monthly_revenue' => Transaction::where('type', 'credit')
                ->whereMonth('created_at', now()->month)->sum('amount'),
            'pending_kyc' => KYC::where('status', 'pending')->count(),
            'active_nfc_tokens' => NfcToken::where('is_active', true)->count(),
            'smtp_accounts' => SmtpAccount::where('is_active', true)->count(),
            'total_invoices' => Invoice::count(),
            'paid_invoices' => Invoice::where('status', 'paid')->sum('total'),
        ];

        // Ecosystem Service Stats
        $payStats   = $this->getServiceStats('pay');
        $mailStats  = $this->getServiceStats('mail');
        $driveStats = $this->getServiceStats('drive');
        $docxStats  = $this->getServiceStats('docx');
        $aiStats    = $this->getServiceStats('ai');
        $playStats  = $this->getServiceStats('playstore');

        // Service Health
        $services = YgService::orderBy('service_name')->get();

        // Platform Features
        $features = PlatformFeature::orderBy('feature_name')->get();

        // Recent Activity Across Services
        $recentActivity = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Top Users by Wallet Balance
        $topUsers = User::with('wallet')
            ->orderByDesc(function($query) {
                $query->select('balance')->from('wallets')
                    ->whereColumn('wallets.user_id', 'users.id');
            })
            ->limit(10)
            ->get();

        // Recent KYC Submissions
        $recentKyc = KYC::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.master.dashboard', compact(
            'accountStats',
            'payStats',
            'mailStats',
            'driveStats',
            'docxStats',
            'aiStats',
            'playStats',
            'services',
            'features',
            'recentActivity',
            'topUsers',
            'recentKyc'
        ));
    }

    /**
     * Fetch stats from a YG service via API
     */
    private function getServiceStats(string $serviceKey): array
    {
        $serviceUrls = [
            'pay'       => 'https://pay.ygxone.com',
            'mail'      => 'https://mail.ygxone.com',
            'drive'     => 'https://drive.ygxone.com',
            'docx'      => 'https://docx.ygxone.com',
            'ai'        => 'https://ai.ygxone.com',
            'playstore' => 'https://play.ygxone.com',
        ];

        $baseUrl = $serviceUrls[$serviceKey] ?? null;
        if (!$baseUrl) {
            return ['error' => 'Service URL not configured'];
        }

        $apiUrl = $baseUrl . '/api/admin/stats';
        if ($serviceKey === 'ai') {
            $apiUrl = $baseUrl . '/api/index.php?action=admin_stats';
        }

        try {
            $response = Http::timeout(5)->get($apiUrl);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Service might be down
        }

        return ['status' => 'unreachable'];
    }

    /**
     * Quick Actions
     */
    public function quickAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'target' => 'nullable|string',
        ]);

        $user = User::findOrFail(session('admin_user_id'));

        ActivityLog::record(
            $user->id,
            'Admin quick action: ' . $request->action,
            'Admin',
            '⚡',
            $request->ip()
        );

        return redirect()->back()->with('success', 'Action executed.');
    }
}
