<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AdminSmtpController extends Controller
{
    public function index(Request $request)
    {
        $query = SmtpAccount::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email_address', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $accounts = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total' => SmtpAccount::count(),
            'active' => SmtpAccount::where('is_active', true)->count(),
            'verified' => SmtpAccount::where('is_verified', true)->count(),
            'total_emails_today' => SmtpAccount::sum('emails_sent_today'),
        ];

        return view('admin.smtp.index', compact('accounts', 'stats'));
    }

    public function show($id)
    {
        $account = SmtpAccount::with('user')->findOrFail($id);
        $dnsConfig = $account->getDnsConfig();

        return view('admin.smtp.show', compact('account', 'dnsConfig'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('admin.smtp.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'domain' => 'required|string',
            'email_address' => 'required|email|unique:smtp_accounts,email_address',
            'display_name' => 'required|string',
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'required|string',
            'smtp_password' => 'required|string',
            'encryption' => 'required|in:tls,ssl,none',
            'daily_limit' => 'required|integer|min:1',
        ]);

        $account = SmtpAccount::create([
            'user_id' => $request->user_id,
            'domain' => $request->domain,
            'email_address' => $request->email_address,
            'display_name' => $request->display_name,
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_username' => $request->smtp_username,
            'smtp_password' => $request->smtp_password,
            'encryption' => $request->encryption,
            'daily_limit' => $request->daily_limit,
        ]);

        return redirect()->route('admin.smtp.show', $account->id)->with('success', 'SMTP account created.');
    }

    public function toggleActive($id)
    {
        $account = SmtpAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);

        return redirect()->back()->with('success', 'SMTP account status updated.');
    }

    public function verify($id)
    {
        $account = SmtpAccount::findOrFail($id);
        $account->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'SMTP account verified.');
    }

    public function testConnection($id)
    {
        $account = SmtpAccount::findOrFail($id);
        
        try {
            // Test SMTP connection
            $fp = @fsockopen($account->smtp_host, $account->smtp_port, $errno, $errstr, 5);
            
            if ($fp) {
                fclose($fp);
                return redirect()->back()->with('success', 'SMTP connection successful.');
            }
            
            return redirect()->back()->with('error', "SMTP connection failed: {$errstr}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'SMTP connection failed: ' . $e->getMessage());
        }
    }

    public function resetDailyCount($id)
    {
        $account = SmtpAccount::findOrFail($id);
        $account->update([
            'emails_sent_today' => 0,
            'last_reset_date' => today(),
        ]);

        return redirect()->back()->with('success', 'Daily email count reset.');
    }

    public function updateLimit(Request $request, $id)
    {
        $request->validate([
            'daily_limit' => 'required|integer|min:1',
        ]);

        $account = SmtpAccount::findOrFail($id);
        $account->update(['daily_limit' => $request->daily_limit]);

        return redirect()->back()->with('success', 'Daily limit updated.');
    }

    public function destroy($id)
    {
        $account = SmtpAccount::findOrFail($id);
        $account->delete();

        return redirect()->route('admin.smtp.index')->with('success', 'SMTP account deleted.');
    }
}
