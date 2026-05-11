<?php

namespace App\Services;

use App\Models\Project;
use App\Models\OAuthApplication;
use Illuminate\Support\Str;

class OAuthService
{
    /**
     * Create new OAuth application
     */
    public function createApplication(Project $project, array $data): OAuthApplication
    {
        return $project->oauthApplications()->create([
            'name' => $data['name'],
            'client_id' => $this->generateClientId(),
            'client_secret' => $this->generateClientSecret(),
            'redirect_uris' => $data['redirect_uris'] ?? [],
            'scopes' => $data['scopes'] ?? ['read', 'write'],
            'is_confidential' => $data['is_confidential'] ?? true,
        ]);
    }

    /**
     * Update OAuth application
     */
    public function updateApplication(OAuthApplication $app, array $data): OAuthApplication
    {
        $app->update([
            'name' => $data['name'] ?? $app->name,
            'redirect_uris' => $data['redirect_uris'] ?? $app->redirect_uris,
            'scopes' => $data['scopes'] ?? $app->scopes,
        ]);

        return $app;
    }

    /**
     * Regenerate client secret
     */
    public function regenerateSecret(OAuthApplication $app): string
    {
        $newSecret = $this->generateClientSecret();
        $app->update(['client_secret' => $newSecret]);

        return $newSecret;
    }

    /**
     * Delete OAuth application
     */
    public function deleteApplication(OAuthApplication $app): bool
    {
        return $app->delete();
    }

    /**
     * Validate redirect URI
     */
    public function isValidRedirectUri(OAuthApplication $app, string $redirectUri): bool
    {
        if (empty($app->redirect_uris)) {
            return false;
        }

        return in_array($redirectUri, $app->redirect_uris);
    }

    /**
     * Generate authorization code (simplified)
     */
    public function generateAuthorizationCode(OAuthApplication $app, array $params): string
    {
        // In production, this would:
        // 1. Validate scopes
        // 2. Check user consent
        // 3. Store code in database with expiration
        // 4. Return secure random code
        
        return bin2hex(random_bytes(32));
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code, OAuthApplication $app): ?array
    {
        // In production, this would:
        // 1. Validate code exists and not expired
        // 2. Verify client credentials
        // 3. Generate access token and refresh token
        // 4. Store tokens in database
        
        return [
            'access_token' => bin2hex(random_bytes(32)),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => bin2hex(random_bytes(32)),
            'scope' => implode(' ', $app->scopes ?? []),
        ];
    }

    /**
     * Validate access token
     */
    public function validateAccessToken(string $token): ?array
    {
        // In production, query database or cache
        // For now, return mock data
        
        return [
            'valid' => true,
            'project_id' => 1,
            'scopes' => ['read', 'write'],
            'expires_at' => now()->addHour(),
        ];
    }

    /**
     * Generate client ID
     */
    protected function generateClientId(): string
    {
        return Str::random(20);
    }

    /**
     * Generate client secret
     */
    protected function generateClientSecret(): string
    {
        return Str::random(40);
    }
}
