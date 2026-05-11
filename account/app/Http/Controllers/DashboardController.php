<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Unified dashboard showing all services (Google-style)
     */
    public function index()
    {
        $user = auth()->user();

        // Get stats from all services
        $stats = [
            'unread_emails' => $this->getUnreadEmailCount($user->id),
            'storage_used' => $this->getTotalStorageUsed($user->id),
            'storage_quota' => 10737418240, // 10GB total
            'recent_files' => $this->getRecentFiles($user->id, 5),
            'recent_emails' => $this->getRecentEmails($user->id, 5),
            'upcoming_meetings' => $this->getUpcomingMeetings($user->id, 3),
            'recent_transactions' => $this->getRecentTransactions($user->id, 3),
            'total_balance' => $this->getWalletBalances($user->id),
        ];

        return view('account.dashboard', compact('stats'));
    }

    /**
     * Get unread email count
     */
    protected function getUnreadEmailCount(int $userId): int
    {
        try {
            return DB::table('mail_messages')
                ->join('mail_mailboxes', 'mail_messages.mailbox_id', '=', 'mail_mailboxes.id')
                ->where('mail_mailboxes.user_id', $userId)
                ->where('mail_messages.is_read', false)
                ->where('mail_messages.folder', 'inbox')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total storage used across all services
     */
    protected function getTotalStorageUsed(int $userId): int
    {
        $quota = DB::table('storage_quotas')
            ->where('user_id', $userId)
            ->first();

        if (!$quota) {
            return 0;
        }

        return $quota->mail_used_bytes + 
               $quota->drive_used_bytes + 
               $quota->docs_used_bytes + 
               $quota->meet_used_bytes;
    }

    /**
     * Get recent files from YG Drive
     */
    protected function getRecentFiles(int $userId, int $limit = 5)
    {
        try {
            return DB::table('drive_files')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get recent emails from YG Mail
     */
    protected function getRecentEmails(int $userId, int $limit = 5)
    {
        try {
            return DB::table('mail_messages')
                ->join('mail_mailboxes', 'mail_messages.mailbox_id', '=', 'mail_mailboxes.id')
                ->where('mail_mailboxes.user_id', $userId)
                ->where('mail_messages.folder', 'inbox')
                ->orderBy('mail_messages.received_at', 'desc')
                ->select('mail_messages.*')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get upcoming meetings from YG Meet
     */
    protected function getUpcomingMeetings(int $userId, int $limit = 3)
    {
        // Placeholder - implement when YG Meet is built
        return collect();
    }

    /**
     * Get recent transactions from YG Pay
     */
    protected function getRecentTransactions(int $userId, int $limit = 3)
    {
        try {
            return DB::table('pay_transactions')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get total wallet balance across all currencies
     */
    protected function getWalletBalances(int $userId)
    {
        try {
            return DB::table('pay_wallets')
                ->where('user_id', $userId)
                ->sum('balance');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Quick action: Compose email
     */
    public function composeEmail()
    {
        return view('mail.compose');
    }

    /**
     * Quick action: Upload file
     */
    public function uploadFile()
    {
        return view('drive.upload');
    }

    /**
     * Get activity feed
     */
    public function getActivityFeed(Request $request)
    {
        $activities = DB::table('activity_logs')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'activities' => $activities,
        ]);
    }
}
