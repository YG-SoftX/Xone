<?php

namespace App\Http\Controllers;

use App\Jobs\CategorizeEmailJob;
use App\Jobs\AnalyzeEmailSentimentJob;
use App\Models\Attachment;
use App\Models\Mail;
use App\Services\SpamProtection;
use App\Services\YgAccountEventPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail as MailFacade;
use Illuminate\Support\Facades\Storage;
use App\Mail\OutgoingMail;
use App\Jobs\SendEmail;
use Illuminate\Support\Facades\DB;

class MailController extends Controller
{
    protected $eventPublisher;

    public function __construct(protected SpamProtection $spamProtection, YgAccountEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    // GET /api/mail/inbox
    public function getInbox()
    {
        $user = Auth::user();

        return response()->json(
            Mail::where('user_id', $user->id)
                ->where('folder', 'inbox')
                ->with('attachments:id,mail_id,file_name,file_size,mime_type')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    // POST /api/mail/send
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per attachment
        ]);

        $user = Auth::user();

        // Spam protection checks
        $validation = $this->spamProtection->validateEmail(
            $user->id,
            $request->to,
            $request->subject,
            $request->body
        );
        if (!$validation['allowed']) {
            return response()->json(['status' => 'error', 'message' => $validation['reason']], 429);
        }

        // Use DB transaction for atomicity
        return DB::transaction(function () use ($request, $user) {
            // Handle attachments
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('uploads', 'local');
                    $attachmentPaths[] = [
                        'path' => Storage::disk('local')->path($path),
                        'name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Create mail record
            $mailRecord = Mail::create([
                'user_id' => $user->id,
                'to' => $request->to,
                'subject' => $request->subject,
                'body' => $request->body,
                'from' => $user->email,
                'folder' => 'sent',
                'read' => true,
            ]);

            // Trigger AI processing in background
            CategorizeEmailJob::dispatch($mailRecord->id);
            AnalyzeEmailSentimentJob::dispatch($mailRecord->id);

            // Queue actual send
            SendEmail::dispatch(
                to: $request->to,
                subject: $request->subject,
                body: $request->body,
                fromEmail: $user->email,
                attachments: $attachmentPaths,
                mailRecordId: $mailRecord->id
            );

            // Record quota usage
            $this->spamProtection->recordEmailSent($user->id);

            // Store attachment records
            foreach ($attachmentPaths as $attData) {
                $attachment = Attachment::create([
                    'mail_id'   => $mailRecord->id,
                    'file_name' => $attData['name'],
                    'file_path' => $attData['path'],
                    'mime_type' => $attData['mime_type'],
                    'file_size' => file_exists($attData['path']) ? filesize($attData['path']) : 0,
                ]);

                $this->eventPublisher->publishAttachmentUploaded(
                    $attachment->id,
                    $user->id,
                    $attData['name'],
                    $attData['mime_type'],
                    $attachment->file_size,
                    $attData['path'],
                    $mailRecord->id
                );
            }

            // Publish email sent event
            $this->eventPublisher->publishEmailSent(
                $mailRecord->id,
                $user->id,
                $request->subject,
                [$request->to],
                $request->body
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Email queued for sending',
                'mail' => $mailRecord,
            ]);
        });
    }

    // PATCH /api/mail/{id}/read
    public function markRead(Request $request, $id)
    {
        $user = Auth::user();
        $mail = Mail::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $mail->update(['read' => true]);

        return response()->json(['status' => 'success']);
    }

    // DELETE /api/mail/{id}
    public function delete(Request $request, $id)
    {
        $user = Auth::user();
        $mail = Mail::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($mail->folder === 'trash') {
            // Delete associated attachments
            foreach ($mail->attachments as $attachment) {
                Storage::disk('local')->delete($attachment->file_path);
                $attachment->delete();
            }

            // Publish email deleted event for search index cleanup
            $this->eventPublisher->publishEmailDeleted($mail->id, $user->id);

            $mail->delete();
            return response()->json(['status' => 'deleted']);
        }

        $mail->update(['folder' => 'trash']);
        return response()->json(['status' => 'trashed']);
    }

    // GET /api/mail/search?q=term
    public function search(Request $request)
    {
        $user = Auth::user();
        $q = trim($request->query('q', ''));

        if (!$q) {
            return response()->json([]);
        }

        $results = Mail::where('user_id', $user->id)
            ->where('folder', '!=', 'deleted')
            ->where(function ($query) use ($q) {
                $query->where('subject', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")
                    ->orWhere('from', 'like', "%{$q}%")
                    ->orWhere('to', 'like', "%{$q}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($results);
    }

    // POST /api/backup
    public function backup(Request $request)
    {
        $user = Auth::user();

        // Export all user's emails and attachments
        $mails = Mail::where('user_id', $user->id)
            ->with('attachments')
            ->orderBy('created_at', 'desc')
            ->get();

        $exportData = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'exported_at' => now()->toISOString(),
            ],
            'emails' => $mails->map(function ($mail) {
                return [
                    'from' => $mail->from,
                    'to' => $mail->to,
                    'subject' => $mail->subject,
                    'body' => $mail->body,
                    'folder' => $mail->folder,
                    'is_read' => $mail->is_read,
                    'created_at' => $mail->created_at->toISOString(),
                    'attachments' => $mail->attachments->map(function ($attachment) {
                        return [
                            'file_name' => $attachment->file_name,
                            'mime_type' => $attachment->mime_type,
                            'file_size' => $attachment->file_size,
                        ];
                    }),
                ];
            }),
        ];

        $filename = 'yg_mail_backup_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        // Save locally first
        $tempPath = 'temp/' . $filename;
        Storage::put($tempPath, json_encode($exportData, JSON_PRETTY_PRINT));
        
        // Sync to Unified Drive
        $drive = app(\App\Services\UnifiedDriveService::class);
        $fileObj = new \Illuminate\Http\File(Storage::path($tempPath));
        $drive->upload($fileObj, 'mail-backup');

        // Return appropriate response based on request type
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Successfully backed up {$mails->count()} emails to Sovereign YG Drive",
                'count' => $mails->count(),
                'filename' => $filename,
            ]);
        }

        return redirect()->route('mail.inbox')->with(
            'success',
            "Successfully backed up {$mails->count()} emails to Sovereign YG Drive"
        );

    }

    // GET /api/mail/attachment/{id}/download
    public function downloadAttachment($id)
    {
        $user = Auth::user();
        $attachment = Attachment::where('id', $id)
            ->whereHas('mail', fn($q) => $q->where('user_id', $user->id))
            ->firstOrFail();

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found');
        }

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->file_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    /**
     * Get AI-powered smart reply suggestions for an email
     * 
     * @param int $id Mail ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSmartReply($id)
    {
        $user = Auth::user();
        
        $mail = Mail::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            // Call YG Account AI Service
            $ygAccountUrl = config('services.yg_account.url', 'http://localhost:8000');
            $response = Http::timeout(5)->post($ygAccountUrl . '/api/ai/smart-reply', [
                'email_body' => strip_tags($mail->body ?? ''),
                'sender_name' => explode('@', $mail->from)[0] ?? '',
                'count' => 3
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'suggestions' => $response->json()['suggestions'] ?? []
                ]);
            }

            // Fallback to default replies
            return response()->json([
                'success' => true,
                'suggestions' => [
                    'Thank you for your email. I will review and respond shortly.',
                    'Thanks for reaching out! Let me check on this and get back to you.',
                    'I appreciate your message. I will follow up with you soon.'
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Smart reply generation failed', [
                'mail_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate suggestions'
            ], 500);
        }
    }
}
