<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search across all services (MySQL full-text search)
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'type' => 'nullable|in:all,mail,drive,docs,contacts',
        ]);

        $query = $request->input('q');
        $type = $request->input('type', 'all');
        $userId = auth()->id();

        $results = [];

        // Search emails
        if ($type === 'all' || $type === 'mail') {
            $emails = DB::table('mail_messages')
                ->join('mail_mailboxes', 'mail_messages.mailbox_id', '=', 'mail_mailboxes.id')
                ->where('mail_mailboxes.user_id', $userId)
                ->where(function ($q) use ($query) {
                    $q->where('subject', 'LIKE', "%{$query}%")
                      ->orWhere('body_plain', 'LIKE', "%{$query}%");
                })
                ->select(
                    'mail_messages.id',
                    'mail_messages.subject as title',
                    DB::raw('SUBSTRING(mail_messages.body_plain, 1, 200) as snippet'),
                    'mail_messages.from_email',
                    'mail_messages.from_name',
                    'mail_messages.received_at as date',
                    DB::raw("'mail' as type"),
                    DB::raw("CONCAT('/mail/', mail_messages.id) as url")
                )
                ->orderBy('mail_messages.received_at', 'desc')
                ->limit(10)
                ->get();

            $results = array_merge($results, $emails->toArray());
        }

        // Search files
        if ($type === 'all' || $type === 'drive') {
            $files = DB::table('drive_files')
                ->where('user_id', $userId)
                ->where('name', 'LIKE', "%{$query}%")
                ->select(
                    'id',
                    'name as title',
                    DB::raw("CONCAT(mime_type, ' - ', ROUND(size_bytes / 1024, 2), ' KB') as snippet"),
                    'created_at as date',
                    DB::raw("'' as from_email"),
                    DB::raw("'' as from_name"),
                    DB::raw("'drive' as type"),
                    DB::raw("CONCAT('/drive/file/', id) as url")
                )
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $results = array_merge($results, $files->toArray());
        }

        // Search documents
        if ($type === 'all' || $type === 'docs') {
            $documents = DB::table('docs_documents')
                ->where('user_id', $userId)
                ->where('title', 'LIKE', "%{$query}%")
                ->select(
                    'id',
                    'title',
                    DB::raw('SUBSTRING(content, 1, 200) as snippet'),
                    'updated_at as date',
                    DB::raw("'' as from_email"),
                    DB::raw("'' as from_name"),
                    DB::raw("'docs' as type"),
                    DB::raw("CONCAT('/docs/', id) as url")
                )
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            $results = array_merge($results, $documents->toArray());
        }

        // Sort by date (most recent first)
        usort($results, function ($a, $b) {
            return strtotime($b->date) - strtotime($a->date);
        });

        return view('search.results', [
            'query' => $query,
            'results' => array_slice($results, 0, 20),
            'total' => count($results),
            'type' => $type,
        ]);
    }

    /**
     * API endpoint for search suggestions (autocomplete)
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $query = $request->input('q');
        $userId = auth()->id();

        $suggestions = [];

        // Email subjects
        $emailSubjects = DB::table('mail_messages')
            ->join('mail_mailboxes', 'mail_messages.mailbox_id', '=', 'mail_mailboxes.id')
            ->where('mail_mailboxes.user_id', $userId)
            ->where('subject', 'LIKE', "{$query}%")
            ->select('subject as text', DB::raw("'email' as type"))
            ->distinct()
            ->limit(5)
            ->get();

        $suggestions = array_merge($suggestions, $emailSubjects->toArray());

        // File names
        $fileNames = DB::table('drive_files')
            ->where('user_id', $userId)
            ->where('name', 'LIKE', "{$query}%")
            ->select('name as text', DB::raw("'file' as type"))
            ->distinct()
            ->limit(5)
            ->get();

        $suggestions = array_merge($suggestions, $fileNames->toArray());

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
