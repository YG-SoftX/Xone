<?php
/**
 * ModelPresets — Transformer size configurations for Yuga
 *
 * Pick a preset when creating a model. Larger = better quality,
 * more RAM, longer training time.
 *
 * nano / small  → shared cPanel hosting (128–512 MB RAM)
 * medium / large → VPS (1–4 GB RAM)
 */
class ModelPresets {

    public static function all(): array {
        return [
            'nano' => [
                'name'            => 'Nano',
                'description'     => 'Default. Ultra-fast, minimal RAM. Best for shared hosting.',
                'emoji'           => '⚡',
                'V'               => 2000,
                'D'               => 32,
                'H'               => 2,
                'L'               => 2,
                'CTX'             => 64,
                'embed_dim'       => 32,
                'hidden_dim'      => 128,
                'context_len'     => 6,
                'approx_params'   => '65K',
                'ram_mb'          => 32,
                'train_steps_rec' => 10000,
                'hosting'         => 'shared',
            ],
            'small' => [
                'name'            => 'Small',
                'description'     => 'Better quality. Still fine on most shared hosting plans.',
                'emoji'           => '🔹',
                'V'               => 8000,
                'D'               => 128,
                'H'               => 4,
                'L'               => 4,
                'CTX'             => 128,
                'embed_dim'       => 128,
                'hidden_dim'      => 512,
                'context_len'     => 12,
                'approx_params'   => '2M',
                'ram_mb'          => 128,
                'train_steps_rec' => 30000,
                'hosting'         => 'shared',
            ],
            'medium' => [
                'name'            => 'Medium',
                'description'     => 'Good quality. Requires VPS or high-memory cPanel (512 MB+).',
                'emoji'           => '🔷',
                'V'               => 16000,
                'D'               => 256,
                'H'               => 8,
                'L'               => 6,
                'CTX'             => 256,
                'embed_dim'       => 256,
                'hidden_dim'      => 1024,
                'context_len'     => 16,
                'approx_params'   => '20M',
                'ram_mb'          => 512,
                'train_steps_rec' => 100000,
                'hosting'         => 'vps',
            ],
            'large' => [
                'name'            => 'Large',
                'description'     => 'High quality generation. Requires VPS with 2 GB+ RAM.',
                'emoji'           => '💎',
                'V'               => 32000,
                'D'               => 512,
                'H'               => 8,
                'L'               => 8,
                'CTX'             => 512,
                'embed_dim'       => 512,
                'hidden_dim'      => 2048,
                'context_len'     => 24,
                'approx_params'   => '130M',
                'ram_mb'          => 2048,
                'train_steps_rec' => 500000,
                'hosting'         => 'vps',
            ],
        ];
    }

    public static function get(string $size): array {
        return self::all()[$size] ?? self::all()['nano'];
    }

    /** Recommend a preset based on the server's PHP memory_limit */
    public static function recommend(): string {
        $mb = self::parseMemory(ini_get('memory_limit'));
        if ($mb <= 0 || $mb >= 512) return 'small';
        if ($mb >= 256)             return 'small';
        return 'nano';
    }

    public static function parseMemory(string $val): int {
        $val  = strtolower(trim($val));
        if ($val === '-1') return 2048;
        $num  = (int) $val;
        $unit = substr($val, -1);
        return match ($unit) {
            'g'     => $num * 1024,
            'm'     => $num,
            'k'     => (int)($num / 1024),
            default => (int)($num / 1_048_576),
        };
    }
}
