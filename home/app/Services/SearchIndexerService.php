<?php

namespace App\Services;

use App\Models\IndexedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchIndexerService
{
    /**
     * Sync all modules into the unified index.
     * Queries the shared MySQL database directly since all YGXONE modules
     * share the same `ygmarket_account` database.
     */
    public function syncAll(): array
    {
        return [
            'mail'  => $this->syncMail(),
            'drive' => $this->syncDrive(),
            'docs'  => $this->syncDocs(),
            'notes' => $this->syncNotes(),
        ];
    }

    /**
     * Sync Mail messages from the shared `mails` table.
     */
    public function syncMail(): int
    {
        try {
            $rows = DB::table('mails')
                ->select('id', 'user_id', 'subject', 'body', 'from', 'created_at')
                ->get();

            $count = 0;
            foreach ($rows as $item) {
                $body = strip_tags((string) $item->body);
                IndexedItem::updateOrCreate(
                    ['service' => 'mail', 'source_id' => (string) $item->id],
                    [
                        'user_id'  => $item->user_id ?? null,
                        'title'    => $item->subject ?? '(No Subject)',
                        'content'  => $body,
                        'snippet'  => mb_substr($body, 0, 200),
                        'url'      => null,
                        'metadata' => [
                            'from' => $item->from ?? 'unknown',
                            'date' => (string) $item->created_at,
                        ],
                    ]
                );
                $count++;
            }
            Log::info("Search indexer: synced {$count} mail items");
            return $count;
        } catch (\Exception $e) {
            Log::error("Failed to sync mail index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync Drive files from the shared `drive_files` table.
     */
    public function syncDrive(): int
    {
        try {
            $rows = DB::table('drive_files')
                ->select('id', 'user_id', 'name', 'description', 'mime_type', 'size', 'created_at')
                ->where('is_trashed', false)
                ->get();

            $count = 0;
            foreach ($rows as $item) {
                IndexedItem::updateOrCreate(
                    ['service' => 'drive', 'source_id' => (string) $item->id],
                    [
                        'user_id'  => $item->user_id,
                        'title'    => $item->name,
                        'content'  => $item->description ?? '',
                        'snippet'  => $item->description ? mb_substr($item->description, 0, 200) : $item->mime_type,
                        'url'      => null,
                        'metadata' => [
                            'mime_type' => $item->mime_type,
                            'size'      => $item->size,
                        ],
                    ]
                );
                $count++;
            }
            Log::info("Search indexer: synced {$count} drive items");
            return $count;
        } catch (\Exception $e) {
            Log::error("Failed to sync drive index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync DocX documents from the shared `documents` table.
     */
    public function syncDocs(): int
    {
        try {
            $rows = DB::table('documents')
                ->select('id', 'user_id', 'title', 'content', 'created_at')
                ->whereNull('deleted_at')
                ->get();

            $count = 0;
            foreach ($rows as $item) {
                $content = strip_tags((string) $item->content);
                IndexedItem::updateOrCreate(
                    ['service' => 'docs', 'source_id' => (string) $item->id],
                    [
                        'user_id'  => $item->user_id,
                        'title'    => $item->title ?? '(Untitled)',
                        'content'  => $content,
                        'snippet'  => mb_substr($content, 0, 200),
                        'url'      => null,
                    ]
                );
                $count++;
            }
            Log::info("Search indexer: synced {$count} doc items");
            return $count;
        } catch (\Exception $e) {
            Log::error("Failed to sync docs index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync Notes from the shared `notes` table.
     */
    public function syncNotes(): int
    {
        try {
            $rows = DB::table('notes')
                ->select('id', 'user_id', 'title', 'content', 'created_at')
                ->where('is_deleted', false)
                ->whereNull('deleted_at')
                ->get();

            $count = 0;
            foreach ($rows as $item) {
                $content = strip_tags((string) $item->content);
                IndexedItem::updateOrCreate(
                    ['service' => 'notes', 'source_id' => (string) $item->id],
                    [
                        'user_id'  => $item->user_id,
                        'title'    => $item->title ?? '(Untitled Note)',
                        'content'  => $content,
                        'snippet'  => mb_substr($content, 0, 200),
                        'url'      => null,
                    ]
                );
                $count++;
            }
            Log::info("Search indexer: synced {$count} note items");
            return $count;
        } catch (\Exception $e) {
            Log::error("Failed to sync notes index: " . $e->getMessage());
            return 0;
        }
    }
}
