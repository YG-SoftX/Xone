<?php
/**
 * Blog — Simple blog post manager for Yuga
 * Data stored in data/blog_posts.json
 */
class Blog {
    private string $file;
    private array  $posts;

    public function __construct(string $data_dir) {
        $this->file  = rtrim($data_dir, '/') . '/blog_posts.json';
        $raw         = file_exists($this->file) ? json_decode(file_get_contents($this->file), true) : null;
        $this->posts = is_array($raw) ? $raw : [];
    }

    public function save(): void {
        file_put_contents($this->file, json_encode($this->posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function create(array $data): string {
        $id = uniqid('post_', true);
        $slug = $this->uniqueSlug($data['slug'] ?? $this->slugify($data['title'] ?? $id));
        $this->posts[$id] = array_merge([
            'id'         => $id,
            'title'      => '',
            'slug'       => $slug,
            'excerpt'    => '',
            'body'       => '',
            'author'     => 'Admin',
            'tags'       => [],
            'status'     => 'draft',
            'created_at' => time(),
            'updated_at' => time(),
        ], $data, ['id' => $id, 'slug' => $slug]);
        $this->save();
        return $id;
    }

    public function update(string $id, array $data): bool {
        if (!isset($this->posts[$id])) return false;
        $data['updated_at'] = time();
        if (isset($data['title']) && !isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($this->slugify($data['title']), $id);
        } elseif (isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $id);
        }
        $this->posts[$id] = array_merge($this->posts[$id], $data, ['id' => $id]);
        $this->save();
        return true;
    }

    public function delete(string $id): void {
        unset($this->posts[$id]);
        $this->save();
    }

    public function get(string $id): ?array {
        return $this->posts[$id] ?? null;
    }

    public function getBySlug(string $slug): ?array {
        foreach ($this->posts as $p) {
            if (($p['slug'] ?? '') === $slug) return $p;
        }
        return null;
    }

    public function list(bool $published_only = false, int $limit = 100, int $offset = 0): array {
        $all = array_values($this->posts);
        if ($published_only) {
            $all = array_values(array_filter($all, fn($p) => ($p['status'] ?? '') === 'published'));
        }
        usort($all, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        return array_slice($all, $offset, $limit);
    }

    public function count(bool $published_only = false): int {
        if (!$published_only) return count($this->posts);
        return count(array_filter($this->posts, fn($p) => ($p['status'] ?? '') === 'published'));
    }

    private function slugify(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'post';
    }

    private function uniqueSlug(string $slug, string $exclude_id = ''): string {
        $base   = $slug;
        $n      = 1;
        while (true) {
            $exists = false;
            foreach ($this->posts as $id => $p) {
                if ($id === $exclude_id) continue;
                if (($p['slug'] ?? '') === $slug) { $exists = true; break; }
            }
            if (!$exists) return $slug;
            $slug = $base . '-' . (++$n);
        }
    }
}
