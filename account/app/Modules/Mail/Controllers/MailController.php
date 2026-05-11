<?php

namespace App\Modules\Mail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Mailbox;
use App\Models\MailMessage;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail as MailFacade;

class MailController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display inbox
     */
    public function index(Request $request)
    {
        $mailbox = auth()->user()->mailboxes()->first();
        
        if (!$mailbox) {
            // Create default mailbox
            $mailbox = Mailbox::create([
                'user_id' => auth()->id(),
                'email' => auth()->user()->email,
                'display_name' => auth()->user()->name,
                'storage_quota_bytes' => 5368709120, // 5GB
            ]);
        }

        $folder = $request->input('folder', 'inbox');
        
        $messages = $mailbox->messages()
            ->where('folder', $folder)
            ->orderBy('received_at', 'desc')
            ->paginate(50);

        $unreadCount = $mailbox->messages()
            ->where('folder', 'inbox')
            ->where('is_read', false)
            ->count();

        return view('mail.inbox', compact('mailbox', 'messages', 'folder', 'unreadCount'));
    }

    /**
     * Show compose form
     */
    public function compose()
    {
        return view('mail.compose');
    }

    /**
     * Send email
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        $mailbox = auth()->user()->mailboxes()->firstOrFail();

        // Save to sent folder
        $message = MailMessage::create([
            'mailbox_id' => $mailbox->id,
            'message_id' => uniqid() . '@ygxone.com',
            'from_email' => $mailbox->email,
            'from_name' => $mailbox->display_name,
            'to_emails' => json_encode([$validated['to']]),
            'subject' => $validated['subject'],
            'body_plain' => strip_tags($validated['body']),
            'body_html' => $validated['body'],
            'folder' => 'sent',
            'is_read' => true,
            'received_at' => now(),
        ]);

        // Handle attachments
        $totalAttachmentSize = 0;
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('mail-attachments/' . date('Y/m/d'));
                
                $message->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'storage_path' => $path,
                ]);

                $totalAttachmentSize += $file->getSize();
            }
        }

        // Update storage used
        $mailbox->increment('storage_used_bytes', $totalAttachmentSize);
        
        // Update user's storage quota
        $quota = \App\Models\StorageQuota::where('user_id', auth()->id())->first();
        if ($quota) {
            $quota->increment('mail_used_bytes', $totalAttachmentSize);
        }

        try {
            MailFacade::html(htmlspecialchars_decode($validated['body']), function ($mail) use ($validated, $mailbox) {
                $mail->to($validated['to'])
                    ->subject($validated['subject'])
                    ->from($mailbox->email, $mailbox->display_name);
            });
        } catch (\Exception $e) {
            // Log error but don't fail - email is saved in sent folder
            \Log::error('Failed to send email via SMTP', ['error' => $e->getMessage()]);
        }

        // Publish event for real-time sync
        $this->eventService->publish(
            'mail',
            'email_sent',
            [
                'message_id' => $message->id,
                'to' => $validated['to'],
                'subject' => $validated['subject'],
            ],
            auth()->id(),
            $mailbox->id
        );

        return redirect()->route('mail.index')->with('success', 'Email sent successfully!');
    }

    /**
     * View email message
     */
    public function show($messageId)
    {
        $message = MailMessage::findOrFail($messageId);
        
        // Verify ownership
        abort_unless($message->mailbox->user_id === auth()->id(), 403);

        // Mark as read
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('mail.show', compact('message'));
    }

    /**
     * Delete message (move to trash)
     */
    public function destroy($messageId)
    {
        $message = MailMessage::findOrFail($messageId);
        
        // Verify ownership
        abort_unless($message->mailbox->user_id === auth()->id(), 403);

        // Move to trash
        $message->update(['folder' => 'trash']);

        // Publish event
        $this->eventService->publish(
            'mail',
            'email_deleted',
            ['message_id' => $message->id],
            auth()->id(),
            $message->mailbox_id
        );

        return redirect()->back()->with('success', 'Message moved to trash');
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($attachmentId)
    {
        $attachment = \App\Models\MailAttachment::findOrFail($attachmentId);
        
        // Verify ownership
        abort_unless($attachment->message->mailbox->user_id === auth()->id(), 403);

        return Storage::download($attachment->storage_path, $attachment->filename);
    }
}
