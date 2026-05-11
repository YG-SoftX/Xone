<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\EventAttendee;
use App\Services\NepaliCalendarService;
use App\Services\YgAccountEventPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    protected $eventPublisher;

    public function __construct(YgAccountEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    // ── Views ──────────────────────────────────────────────────────────────────

    /**
     * Main calendar view (month by default, or week/day via ?view=).
     */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $allowed = ['month', 'week', 'day', 'agenda'];
        $view    = in_array($request->query('view'), $allowed, true)
            ? $request->query('view')
            : 'month';

        // Validate date — fall back to today if invalid
        $rawDate = $request->query('date', now()->toDateString());
        try {
            $current = Carbon::createFromFormat('Y-m-d', $rawDate);
            if (!$current) throw new \Exception();
        } catch (\Exception) {
            $current = now();
        }

        $calendars = Calendar::where('user_id', $user->id)->get();

        // Compute date range for the selected view
        [$rangeStart, $rangeEnd] = match ($view) {
            'week'   => [$current->copy()->startOfWeek(), $current->copy()->endOfWeek()],
            'day'    => [$current->copy()->startOfDay(), $current->copy()->endOfDay()],
            'agenda' => [$current->copy()->startOfDay(), $current->copy()->addDays(30)->endOfDay()],
            default  => [$current->copy()->startOfMonth()->startOfWeek(), $current->copy()->endOfMonth()->endOfWeek()],
        };

        $calendarIds = $calendars->where('is_visible', true)->pluck('id');

        $events = CalendarEvent::whereIn('calendar_id', $calendarIds)
            ->where('status', '!=', 'cancelled')
            ->inRange($rangeStart, $rangeEnd)
            ->with(['calendar', 'attendees'])
            ->orderBy('starts_at')
            ->get();

        // Build month grid (only for month view)
        $grid = $view === 'month' ? $this->buildMonthGrid($current, $events) : [];

        // Nepali Calendar Data
        $bsToday    = NepaliCalendarService::adToBS($current->year, $current->month, $current->day);
        $bsHolidays = NepaliCalendarService::getHolidays($bsToday['year']);

        return view('calendar.index', [
            'calendars'      => $calendars,
            'events'         => $events,
            'upcomingEvents' => $events->filter(fn($e) => $e->starts_at->isFuture())->take(5)->values(),
            'view'           => $view,
            'current'        => $current,
            'rangeStart'     => $rangeStart,
            'rangeEnd'       => $rangeEnd,
            'grid'           => $grid,
            'bsToday'        => $bsToday,
            'bsHolidays'     => $bsHolidays,
        ]);
    }

    // ── Nepali Calendar API (returns BS month data as JSON) ────────────────────

    public function nepaliApi(Request $request)
    {
        $request->validate([
            'year'  => ['nullable', 'integer', 'min:2000', 'max:2090'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $bsYear  = (int) $request->query('year',  NepaliCalendarService::adToBS(now()->year, now()->month, now()->day)['year']);
        $bsMonth = (int) $request->query('month', NepaliCalendarService::adToBS(now()->year, now()->month, now()->day)['month']);

        // Clamp to supported range
        $bsYear  = max(2000, min(2090, $bsYear));
        $bsMonth = max(1, min(12, $bsMonth));

        return response()->json([
            'year'         => $bsYear,
            'month'        => $bsMonth,
            'month_name'   => NepaliCalendarService::$monthsNepaliEng[$bsMonth],
            'month_nepali' => NepaliCalendarService::$monthsNepali[$bsMonth],
            'days'         => NepaliCalendarService::getDaysInMonth($bsYear, $bsMonth),
            'holidays'     => NepaliCalendarService::getHolidays($bsYear),
        ]);
    }

    // ── CRUD ───────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_id'     => ['required', 'integer', 'exists:calendars,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'location'        => ['nullable', 'string', 'max:500'],
            'meet_link'       => ['nullable', 'url', 'max:500'],
            'starts_at'       => ['required', 'date'],
            'ends_at'         => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day'         => ['boolean'],
            'color'           => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'status'          => ['nullable', 'string', 'in:confirmed,tentative,cancelled'],
            'visibility'      => ['nullable', 'string', 'in:public,private,internal'],
            'recurrence_rule' => ['nullable', 'string', 'max:255'],
            'recurrence_until'=> ['nullable', 'date'],
            'reminders'       => ['nullable', 'array'],
            'attendees'       => ['nullable', 'array', 'max:100'],
            'attendees.*.email' => ['required', 'email'],
            'attendees.*.name'  => ['nullable', 'string', 'max:255'],
        ]);

        $calendar = Calendar::where('id', $validated['calendar_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $event = CalendarEvent::create([
            'calendar_id'      => $calendar->id,
            'user_id'          => Auth::id(),
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'location'         => $validated['location'] ?? null,
            'meet_link'        => $validated['meet_link'] ?? null,
            'starts_at'        => $validated['starts_at'],
            'ends_at'          => $validated['ends_at'],
            'all_day'          => $validated['all_day'] ?? false,
            'color'            => $validated['color'] ?? null,
            'status'           => $validated['status'] ?? 'confirmed',
            'visibility'       => $validated['visibility'] ?? 'public',
            'recurrence_rule'  => $validated['recurrence_rule'] ?? null,
            'recurrence_until' => $validated['recurrence_until'] ?? null,
            'reminders'        => $validated['reminders'] ?? null,
        ]);

        // Add organiser as first attendee
        $organizer = new EventAttendee();
        $organizer->event_id     = $event->id;
        $organizer->user_id      = Auth::id();
        $organizer->email        = Auth::user()->email;
        $organizer->name         = Auth::user()->name;
        $organizer->response     = 'accepted';
        $organizer->is_organizer = true;
        $organizer->save();

        // Prepare attendees array for event publishing
        $attendeesArray = [];

        // Add other attendees
        foreach ($validated['attendees'] ?? [] as $att) {
            $attendee = EventAttendee::where('event_id', $event->id)
                ->where('email', $att['email'])
                ->first();
            if (!$attendee) {
                $attendee = new EventAttendee();
                $attendee->event_id  = $event->id;
                $attendee->email     = $att['email'];
                $attendee->name      = $att['name'] ?? null;
                $attendee->response  = 'pending';
                $attendee->save();
            }
            $attendeesArray[] = [
                'email' => $att['email'],
                'name'  => $att['name'] ?? null,
            ];
        }

        // Publish calendar event created for cross-module sync
        $this->eventPublisher->publishEventCreated(
            $event->id,
            Auth::id(),
            $event->title,
            $event->starts_at->toIso8601String(),
            $event->ends_at->toIso8601String(),
            $event->location,
            $event->description,
            $attendeesArray
        );

        return redirect()
            ->route('calendar.index', ['date' => $event->starts_at->toDateString()])
            ->with('success', 'Event created.');
    }

    public function show(CalendarEvent $event)
    {
        abort_unless($event->user_id === Auth::id(), 403);
        $event->load(['calendar', 'attendees']);
        return view('calendar.show', compact('event'));
    }

    public function edit(CalendarEvent $event)
    {
        abort_unless($event->user_id === Auth::id(), 403);
        $calendars = Calendar::where('user_id', Auth::id())->get();
        $event->load(['attendees']);
        return view('calendar.edit', compact('event', 'calendars'));
    }

    public function update(Request $request, CalendarEvent $event)
    {
        abort_unless($event->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'calendar_id' => ['required', 'integer', 'exists:calendars,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location'    => ['nullable', 'string', 'max:500'],
            'meet_link'   => ['nullable', 'url', 'max:500'],
            'starts_at'   => ['required', 'date'],
            'ends_at'     => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day'     => ['boolean'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'status'      => ['nullable', 'string', 'in:confirmed,tentative,cancelled'],
        ]);

        $event->calendar_id = $validated['calendar_id'];
        $event->fill(array_diff_key($validated, ['calendar_id' => true]));
        $event->save();

        // Publish event updated for attendee notifications
        $this->eventPublisher->publishEventUpdated(
            $event->id,
            Auth::id(),
            $event->title
        );

        return redirect()
            ->route('calendar.index', ['date' => $event->starts_at->toDateString()])
            ->with('success', 'Event updated.');
    }

    public function destroy(CalendarEvent $event)
    {
        abort_unless($event->user_id === Auth::id(), 403);
        $date = $event->starts_at->toDateString();
        
        // Publish event deleted before removing from database
        $this->eventPublisher->publishEventDeleted(
            $event->id,
            Auth::id()
        );
        
        $event->delete();
        return redirect()->route('calendar.index', ['date' => $date])->with('success', 'Event deleted.');
    }

    // ── Quick-create (AJAX) ────────────────────────────────────────────────────

    public function quickCreate(Request $request)
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at'   => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day'   => ['boolean'],
        ]);

        $calendar = Calendar::where('user_id', Auth::id())
            ->where('is_primary', true)
            ->firstOrFail();

        $event = new CalendarEvent($validated);
        $event->calendar_id = $calendar->id;
        $event->user_id     = Auth::id();
        $event->status      = 'confirmed';
        $event->save();

        return response()->json(['success' => true, 'event' => $event]);
    }

    // ── Attendee response ──────────────────────────────────────────────────────

    public function respondToInvite(Request $request, CalendarEvent $event)
    {
        $validated = $request->validate([
            'response' => ['required', 'in:accepted,declined,tentative'],
        ]);

        EventAttendee::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->firstOrFail()
            ->update(['response' => $validated['response'], 'responded_at' => now()]);

        return redirect()->back()->with('success', 'Response recorded.');
    }

    // ── Calendar CRUD ──────────────────────────────────────────────────────────

    public function createCalendar(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'type'        => ['nullable', 'string', 'in:personal,work,birthdays,holidays,other'],
        ]);

        $cal = new Calendar($validated);
        $cal->user_id = Auth::id();
        $cal->save();

        return redirect()->back()->with('success', 'Calendar created.');
    }

    public function deleteCalendar(Calendar $calendar)
    {
        abort_unless($calendar->user_id === Auth::id(), 403);
        abort_if($calendar->is_primary, 422, 'Cannot delete the primary calendar.');
        $calendar->delete();
        return redirect()->route('calendar.index')->with('success', 'Calendar deleted.');
    }

    // ── API: events as JSON (for AJAX calendar rendering) ─────────────────────

    public function apiEvents(Request $request)
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date'],
        ]);

        $start = Carbon::parse($request->query('start', now()->startOfMonth()->toIso8601String()));
        $end   = Carbon::parse($request->query('end',   now()->endOfMonth()->toIso8601String()));

        $calendarIds = Calendar::where('user_id', Auth::id())
            ->where('is_visible', true)
            ->pluck('id');

        $events = CalendarEvent::whereIn('calendar_id', $calendarIds)
            ->where('status', '!=', 'cancelled')
            ->inRange($start, $end)
            ->with('calendar')
            ->get()
            ->map(fn ($e) => [
                'id'        => $e->id,
                'title'     => $e->title,
                'start'     => $e->starts_at->toIso8601String(),
                'end'       => $e->ends_at->toIso8601String(),
                'allDay'    => $e->all_day,
                'color'     => $e->display_color,
                'location'  => $e->location,
                'meet_link' => (str_starts_with($e->meet_link ?? '', 'https://') ? $e->meet_link : null),
                'url'       => route('calendar.show', $e->id),
            ]);

        return response()->json($events);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function buildMonthGrid(Carbon $current, $events): array
    {
        $start = $current->copy()->startOfMonth()->startOfWeek();
        $end   = $current->copy()->endOfMonth()->endOfWeek();

        $grid  = [];
        $day   = $start->copy();

        while ($day <= $end) {
            $dateStr    = $day->toDateString();
            $dayEvents  = $events->filter(fn ($e) => $e->starts_at->toDateString() === $dateStr)->values();

            $grid[] = [
                'date'       => $dateStr,
                'day'        => $day->day,
                'isToday'    => $day->isToday(),
                'isCurrentMonth' => $day->month === $current->month,
                'isWeekend'  => $day->isWeekend(),
                'events'     => $dayEvents,
            ];

            $day->addDay();
        }

        return $grid;
    }
}
