<?php
/**
 * Tickets — Support ticket system for Yuga
 * Data stored in data/tickets.json and data/ticket_messages/[id].json
 */
class Tickets {
    private string $file;
    private string $msg_dir;
    private array  $tickets;

    public const STATUSES   = ['open', 'in_progress', 'resolved', 'closed'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function __construct(string $data_dir) {
        $this->file    = rtrim($data_dir, '/') . '/tickets.json';
        $this->msg_dir = rtrim($data_dir, '/') . '/ticket_messages';
        if (!is_dir($this->msg_dir)) mkdir($this->msg_dir, 0755, true);
        $raw           = file_exists($this->file) ? json_decode(file_get_contents($this->file), true) : null;
        $this->tickets = is_array($raw) ? $raw : [];
    }

    private function save(): void {
        file_put_contents($this->file, json_encode($this->tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function create(string $name, string $email, string $subject, string $message, string $priority = 'normal'): string {
        $id = 'TKT-' . strtoupper(substr(uniqid(), -6));
        while (isset($this->tickets[$id])) $id = 'TKT-' . strtoupper(substr(uniqid(), -6));
        $this->tickets[$id] = [
            'id'         => $id,
            'name'       => substr(trim($name), 0, 100),
            'email'      => strtolower(trim($email)),
            'subject'    => substr(trim($subject), 0, 200),
            'priority'   => in_array($priority, self::PRIORITIES) ? $priority : 'normal',
            'status'     => 'open',
            'created_at' => time(),
            'updated_at' => time(),
            'replies'    => 0,
        ];
        $this->save();
        // Save first message
        $this->addMessage($id, $name, $email, 'user', $message);
        return $id;
    }

    public function addMessage(string $id, string $author_name, string $author_email, string $type, string $message): void {
        if (!isset($this->tickets[$id])) return;
        $file = $this->msg_dir . '/' . $id . '.json';
        $msgs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        if (!is_array($msgs)) $msgs = [];
        $msgs[] = [
            'id'          => uniqid('msg_'),
            'author_name' => substr(trim($author_name), 0, 100),
            'author_email'=> strtolower(trim($author_email)),
            'type'        => $type === 'admin' ? 'admin' : 'user',
            'message'     => trim($message),
            'created_at'  => time(),
        ];
        file_put_contents($file, json_encode($msgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->tickets[$id]['updated_at'] = time();
        $this->tickets[$id]['replies']    = max(0, count($msgs) - 1);
        $this->save();
    }

    public function get(string $id): ?array {
        return $this->tickets[$id] ?? null;
    }

    public function getMessages(string $id): array {
        $file = $this->msg_dir . '/' . $id . '.json';
        if (!file_exists($file)) return [];
        $msgs = json_decode(file_get_contents($file), true);
        return is_array($msgs) ? $msgs : [];
    }

    public function updateStatus(string $id, string $status): void {
        if (!isset($this->tickets[$id])) return;
        if (!in_array($status, self::STATUSES)) return;
        $this->tickets[$id]['status']     = $status;
        $this->tickets[$id]['updated_at'] = time();
        $this->save();
    }

    public function list(string $status = '', int $limit = 200): array {
        $all = array_values($this->tickets);
        if ($status) $all = array_values(array_filter($all, fn($t) => $t['status'] === $status));
        usort($all, fn($a, $b) => ($b['updated_at'] ?? 0) <=> ($a['updated_at'] ?? 0));
        return array_slice($all, 0, $limit);
    }

    public function listByEmail(string $email): array {
        $email = strtolower(trim($email));
        $all   = array_values(array_filter($this->tickets, fn($t) => strtolower($t['email'] ?? '') === $email));
        usort($all, fn($a, $b) => ($b['updated_at'] ?? 0) <=> ($a['updated_at'] ?? 0));
        return $all;
    }

    public function countByStatus(): array {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($this->tickets as $t) {
            $s = $t['status'] ?? 'open';
            if (isset($counts[$s])) $counts[$s]++;
        }
        return $counts;
    }

    public function delete(string $id): void {
        unset($this->tickets[$id]);
        $this->save();
        @unlink($this->msg_dir . '/' . $id . '.json');
    }
}
