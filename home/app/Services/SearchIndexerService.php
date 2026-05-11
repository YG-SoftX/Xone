<?php

namespace App\Services;

use App\Models\IndexedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchIndexerService
{
    /**
     * Sync all modules into the unified index
     */
    public function syncAll(): array
    {
        return [
            'mail'     => $this->syncMail(),
            'drive'    => $this->syncDrive(),
            'docs'     => $this->syncDocs(),
            'contacts' => $this->syncContacts(),
        ];
    }

    /**
     * Sync Mail messages
     */
    public function syncMail(): int
    {
        $path = $this->getDbPath('YG_MAIL_DB_PATH', 'YG Mail');
        if (!$path || !file_exists($path)) return 0;

        try {
            $db = new \PDO("sqlite:{$path}");
            $stmt = $db->query("SELECT id, user_id, subject, body, \"from\", created_at FROM mails");
            
            $count = 0;
            while ($item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                IndexedItem::updateOrCreate(
                    ['service' => 'mail', 'source_id' => $item['id']],
                    [
                        'user_id' => $item['user_id'],
                        'title'   => $item['subject'],
                        'content' => strip_tags($item['body']),
                        'snippet' => substr(strip_tags($item['body']), 0, 200),
                        'url'     => "/mail/view/" . $item['id'],
                        'metadata' => [
                            'from' => $item['from'],
                            'date' => $item['created_at'],
                        ],
                    ]
                );
                $count++;
            }
            return $count;
        } catch (\Exception $e) {

            Log::error("Failed to sync mail index: " . $e->getMessage());
            return 0;
        }
    }


    /**
     * Sync Drive files
     */
    public function syncDrive(): int
    {
        $path = $this->getDbPath('YG_DRIVE_DB_PATH', 'YG Drive');
        if (!$path || !file_exists($path)) return 0;

        try {
            $db = new \PDO("sqlite:{$path}");
            $stmt = $db->query("SELECT id, user_id, name, description, mime_type, size_bytes FROM drive_files");
            
            $count = 0;
            while ($item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                IndexedItem::updateOrCreate(
                    ['service' => 'drive', 'source_id' => $item['id']],
                    [
                        'user_id' => $item['user_id'],
                        'title'   => $item['name'],
                        'content' => $item['description'],
                        'snippet' => $item['description'] ?: $item['mime_type'],
                        'url'     => "/drive/files/" . $item['id'],
                        'metadata' => [
                            'mime_type' => $item['mime_type'],
                            'size'      => $item['size_bytes'],
                        ],
                    ]
                );
                $count++;
            }
            return $count;
        } catch (\Exception $e) {

            Log::error("Failed to sync drive index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync Docs
     */
    public function syncDocs(): int
    {
        $path = $this->getDbPath('YG_DOCX_DB_PATH', 'YG DocX');
        if (!$path || !file_exists($path)) return 0;

        try {
            $db = new \PDO("sqlite:{$path}");
            $stmt = $db->query("SELECT id, user_id, title, content FROM documents");
            
            $count = 0;
            while ($item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                IndexedItem::updateOrCreate(
                    ['service' => 'docs', 'source_id' => $item['id']],
                    [
                        'user_id' => $item['user_id'],
                        'title'   => $item['title'],
                        'content' => strip_tags($item['content']),
                        'snippet' => substr(strip_tags($item['content']), 0, 200),
                        'url'     => "/docs/edit/" . $item['id'],
                    ]
                );
                $count++;
            }
            return $count;
        } catch (\Exception $e) {

            Log::error("Failed to sync docs index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync Contacts
     */
    public function syncContacts(): int
    {
        $path = $this->getDbPath('YG_CONTACTS_DB_PATH', 'YG Contacts');
        if (!$path || !file_exists($path)) return 0;

        try {
            $db = new \PDO("sqlite:{$path}");
            $stmt = $db->query("SELECT id, user_id, first_name, last_name, email, phone, company FROM contacts");
            
            $count = 0;
            while ($item = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $name = "{$item['first_name']} {$item['last_name']}";
                IndexedItem::updateOrCreate(
                    ['service' => 'contacts', 'source_id' => $item['id']],
                    [
                        'user_id' => $item['user_id'],
                        'title'   => $name,
                        'content' => "{$name} {$item['email']} {$item['phone']} {$item['company']}",
                        'snippet' => "{$item['email']} | {$item['company']}",
                        'url'     => "/contacts/view/" . $item['id'],
                        'metadata' => [
                            'email' => $item['email'],
                            'phone' => $item['phone'],
                        ],
                    ]
                );
                $count++;
            }
            return $count;
        } catch (\Exception $e) {

            Log::error("Failed to sync contacts index: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Resolve DB path from env or relative to base
     */
    private function getDbPath(string $envKey, string $dirName): ?string
    {
        $path = config('database.' . strtolower($envKey), env($envKey));

        if (!$path) {
            $path = "../{$dirName}/database/database.sqlite";
        }

        // Resolve to absolute path and verify it stays within expected directory
        $absolutePath = realpath(base_path($path));

        if (!$absolutePath) {
            $absolutePath = realpath(base_path(str_replace('../', '', $path)));
        }

        // Security: ensure path ends with .sqlite and doesn't escape base
        if (!$absolutePath
            || !str_ends_with($absolutePath, '.sqlite')
            || !str_contains($absolutePath, 'database')) {
            return null;
        }

        return file_exists($absolutePath) ? $absolutePath : null;
    }
}

