<?php

namespace App\Modules\Meet\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MeetMeeting;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MeetController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display meetings dashboard
     */
    public function index()
    {
        $upcomingMeetings = MeetMeeting::where('user_id', auth()->id())
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->paginate(20);

        $pastMeetings = MeetMeeting::where('user_id', auth()->id())
            ->where('scheduled_at', '<', now())
            ->orWhere('status', 'completed')
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get();

        return view('meet.index', compact('upcomingMeetings', 'pastMeetings'));
    }

    /**
     * Schedule new meeting
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'require_password' => 'boolean',
            'password' => 'nullable|required_if:require_password,1|string|min:6',
            'attendees' => 'nullable|array',
            'attendees.*' => 'email',
        ]);

        $meeting = MeetMeeting::create([
            'user_id' => auth()->id(),
            'meeting_code' => $this->generateMeetingCode(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_participants' => $validated['max_participants'] ?? 100,
            'require_password' => $validated['require_password'] ?? false,
            'password_hash' => $validated['require_password'] ? bcrypt($validated['password']) : null,
            'settings' => json_encode([
                'screen_share' => true,
                'chat' => true,
                'recording' => true,
            ]),
        ]);

        // Add host as participant
        $meeting->participants()->create([
            'user_id' => auth()->id(),
            'display_name' => auth()->user()->name,
            'role' => 'host',
        ]);

        // Publish event
        $this->eventService->publish(
            'meet',
            'meeting_scheduled',
            [
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'scheduled_at' => $meeting->scheduled_at,
            ],
            auth()->id()
        );

        return redirect()->route('meet.show', $meeting->meeting_code)
            ->with('success', 'Meeting scheduled successfully!');
    }

    /**
     * Show meeting details
     */
    public function show($meetingCode)
    {
        $meeting = MeetMeeting::where('meeting_code', $meetingCode)->firstOrFail();

        // Check if user is participant
        $isParticipant = $meeting->participants()
            ->where('user_id', auth()->id())
            ->exists();

        abort_unless($isParticipant || $meeting->user_id === auth()->id(), 403);

        $participants = $meeting->participants()->get();
        $chatMessages = $meeting->chatMessages()->with('participant')->latest()->limit(50)->get();

        return view('meet.show', compact('meeting', 'participants', 'chatMessages'));
    }

    /**
     * Join meeting
     */
    public function join(Request $request, $meetingCode)
    {
        $meeting = MeetMeeting::where('meeting_code', $meetingCode)->firstOrFail();

        // Verify password if required
        if ($meeting->require_password) {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);
            
            abort_unless(password_verify($validated['password'], $meeting->password_hash), 403);
        }

        // Add participant
        $participant = $meeting->participants()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'display_name' => auth()->user()->name,
                'joined_at' => now(),
            ]
        );

        // Update meeting status to active if first participant
        if ($meeting->status === 'scheduled') {
            $meeting->update(['status' => 'active']);
        }

        return response()->json([
            'success' => true,
            'meeting' => $meeting,
            'participant' => $participant,
        ]);
    }

    /**
     * Send chat message
     */
    public function sendChatMessage(Request $request, $meetingCode)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $meeting = MeetMeeting::where('meeting_code', $meetingCode)->firstOrFail();
        
        $participant = $meeting->participants()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $chatMessage = $meeting->chatMessages()->create([
            'participant_id' => $participant->id,
            'message' => $validated['message'],
        ]);

        return response()->json(['message' => $chatMessage]);
    }

    /**
     * Leave meeting
     */
    public function leave($meetingCode)
    {
        $meeting = MeetMeeting::where('meeting_code', $meetingCode)->firstOrFail();
        
        $participant = $meeting->participants()
            ->where('user_id', auth()->id())
            ->first();

        if ($participant) {
            $participant->update(['left_at' => now()]);
        }

        return redirect()->route('meet.index')->with('success', 'Left meeting');
    }

    /**
     * Generate unique meeting code
     */
    protected function generateMeetingCode(): string
    {
        do {
            $code = strtoupper(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));
        } while (MeetMeeting::where('meeting_code', $code)->exists());

        return $code;
    }
}
