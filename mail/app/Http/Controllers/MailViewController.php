<?php

namespace App\Http\Controllers;

use App\Models\Mail;
use App\Models\Attachment;
use App\Models\MailSetting;
use App\Models\EmailQuota;
use App\Jobs\SendEmail;
use App\Jobs\CategorizeEmailJob;
use App\Jobs\AnalyzeEmailSentimentJob;
use App\Services\SpamProtection;
use App\Services\YgAccountEventPublisher;
use App\Mail\OutgoingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
class MailViewController extends Controller
{
    public function __construct(
        protected SpamProtection $spamProtection,
        protected YgAccountEventPublisher $eventPublisher
    ) {}

    /**
     * Show the inbox view with paginated emails.
     */
    public function inbox(Request $request)
    {
        $user = Auth::user();
        $folder = $request->query('folder', 'inbox');

        $query = Mail::where('user_id', $user->id);

        if ($folder === 'starred') {
            $query->where('is_starred', true);
        } elseif ($folder === 'trash') {
            $query->where('folder', 'trash');
        } elseif ($folder === 'spam') {
            $query->where('folder', 'spam');
        } elseif ($folder === 'sent') {
            $query->where('folder', 'sent');
        } elseif ($folder === 'drafts') {
            $query->where('folder', 'drafts');
        } else {
            // 'inbox' and default
            $query->where('folder', 'inbox');
        }

        $emails = $query->with('attachments')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        // Get unread counts per folder
        $unreadCounts = [
            'inbox' => Mail::where('user_id', $user->id)->where('folder', 'inbox')->where('read', false)->count(),
            'sent' => 0,
            'trash' => Mail::where('user_id', $user->id)->where('folder', 'trash')->count(),
            'spam' => Mail::where('user_id', $user->id)->where('folder', 'spam')->count(),
            'starred' => Mail::where('user_id', $user->id)->where('is_starred', true)->count(),
        ];

        // Get daily quota info
        $quota = EmailQuota::firstOrCreate(
            ['user_id' => $user->id, 'date' => now()->toDateString()],
            ['daily_sent' => 0, 'daily_limit' => 100]
        );

        return view('mail.inbox', [
            'emails' => $emails,
            'currentFolder' => $folder,
            'unreadCounts' => $unreadCounts,
            'quota' => $quota,
        ]);
    }

    /**
     * Show a single email.
     */
    public function show($id)
    {
        $user = Auth::user();
        $email = Mail::with('attachments')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Mark as read
        if (!$email->read) {
            $email->update(['read' => true]);
        }

        $unreadCounts = [
            'inbox' => Mail::where('user_id', $user->id)->where('folder', 'inbox')->where('read', false)->count(),
            'sent' => 0,
            'trash' => Mail::where('user_id', $user->id)->where('folder', 'trash')->count(),
            'spam' => Mail::where('user_id', $user->id)->where('folder', 'spam')->count(),
            'starred' => Mail::where('user_id', $user->id)->where('is_starred', true)->count(),
        ];

        return view('mail.show', [
            'email' => $email,
            'currentFolder' => 'inbox',
            'unreadCounts' => $unreadCounts,
            'quota' => null,
            'searchQuery' => '',
        ]);
    }

    /**
     * Send an email (POST from compose form).
     */
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $user = Auth::user();

        // Spam protection
        $validation = $this->spamProtection->validateEmail(
            $user->id,
            $request->to,
            $request->subject,
            $request->body
        );

        if (!$validation['allowed']) {
            return back()->withErrors(['email' => $validation['reason']]);
        }

        DB::transaction(function () use ($request, $user) {
            $mailRecord = Mail::create([
                'user_id' => $user->id,
                'to' => $request->to,
                'subject' => $request->subject,
                'body' => $request->body,
                'from' => $user->email,
                'folder' => 'sent',
                'read' => true,
            ]);

            // Dispatch jobs
            CategorizeEmailJob::dispatch($mailRecord->id);
            AnalyzeEmailSentimentJob::dispatch($mailRecord->id);

            SendEmail::dispatch(
                to: $request->to,
                subject: $request->subject,
                body: $request->body,
                fromEmail: $user->email,
                attachments: [],
                mailRecordId: $mailRecord->id
            );

            // Record quota usage
            $this->spamProtection->recordEmailSent($user->id);

            // Publish event
            $this->eventPublisher->publishEmailSent(
                $mailRecord->id,
                $user->id,
                $request->subject,
                [$request->to],
                $request->body
            );
        });

        return redirect()->route('mail.inbox', ['folder' => 'sent'])
            ->with('success', 'Email sent successfully!');
    }

    /**
     * Star/unstar an email.
     */
    public function toggleStar(Request $request, $id)
    {
        $user = Auth::user();
        $email = Mail::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $email->update(['is_starred' => !$email->is_starred]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_starred' => $email->is_starred]);
        }

        return back();
    }

    /**
     * Move email to trash or delete permanently.
     */
    public function delete(Request $request, $id)
    {
        $user = Auth::user();
        $email = Mail::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if ($email->folder === 'trash') {
            foreach ($email->attachments as $attachment) {
                Storage::disk('local')->delete($attachment->file_path);
                $attachment->delete();
            }
            $this->eventPublisher->publishEmailDeleted($email->id, $user->id);
            $email->delete();
        } else {
            $email->update(['folder' => 'trash']);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('mail.inbox')->with('success', 'Email moved to trash.');
    }

    /**
     * Poll for new emails (used by inbox auto-refresh).
     */
    public function poll()
    {
        $user = Auth::user();
        $count = Mail::where('user_id', $user->id)
            ->where('folder', 'inbox')
            ->where('read', false)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        return response()->json(['new_emails' => $count]);
    }

    /**
     * Move email to a different folder.
     */
    public function moveToFolder(Request $request, $id)
    {
        $request->validate(['folder' => 'required|string|in:inbox,sent,trash,spam,archive']);

        $user = Auth::user();
        $email = Mail::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $email->update(['folder' => $request->folder]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'folder' => $request->folder]);
        }

        return redirect()->route('mail.inbox')->with('success', 'Email moved to ' . $request->folder);
    }

    /**
     * Search emails.
     */
    /**
     * Reply to an email.
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $user = Auth::user();
        $original = Mail::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($request, $user, $original) {
            $mailRecord = Mail::create([
                'user_id' => $user->id,
                'to' => $original->from,
                'subject' => 'Re: ' . $original->subject,
                'body' => $request->body,
                'from' => $user->email,
                'folder' => 'sent',
                'read' => true,
            ]);

            SendEmail::dispatch(
                to: $original->from,
                subject: $mailRecord->subject,
                body: $request->body,
                fromEmail: $user->email,
                attachments: [],
                mailRecordId: $mailRecord->id
            );

            $this->spamProtection->recordEmailSent($user->id);
        });

        return redirect()->route('mail.inbox', ['folder' => 'sent'])
            ->with('success', 'Reply sent successfully!');
    }

    /**
     * Search emails.
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $q = trim($request->query('q', ''));

        $results = Mail::where('user_id', $user->id)
            ->where('folder', '!=', 'deleted')
            ->where(function ($query) use ($q) {
                $query->where('subject', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")
                    ->orWhere('from', 'like', "%{$q}%")
                    ->orWhere('to', 'like', "%{$q}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($results->items());
        }

        $unreadCounts = [
            'inbox' => Mail::where('user_id', $user->id)->where('folder', 'inbox')->where('read', false)->count(),
            'sent' => 0,
            'trash' => Mail::where('user_id', $user->id)->where('folder', 'trash')->count(),
            'spam' => Mail::where('user_id', $user->id)->where('folder', 'spam')->count(),
            'starred' => Mail::where('user_id', $user->id)->where('is_starred', true)->count(),
        ];

        return view('mail.inbox', [
            'emails' => $results,
            'currentFolder' => 'search',
            'searchQuery' => $q,
            'unreadCounts' => $unreadCounts,
            'quota' => null,
        ]);
    }
}
