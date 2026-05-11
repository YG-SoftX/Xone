<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\ChatMember;
use App\Models\ChatMessage;
use App\Models\ChatSpace;
use App\Models\User;
use App\Services\NeuralSummaryService;
use App\Services\ChatAdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    // ── Home ──────────────────────────────────────────────────────────────────

    public function index()
    {
        $spaces = $this->userSpaces();
        $first  = $spaces->first();

        if ($first) {
            return redirect()->route('chat.show', $first->id);
        }

        return view('chat.index', ['spaces' => $spaces, 'space' => null, 'messages' => collect()]);
    }

    // ── Show a space ──────────────────────────────────────────────────────────

    public function show(ChatSpace $space)
    {
        $this->authorizeSpace($space);

        // Mark all messages as read
        ChatMember::where('space_id', $space->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        $messages = ChatMessage::where('space_id', $space->id)
            ->where('is_deleted', false)
            ->with(['user', 'replyTo.user'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($m) => $m->created_at->toDateString());

        $space->load('members.user');
        $spaces = $this->userSpaces();

        return view('chat.show', compact('space', 'spaces', 'messages'));
    }

    // ── Send message ──────────────────────────────────────────────────────────

    public function send(Request $request, ChatSpace $space)
    {
        $this->authorizeSpace($space);

        $validated = $request->validate([
            'body'        => ['required_without:file', 'nullable', 'string', 'max:10000'],
            'reply_to_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
            'file'        => ['nullable', 'file', 'max:10240',
                              'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,zip'],
        ]);

        $attachments = [];

        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $file->getClientOriginalName());
            $safeName = mb_substr($safeName, 0, 255);
            $path     = $file->store("chat/{$space->id}", 'local');
            $attachments[] = [
                'name' => $safeName,
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        $message = new ChatMessage();
        $message->space_id    = $space->id;
        $message->user_id     = Auth::id();
        $message->reply_to_id = $validated['reply_to_id'] ?? null;
        $message->body        = $validated['body'] ?? '';
        $message->type        = $attachments ? 'file' : 'text';
        $message->attachments = $attachments ?: null;
        $message->save();

        // 🔴 REAL-TIME: Broadcast to all space members via WebSocket
        broadcast(new MessageSent($message))->toOthers();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message->load(['sender', 'replyTo.sender'])]);
        }

        return redirect()->route('chat.show', $space->id);
    }

    // ── Edit / Delete message ──────────────────────────────────────────────────

    public function editMessage(Request $request, ChatMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        $message->update(['body' => $validated['body'], 'is_edited' => true]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function deleteMessage(ChatMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $message->softDelete();
        return response()->json(['success' => true]);
    }

    // ── Reactions ─────────────────────────────────────────────────────────────

    public function react(Request $request, ChatMessage $message)
    {
        $validated = $request->validate(['emoji' => ['required', 'string', 'max:10']]);

        $reactions = $message->reactions ?? [];
        $userId    = Auth::id();
        $emoji     = $validated['emoji'];

        if (in_array($userId, $reactions[$emoji] ?? [])) {
            $message->removeReaction($userId, $emoji);
        } else {
            $message->addReaction($userId, $emoji);
        }

        return response()->json(['success' => true, 'reactions' => $message->fresh()->reactions]);
    }

    // ── Create space / DM ──────────────────────────────────────────────────────

    public function createSpace(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type'        => ['required', 'in:space,group'],
            'members'     => ['nullable', 'array', 'max:50'],
            'members.*'   => ['integer', 'exists:users,id'],
        ]);

        $space = new ChatSpace();
        $space->name        = $validated['name'];
        $space->description = $validated['description'] ?? null;
        $space->type        = $validated['type'];
        $space->created_by  = Auth::id();
        $space->save();

        // Add creator as owner
        $owner = new ChatMember();
        $owner->space_id = $space->id;
        $owner->user_id  = Auth::id();
        $owner->role     = 'owner';
        $owner->save();

        // Add other members
        foreach ($validated['members'] ?? [] as $userId) {
            if ($userId !== Auth::id()) {
                $existing = ChatMember::where('space_id', $space->id)->where('user_id', $userId)->first();
                if (!$existing) {
                    $member = new ChatMember();
                    $member->space_id = $space->id;
                    $member->user_id  = $userId;
                    $member->role     = 'member';
                    $member->save();
                }
            }
        }

        return redirect()->route('chat.show', $space->id)->with('success', 'Space created.');
    }

    public function startDm(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $otherId = (int) $validated['user_id'];

        // Find existing DM
        $existing = ChatSpace::where('type', 'dm')
            ->whereHas('members', fn ($q) => $q->where('user_id', Auth::id()))
            ->whereHas('members', fn ($q) => $q->where('user_id', $otherId))
            ->first();

        if ($existing) {
            return redirect()->route('chat.show', $existing->id);
        }

        $space = new ChatSpace();
        $space->type       = 'dm';
        $space->created_by = Auth::id();
        $space->save();
        $ownerMember = new ChatMember();
        $ownerMember->space_id = $space->id;
        $ownerMember->user_id  = Auth::id();
        $ownerMember->role     = 'owner';
        $ownerMember->save();

        $otherMember = new ChatMember();
        $otherMember->space_id = $space->id;
        $otherMember->user_id  = (int) $otherId;
        $otherMember->role     = 'member';
        $otherMember->save();

        return redirect()->route('chat.show', $space->id);
    }

    // ── Polling (cPanel-compatible real-time) ──────────────────────────────────

    /**
     * AJAX: return messages since a given ID (for long-polling).
     */
    public function poll(Request $request, ChatSpace $space)
    {
        $this->authorizeSpace($space);

        $since = max(0, (int) $request->query('since', 0));

        $messages = ChatMessage::where('space_id', $space->id)
            ->where('id', '>', $since)
            ->where('is_deleted', false)
            ->with('user')
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'user_name'  => $m->user?->name,
                'body'       => $m->body,
                'type'       => $m->type,
                'created_at' => $m->created_at->toIso8601String(),
                'is_edited'  => $m->is_edited,
                'reactions'  => $m->reactions,
            ]);

        // Mark read
        ChatMember::where('space_id', $space->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        return response()->json([
            'messages'  => $messages,
            'last_id'   => $messages->last() ? $messages->last()['id'] : $since,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ── Download attachment ────────────────────────────────────────────────────

    public function downloadAttachment(ChatMessage $message, int $index)
    {
        $this->authorizeSpace($message->space);

        $att = ($message->attachments ?? [])[$index] ?? null;
        abort_unless(
            $att
            && isset($att['path'])
            && Storage::disk('local')->exists($att['path'])
            && str_starts_with($att['path'], 'chat/'),
            404
        );

        return Storage::disk('local')->download($att['path'], $att['name'] ?? 'attachment');
    }

    // ── Neural Summary API ─────────────────────────────────────────────────────

    public function neuralSummary(Request $request, ChatSpace $space)
    {
        $this->authorizeSpace($space);

        $messages = ChatMessage::where('space_id', $space->id)
            ->where('is_deleted', false)
            ->with('user')
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'body'   => $m->body,
                'sender' => ['name' => $m->user?->name ?? 'Unknown'],
            ])->toArray();

        $summary = NeuralSummaryService::summarize($messages);

        return response()->json($summary);
    }

    // ── Typing Indicator ───────────────────────────────────────────────────────

    public function typing(ChatSpace $space)
    {
        $this->authorizeSpace($space);
        broadcast(new UserTyping($space->id, [
            'id'   => Auth::id(),
            'name' => Auth::user()->name,
        ]))->toOthers();
        return response()->json(['status' => 'ok']);
    }

    // ── Channel Ad API ─────────────────────────────────────────────────────────

    public function channelAd(ChatSpace $space)
    {
        $this->authorizeSpace($space);
        $count = ChatMessage::where('space_id', $space->id)->count();
        $ad    = ChatAdService::getChannelAd($space->id, $count);
        return response()->json(['ad' => $ad]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function userSpaces()
    {
        return ChatSpace::whereHas('members', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['latestMessage.user', 'members.user'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(fn ($s) => $s->unread = $s->unreadCount(Auth::id()));
    }

    private function authorizeSpace(ChatSpace $space): void
    {
        $isMember = ChatMember::where('space_id', $space->id)->where('user_id', Auth::id())->exists();
        abort_unless($isMember, 403, 'You are not a member of this space.');
    }
}
