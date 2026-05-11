<?php
/**
 * ContactStore — Contact form submission storage
 * Data stored in data/contact_submissions.json
 */
class ContactStore {
    private string $file;
    private array  $items;

    public function __construct(string $data_dir) {
        $this->file  = rtrim($data_dir, '/') . '/contact_submissions.json';
        $raw         = file_exists($this->file) ? json_decode(file_get_contents($this->file), true) : null;
        $this->items = is_array($raw) ? $raw : [];
    }

    public function add(string $name, string $email, string $subject, string $message): string {
        $id = uniqid('c_');
        $this->items[$id] = [
            'id'         => $id,
            'name'       => substr(trim($name), 0, 100),
            'email'      => strtolower(trim($email)),
            'subject'    => substr(trim($subject), 0, 200),
            'message'    => substr(trim($message), 0, 5000),
            'read'       => false,
            'created_at' => time(),
        ];
        file_put_contents($this->file, json_encode($this->items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $id;
    }

    public function markRead(string $id): void {
        if (isset($this->items[$id])) {
            $this->items[$id]['read'] = true;
            file_put_contents($this->file, json_encode($this->items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function delete(string $id): void {
        unset($this->items[$id]);
        file_put_contents($this->file, json_encode($this->items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function list(int $limit = 200): array {
        $all = array_values($this->items);
        usort($all, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        return array_slice($all, 0, $limit);
    }

    public function unreadCount(): int {
        return count(array_filter($this->items, fn($i) => !($i['read'] ?? false)));
    }
}
