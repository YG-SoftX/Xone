<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centralised HTTP client for the YG Account developer API.
 * Automatically injects the authenticated user's Bearer token.
 */
class ApiClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.yg_account.api_base', ''), '/');
        $this->token   = auth()->user()?->api_token ?? '';
    }

    /** GET request — returns decoded JSON array or [] on failure. */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->send('get', $endpoint, ['query' => $params]);
    }

    /** POST request — returns decoded JSON array or [] on failure. */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->send('post', $endpoint, ['json' => $data]);
    }

    /** PUT request — returns decoded JSON array or [] on failure. */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->send('put', $endpoint, ['json' => $data]);
    }

    /** DELETE request — returns decoded JSON array or [] on failure. */
    public function delete(string $endpoint): array
    {
        return $this->send('delete', $endpoint);
    }

    /** Returns the raw Response for cases that need status code inspection. */
    public function raw(string $method, string $endpoint, array $options = []): Response
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        return Http::withToken($this->token)
            ->timeout(10)
            ->$method($url, $options['json'] ?? $options['query'] ?? []);
    }

    // -------------------------------------------------------------------------

    private function send(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->raw($method, $endpoint, $options);

            if ($response->ok()) {
                return $response->json() ?? [];
            }

            Log::warning("YG API {$method} {$endpoint} failed", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['error' => $response->json('message') ?? 'Request failed', 'status' => $response->status()];
        } catch (\Throwable $e) {
            Log::error("YG API {$method} {$endpoint} exception: " . $e->getMessage());
            return ['error' => 'Unable to reach YG Account API.'];
        }
    }
}
