<?php
/**
 * ConfigWriter — Safely update config.php from the admin UI
 * Reads the current config, merges changes, rewrites the file.
 */
class ConfigWriter {

    private string $path;

    public function __construct(string $config_path) {
        $this->path = $config_path;
    }

    public function get(): array {
        if (!file_exists($this->path)) return [];
        // Isolate require in a closure to avoid variable pollution
        return (static function($p){ return require $p; })($this->path);
    }

    /**
     * Merge $changes into the existing config and rewrite config.php.
     * Supports dot-notation for nested keys: 'smtp.host' => 'mail.example.com'
     */
    public function update(array $changes): void {
        $cfg = $this->get();

        foreach ($changes as $key => $value) {
            if (str_contains($key, '.')) {
                $parts = explode('.', $key, 2);
                $cfg[$parts[0]][$parts[1]] = $value;
            } else {
                $cfg[$key] = $value;
            }
        }

        $this->write($cfg);
    }

    /** Replace an entire top-level key with an array */
    public function setSection(string $key, array $value): void {
        $cfg = $this->get();
        $cfg[$key] = $value;
        $this->write($cfg);
    }

    private function write(array $cfg): void {
        $content = "<?php\n// Yuga configuration — auto-updated by admin panel\nreturn "
                 . var_export($cfg, true) . ";\n";
        file_put_contents($this->path, $content);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->path, true);
        }
    }
}
