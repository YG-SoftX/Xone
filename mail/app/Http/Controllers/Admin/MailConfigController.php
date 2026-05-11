<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class MailConfigController extends Controller
{
    public function index()
    {
        $config = [
            'smtp_host' => config('mail.mailers.smtp.host', ''),
            'smtp_port' => config('mail.mailers.smtp.port', ''),
            'smtp_username' => config('mail.mailers.smtp.username', ''),
            'incoming_host' => config('mail.incoming.host', ''),
            'incoming_port' => config('mail.incoming.port', ''),
            'incoming_username' => config('mail.incoming.username', ''),
            'yg_account_url' => config('services.yg_account.url', ''),
            'queue_connection' => config('queue.default', 'sync'),
        ];

        // Check if IMAP extension is loaded
        $imapLoaded = extension_loaded('imap');

        return view('admin.config', compact('config', 'imapLoaded'));
    }

    public function update(Request $request)
    {
        // We can't modify .env at runtime, so we show instructions
        return redirect()->back()->with('info',
            'To update mail configuration, edit the .env file directly. '
            . 'After changes, run: php artisan config:cache'
        );
    }

    public function testImap(Request $request)
    {
        if (!extension_loaded('imap')) {
            return back()->with('error', 'IMAP extension is not loaded on this server. Install php-imap.');
        }

        $host = config('mail.incoming.host');
        $port = config('mail.incoming.port', 993);
        $username = config('mail.incoming.username');
        $password = config('mail.incoming.password');

        if (!$host || !$username || !$password) {
            return back()->with('error', 'IMAP credentials not configured in .env');
        }

        $mailboxPath = '{' . $host . ':' . $port . '/ssl/novalidate-cert}INBOX';

        try {
            $connection = @imap_open($mailboxPath, $username, $password, OP_READONLY, 3);
            if ($connection) {
                $msgCount = imap_num_msg($connection);
                imap_close($connection);
                return back()->with('success', "IMAP connected successfully. Inbox has {$msgCount} messages.");
            }
            return back()->with('error', 'IMAP connection failed: ' . imap_last_error());
        } catch (\Exception $e) {
            return back()->with('error', 'IMAP error: ' . $e->getMessage());
        }
    }
}
