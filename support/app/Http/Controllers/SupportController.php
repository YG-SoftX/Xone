<?php

namespace App\Http\Controllers;

use App\Mail\TicketNotification;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Support portal dashboard — show ticket summary and quick links.
     */
    public function dashboard(): View
    {
        $user = auth()->user();

        $stats = [
            'open'      => Ticket::where('user_id', $user->id)->whereIn('status', ['open', 'pending'])->count(),
            'resolved'  => Ticket::where('user_id', $user->id)->where('status', 'resolved')->count(),
            'total'     => Ticket::where('user_id', $user->id)->count(),
        ];

        $recentTickets = Ticket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user'          => $user,
            'stats'         => $stats,
            'recentTickets' => $recentTickets,
        ]);
    }

    /**
     * List all tickets for the authenticated user.
     */
    public function tickets(Request $request): View
    {
        $user = auth()->user();
        $status = $request->query('status');

        $query = Ticket::where('user_id', $user->id);

        if ($status && in_array($status, ['open', 'pending', 'resolved', 'closed'])) {
            $query->where('status', $status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('tickets.index', [
            'tickets' => $tickets,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show the create ticket form.
     */
    public function createTicket(): View
    {
        return view('tickets.create');
    }

    /**
     * Store a new ticket.
     */
    public function storeTicket(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:billing,technical,account,feature,other',
            'priority' => 'required|string|in:low,medium,high',
            'message'  => 'required|string|max:10000',
        ]);

        $ticket = Ticket::create([
            'user_id'  => auth()->id(),
            'subject'  => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status'   => 'open',
            'messages' => [['role' => 'user', 'body' => $data['message'], 'created_at' => now()->toISOString()]],
        ]);

        // Send notification
        try {
            $user = auth()->user();
            Mail::to($user->email)->queue(new TicketNotification(
                ticket: $ticket,
                notificationType: 'created',
                messageBody: $data['message'],
                recipientName: $user->name,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to queue ticket creation notification', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully. We\'ll get back to you shortly.');
    }

    /**
     * Show a single ticket with its message thread.
     */
    public function showTicket($id): View
    {
        $user = auth()->user();
        $ticket = Ticket::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        return view('tickets.show', ['ticket' => $ticket]);
    }

    /**
     * Reply to an existing ticket.
     */
    public function replyTicket(Request $request, $id)
    {
        $data = $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        $user = auth()->user();
        $ticket = Ticket::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $messages = $ticket->messages ?? [];
        $messages[] = ['role' => 'user', 'body' => $data['message'], 'created_at' => now()->toISOString()];

        $ticket->update([
            'messages' => $messages,
            'status'   => 'pending', // re-open if closed
        ]);

        // Send notification
        try {
            $user = auth()->user();
            Mail::to($user->email)->queue(new TicketNotification(
                ticket: $ticket,
                notificationType: 'user_replied',
                messageBody: $data['message'],
                recipientName: $user->name,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to queue reply notification', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Your reply has been added.');
    }

    /**
     * Close a ticket.
     */
    public function closeTicket($id)
    {
        $user = auth()->user();
        $ticket = Ticket::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $ticket->update(['status' => 'closed']);

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket closed.');
    }
}
