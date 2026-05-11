<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AdminSupportController extends Controller
{
    /**
     * List all tickets with filters.
     */
    public function index(Request $request)
    {
        try {
            $query = SupportTicket::with(['user', 'assignee']);

            // Filter by status
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Filter by priority
            if ($request->filled('priority')) {
                $query->byPriority($request->priority);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter by service
            if ($request->filled('service')) {
                $query->byService($request->service);
            }

            // Filter by assigned admin
            if ($request->filled('assigned_to')) {
                $query->assignedTo($request->assigned_to);
            }

            // Search by ticket number, subject, or description
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $allowedSorts = ['created_at', 'updated_at', 'resolved_at', 'priority', 'status'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $tickets = $query->paginate(50);

            // Stats dashboard
            $stats = $this->getStats();

            // Admin users for assignment dropdown
            $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

            return view('admin.support.index', compact('tickets', 'stats', 'adminUsers'));
        } catch (Exception $e) {
            Log::error('AdminSupportController@index failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load support tickets.');
        }
    }

    /**
     * View a single ticket with all replies.
     */
    public function show($id)
    {
        try {
            $ticket = SupportTicket::with([
                'user',
                'assignee',
                'replies' => function ($query) {
                    $query->with('user')->orderBy('created_at', 'asc');
                },
            ])->findOrFail($id);

            return view('admin.support.show', compact('ticket'));
        } catch (Exception $e) {
            Log::error('AdminSupportController@show failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.support.index')
                ->with('error', 'Failed to load support ticket.');
        }
    }

    /**
     * Assign a ticket to an admin user.
     */
    public function assign(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'assigned_to' => 'required|exists:users,id',
            ]);

            $ticket = SupportTicket::findOrFail($id);

            $ticket->update([
                'assigned_to' => $validated['assigned_to'],
            ]);

            // Auto-update status to in_progress if still open
            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }

            $assignee = User::find($validated['assigned_to']);

            Log::info("Ticket assigned", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'assigned_to' => $assignee ? $assignee->name : 'unknown',
            ]);

            return redirect()->back()->with('success', "Ticket assigned to {$assignee->name}.");
        } catch (Exception $e) {
            Log::error('AdminSupportController@assign failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to assign ticket.');
        }
    }

    /**
     * Staff reply to a ticket.
     */
    public function reply(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string',
            ]);

            $ticket = SupportTicket::findOrFail($id);

            $reply = $ticket->replies()->create([
                'user_id' => session('admin_user_id'),
                'message' => $validated['message'],
                'is_staff' => true,
            ]);

            // Auto-update status to in_progress if still open
            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }

            Log::info("Staff replied to ticket", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'reply_id' => $reply->id,
            ]);

            return redirect()->back()->with('success', 'Reply posted successfully.');
        } catch (Exception $e) {
            Log::error('AdminSupportController@reply failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to post reply.');
        }
    }

    /**
     * Mark a ticket as resolved.
     */
    public function resolve($id)
    {
        try {
            $ticket = SupportTicket::findOrFail($id);

            $ticket->resolve();

            Log::info("Ticket resolved", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

            return redirect()->back()->with('success', "Ticket '{$ticket->ticket_number}' marked as resolved.");
        } catch (Exception $e) {
            Log::error('AdminSupportController@resolve failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to resolve ticket.');
        }
    }

    /**
     * Mark a ticket as closed.
     */
    public function close($id)
    {
        try {
            $ticket = SupportTicket::findOrFail($id);

            $ticket->close();

            Log::info("Ticket closed", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

            return redirect()->back()->with('success', "Ticket '{$ticket->ticket_number}' closed.");
        } catch (Exception $e) {
            Log::error('AdminSupportController@close failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to close ticket.');
        }
    }

    /**
     * Reopen a resolved or closed ticket.
     */
    public function reopen($id)
    {
        try {
            $ticket = SupportTicket::findOrFail($id);

            $ticket->update([
                'status' => 'in_progress',
                'resolved_at' => null,
            ]);

            Log::info("Ticket reopened", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

            return redirect()->back()->with('success', "Ticket '{$ticket->ticket_number}' reopened.");
        } catch (Exception $e) {
            Log::error('AdminSupportController@reopen failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to reopen ticket.');
        }
    }

    /**
     * Delete a ticket and all its replies.
     */
    public function destroy($id)
    {
        try {
            $ticket = SupportTicket::findOrFail($id);
            $ticketNumber = $ticket->ticket_number;

            // Delete all replies first (should cascade, but be explicit)
            $ticket->replies()->delete();
            $ticket->delete();

            Log::info("Ticket deleted", [
                'ticket_id' => $id,
                'ticket_number' => $ticketNumber,
            ]);

            return redirect()->route('admin.support.index')
                ->with('success', "Ticket '{$ticketNumber}' deleted.");
        } catch (Exception $e) {
            Log::error('AdminSupportController@destroy failed: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete ticket.');
        }
    }

    /**
     * Bulk actions for tickets.
     */
    public function bulkAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:assign-selected,resolve-selected,close-selected,reopen-selected,delete-selected',
                'ticket_ids' => 'required|array',
                'ticket_ids.*' => 'exists:support_tickets,id',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $ticketIds = $validated['ticket_ids'];
            $action = $validated['action'];
            $affectedCount = 0;

            DB::beginTransaction();

            try {
                switch ($action) {
                    case 'assign-selected':
                        if (empty($validated['assigned_to'])) {
                            DB::rollBack();
                            return redirect()->back()->with('error', 'Please select an admin to assign.');
                        }

                        $affectedCount = SupportTicket::whereIn('id', $ticketIds)->update([
                            'assigned_to' => $validated['assigned_to'],
                            'status' => 'in_progress',
                        ]);

                        $assignee = User::find($validated['assigned_to']);
                        $message = "{$affectedCount} ticket(s) assigned to {$assignee->name}.";
                        break;

                    case 'resolve-selected':
                        $affectedCount = SupportTicket::whereIn('id', $ticketIds)
                            ->whereNotIn('status', ['resolved', 'closed'])
                            ->update([
                                'status' => 'resolved',
                                'resolved_at' => Carbon::now(),
                            ]);
                        $message = "{$affectedCount} ticket(s) marked as resolved.";
                        break;

                    case 'close-selected':
                        $affectedCount = SupportTicket::whereIn('id', $ticketIds)
                            ->whereNotIn('status', ['closed'])
                            ->update([
                                'status' => 'closed',
                                'resolved_at' => DB::raw('COALESCE(resolved_at, NOW())'),
                            ]);
                        $message = "{$affectedCount} ticket(s) closed.";
                        break;

                    case 'reopen-selected':
                        $affectedCount = SupportTicket::whereIn('id', $ticketIds)
                            ->whereIn('status', ['resolved', 'closed'])
                            ->update([
                                'status' => 'in_progress',
                                'resolved_at' => null,
                            ]);
                        $message = "{$affectedCount} ticket(s) reopened.";
                        break;

                    case 'delete-selected':
                        // Delete replies first
                        TicketReply::whereIn('ticket_id', $ticketIds)->delete();
                        $affectedCount = SupportTicket::whereIn('id', $ticketIds)->count();
                        SupportTicket::whereIn('id', $ticketIds)->delete();
                        $message = "{$affectedCount} ticket(s) deleted.";
                        break;

                    default:
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Invalid bulk action.');
                }

                DB::commit();

                Log::info("Bulk action performed", [
                    'action' => $action,
                    'ticket_count' => count($ticketIds),
                    'affected_count' => $affectedCount,
                ]);

                return redirect()->back()->with('success', $message);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('AdminSupportController@bulkAction failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Bulk action failed. Please try again.');
        }
    }

    /**
     * Get stats dashboard data.
     */
    private function getStats(): array
    {
        $openCount = SupportTicket::whereNotIn('status', ['closed', 'resolved'])->count();
        $inProgressCount = SupportTicket::where('status', 'in_progress')->count();
        $resolvedCount = SupportTicket::where('status', 'resolved')->count();
        $closedCount = SupportTicket::where('status', 'closed')->count();

        // Calculate average response time (time between ticket creation and first reply)
        $avgResponseTime = DB::table('support_tickets')
            ->join('ticket_replies', 'support_tickets.id', '=', 'ticket_replies.ticket_id')
            ->where('ticket_replies.is_staff', true)
            ->selectRaw(
                'AVG(TIMESTAMPDIFF(SECOND, support_tickets.created_at, ticket_replies.created_at)) as avg_seconds'
            )
            ->value('avg_seconds');

        $avgResponseTimeFormatted = null;
        if ($avgResponseTime !== null) {
            $avgResponseTimeFormatted = $this->formatDuration((int) $avgResponseTime);
        }

        return [
            'open' => $openCount,
            'in_progress' => $inProgressCount,
            'resolved' => $resolvedCount,
            'closed' => $closedCount,
            'avg_response_time' => $avgResponseTimeFormatted,
            'by_priority' => SupportTicket::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'by_category' => SupportTicket::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'by_service' => SupportTicket::selectRaw('service, COUNT(*) as count')
                ->groupBy('service')
                ->pluck('count', 'service')
                ->toArray(),
        ];
    }

    /**
     * Format seconds into human-readable duration.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            $minutes = (int) ($seconds / 60);
            return "{$minutes}m";
        }

        if ($seconds < 86400) {
            $hours = (int) ($seconds / 3600);
            $minutes = (int) (($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }

        $days = (int) ($seconds / 86400);
        $hours = (int) (($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }
}
