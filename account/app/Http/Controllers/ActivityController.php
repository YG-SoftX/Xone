<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        try {
            $activities = ActivityLog::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'action' => $a->action,
                    'type' => $a->type,
                    'icon' => $a->icon,
                    'time' => $a->created_at?->diffForHumans(),
                    'date' => $a->created_at?->format('d M Y, H:i'),
                ]);

            return Inertia::render('Activity', [
                'activities' => $activities,
            ]);
        } catch (Exception $e) {
            Log::error('Activity index error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load activity.');
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            ActivityLog::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail()
                ->delete();

            return redirect()->back()->with('success', 'Activity deleted.');
        } catch (Exception $e) {
            Log::error('Activity delete error: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete activity.');
        }
    }
}
