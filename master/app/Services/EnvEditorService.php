<?php

namespace App\Services;

class EnvEditorService
{
    // Keys whose values are always masked in the UI
    private const MASKED_KEYS = [
        'APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'REDIS_PASSWORD',
        'AWS_SECRET_ACCESS_KEY', 'STRIPE_SECRET', 'PUSHER_APP_SECRET',
        'MIX_PUSHER_APP_KEY', 'SANCTUM_SECRET', 'WEBHOOK_SECRET',
        'STRIPE_SECRET_KEY', 'PAYPAL_CLIENT_SECRET', 'RAZORPAY_KEY_SECRET',
        'RAZORPAY_WEBHOOK_SECRET', 'KHALTI_SECRET_KEY', 'FONEPAY_SECRET_KEY',
        'CONNECTIPS_PASSWORD', 'TRUELAYER_CLIENT_SECRET', 'YGPAY_API_KEY',
    ];

    public function __construct(private readonly AppRegistryService $registry) {}

    /**
     * Parse an app's .env into an ordered array of entries.
     *
     * Each entry is one of:
     *   ['type' => 'comment', 'value' => '# some comment']
     *   ['type' => 'blank']
     *   ['type' => 'key', 'key' => 'APP_NAME', 'value' => 'YG Account', 'masked' => false]
     */
    public function parse(string $id): array
    {
        $path    = $this->registry->path($id);
        $envFile = $path . '/.env';

        if (! file_exists($envFile)) {
            return [];
        }

        $entries = [];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
            if (trim($line) === '') {
                $entries[] = ['type' => 'blank'];
                continue;
            }

            if (str_starts_with(ltrim($line), '#')) {
                $entries[] = ['type' => 'comment', 'value' => $line];
                continue;
            }

            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $key     = trim($k);
            $value   = trim($v, '"\'');

            $entries[] = [
                'type'   => 'key',
                'key'    => $key,
                'value'  => $value,
                'masked' => in_array($key, self::MASKED_KEYS, true),
            ];
        }

        return $entries;
    }

    /** Read a flat key→value map (masked values excluded) */
    public function read(string $id): array
    {
        return collect($this->parse($id))
            ->filter(fn ($e) => $e['type'] === 'key' && ! $e['masked'])
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Update specific keys in the .env file, preserving all other lines exactly.
     *
     * @param array<string, string> $updates key→value pairs to update
     */
    public function update(string $id, array $updates): bool
    {
        $path    = $this->registry->path($id);
        $envFile = $path . '/.env';

        if (! file_exists($envFile)) {
            return false;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        $out   = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                $out[] = $line;
                continue;
            }

            [$k] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($k);

            if (array_key_exists($key, $updates)) {
                $value   = $updates[$key];
                $needsQuotes = preg_match('/\s/', $value) || str_contains($value, '#');
                $out[] = $key . '=' . ($needsQuotes ? '"' . $value . '"' : $value);
                unset($updates[$key]); // mark as handled
            } else {
                $out[] = $line;
            }
        }

        // Append any new keys that didn't exist
        foreach ($updates as $key => $value) {
            $needsQuotes = preg_match('/\s/', $value) || str_contains($value, '#');
            $out[] = $key . '=' . ($needsQuotes ? '"' . $value . '"' : $value);
        }

        return file_put_contents($envFile, implode("\n", $out) . "\n") !== false;
    }

    /** Read a single key directly from the .env file */
    public function get(string $id, string $key): ?string
    {
        foreach ($this->parse($id) as $entry) {
            if ($entry['type'] === 'key' && $entry['key'] === $key) {
                return $entry['value'];
            }
        }
        return null;
    }

    public function isMasked(string $key): bool
    {
        return in_array($key, self::MASKED_KEYS, true);
    }
}
