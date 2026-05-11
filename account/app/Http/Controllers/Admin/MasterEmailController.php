<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class MasterEmailController extends Controller
{
    private function getMailUrl(): string
    {
        return config('services.yg_mail.url', env('VITE_YG_MAIL_URL', 'http://localhost:3002'));
    }

    /**
     * Email Management Dashboard
     */
    public function index()
    {
        $mailUrl = $this->getMailUrl();
        $stats = $this->callApi($mailUrl, '/api/admin/stats');
        $recentEmails = $this->callApi($mailUrl, '/api/admin/emails/recent');
        $topSenders = $this->callApi($mailUrl, '/api/admin/top-senders');
        $failedJobs = $this->callApi($mailUrl, '/api/admin/queue/failed');
        $config = $this->callApi($mailUrl, '/api/admin/config');

        return view('admin.master.email.index', compact('stats', 'recentEmails', 'topSenders', 'failedJobs', 'config'));
    }

    /**
     * View All Emails
     */
    public function emails(Request $request)
    {
        $mailUrl = $this->getMailUrl();
        $params = $request->only(['search', 'folder', 'user_id', 'date_from', 'date_to', 'page']);
        $response = Http::timeout(10)->get($mailUrl . '/api/admin/emails', $params);
        $emails = $response->json()['data'] ?? [];

        return view('admin.master.email.emails', compact('emails'));
    }

    /**
     * View Single Email
     */
    public function showEmail($id)
    {
        $mailUrl = $this->getMailUrl();
        $email = $this->callApi($mailUrl, "/api/admin/emails/{$id}");

        return view('admin.master.email.show', compact('email'));
    }

    /**
     * Delete Email
     */
    public function deleteEmail($id)
    {
        $mailUrl = $this->getMailUrl();
        Http::timeout(10)->delete($mailUrl . "/api/admin/emails/{$id}");

        return redirect()->back()->with('success', 'Email deleted.');
    }

    /**
     * SMTP Accounts Management
     */
    public function smtpAccounts(Request $request)
    {
        $mailUrl = $this->getMailUrl();
        $params = $request->only(['search', 'status', 'page']);
        $response = Http::timeout(10)->get($mailUrl . '/api/admin/smtp', $params);
        $accounts = $response->json()['data'] ?? [];
        $stats = $response->json()['stats'] ?? [];

        return view('admin.master.email.smtp', compact('accounts', 'stats'));
    }

    /**
     * Toggle SMTP Account
     */
    public function toggleSmtp($id)
    {
        $mailUrl = $this->getMailUrl();
        Http::timeout(10)->post($mailUrl . "/api/admin/smtp/{$id}/toggle");

        return redirect()->back()->with('success', 'SMTP account toggled.');
    }

    /**
     * Test SMTP Connection
     */
    public function testSmtp($id)
    {
        $mailUrl = $this->getMailUrl();
        $result = $this->callApi($mailUrl, "/api/admin/smtp/{$id}/test", 'POST');

        $success = !($result['error'] ?? false);
        return redirect()->back()->with($success ? 'success' : 'error', $result['message'] ?? 'Test completed');
    }

    /**
     * Mail Configuration
     */
    public function config()
    {
        $mailUrl = $this->getMailUrl();
        $config = $this->callApi($mailUrl, '/api/admin/config');
        $imapStatus = $this->callApi($mailUrl, '/api/admin/imap/test', 'POST');

        return view('admin.master.email.config', compact('config', 'imapStatus'));
    }

    /**
     * Test IMAP Connection
     */
    public function testImap()
    {
        $mailUrl = $this->getMailUrl();
        $result = $this->callApi($mailUrl, '/api/admin/imap/test', 'POST');
        $success = !($result['error'] ?? false);

        return redirect()->back()->with($success ? 'success' : 'error', $result['message'] ?? 'IMAP test completed');
    }

    /**
     * Queue Management
     */
    public function queue()
    {
        $mailUrl = $this->getMailUrl();
        $failed = $this->callApi($mailUrl, '/api/admin/queue/failed');
        $pending = $this->callApi($mailUrl, '/api/admin/queue/pending');
        $stats = $this->callApi($mailUrl, '/api/admin/queue/stats');

        return view('admin.master.email.queue', compact('failed', 'pending', 'stats'));
    }

    /**
     * Retry Failed Jobs
     */
    public function retryQueue()
    {
        $mailUrl = $this->getMailUrl();
        Http::timeout(30)->post($mailUrl . '/api/admin/queue/retry-all');

        return redirect()->back()->with('success', 'Queue retry initiated.');
    }

    /**
     * Helper: Call YG Mail API
     */
    private function callApi(string $baseUrl, string $endpoint, string $method = 'GET', array $data = []): array
    {
        try {
            if ($method === 'GET') {
                $response = Http::timeout(10)->get($baseUrl . $endpoint);
            } else {
                $response = Http::timeout(10)->post($baseUrl . $endpoint, $data);
            }

            if ($response->successful()) {
                return $response->json() ?? ['success' => true];
            }
            return ['error' => 'API request failed', 'status' => $response->status()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
