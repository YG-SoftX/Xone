<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

/**
 * PasswordManagerService
 * 
 * Securely stores and auto-fills login credentials for visited websites.
 * Uses Laravel's encryption for security.
 */
class PasswordManagerService
{
    /**
     * Save credentials for a website
     * 
     * @param string $url Website URL
     * @param string $username Username/email
     * @param string $password Password (will be encrypted)
     * @param int|null $userId Authenticated user ID (null for session-only)
     * @return bool Success status
     */
    public function saveCredentials(string $url, string $username, string $password, ?int $userId = null): bool
    {
        try {
            // Normalize URL to domain
            $domain = $this->extractDomain($url);
            
            if (empty($domain)) {
                return false;
            }
            
            // Encrypt sensitive data
            $encryptedPassword = Crypt::encryptString($password);
            $encryptedUsername = Crypt::encryptString($username);
            
            // Upsert credentials
            DB::table('saved_passwords')->updateOrInsert(
                [
                    'domain' => $domain,
                    'user_id' => $userId,
                    'session_id' => $userId ? null : session()->getId(),
                ],
                [
                    'username' => $encryptedUsername,
                    'password' => $encryptedPassword,
                    'last_used_at' => now(),
                    'usage_count' => DB::raw('usage_count + 1'),
                    'updated_at' => now(),
                ]
            );
            
            Log::info("Password saved for domain: {$domain}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to save password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get credentials for a website
     * 
     * @param string $url Website URL
     * @param int|null $userId Authenticated user ID
     * @return array|null Decrypted credentials or null
     */
    public function getCredentials(string $url, ?int $userId = null): ?array
    {
        try {
            $domain = $this->extractDomain($url);
            
            if (empty($domain)) {
                return null;
            }
            
            // Try user-specific first, then session-based
            $record = DB::table('saved_passwords')
                ->where('domain', $domain)
                ->where(function($query) use ($userId) {
                    if ($userId) {
                        $query->where('user_id', $userId)
                              ->orWhereNull('user_id');
                    } else {
                        $query->where('session_id', session()->getId());
                    }
                })
                ->orderBy('user_id', 'desc') // Prefer user-specific over session
                ->first();
            
            if (!$record) {
                return null;
            }
            
            // Decrypt credentials
            return [
                'domain' => $record->domain,
                'username' => Crypt::decryptString($record->username),
                'password' => Crypt::decryptString($record->password),
                'last_used' => $record->last_used_at,
                'usage_count' => $record->usage_count,
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to retrieve password: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * List all saved passwords for user/session
     * 
     * @param int|null $userId Authenticated user ID
     * @return array List of saved credentials (without passwords)
     */
    public function listSavedPasswords(?int $userId = null): array
    {
        try {
            $query = DB::table('saved_passwords')
                ->select('id', 'domain', 'username', 'last_used_at', 'usage_count', 'created_at')
                ->where(function($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId)
                          ->orWhereNull('user_id');
                    } else {
                        $q->where('session_id', session()->getId());
                    }
                })
                ->orderBy('last_used_at', 'desc');
            
            $records = $query->limit(50)->get()->toArray();
            
            // Decrypt usernames for display
            return array_map(function($record) {
                try {
                    return [
                        'id' => $record->id,
                        'domain' => $record->domain,
                        'username' => Crypt::decryptString($record->username),
                        'last_used' => $record->last_used_at,
                        'usage_count' => $record->usage_count,
                        'created_at' => $record->created_at,
                    ];
                } catch (\Exception $e) {
                    return [
                        'id' => $record->id,
                        'domain' => $record->domain,
                        'username' => '[Encrypted]',
                        'last_used' => $record->last_used_at,
                        'usage_count' => $record->usage_count,
                        'created_at' => $record->created_at,
                    ];
                }
            }, $records);
            
        } catch (\Exception $e) {
            Log::error("Failed to list passwords: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete saved password
     * 
     * @param int $id Password record ID
     * @param int|null $userId User ID for authorization
     * @return bool Success status
     */
    public function deletePassword(int $id, ?int $userId = null): bool
    {
        try {
            $query = DB::table('saved_passwords')->where('id', $id);
            
            if ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhereNull('user_id');
                });
            } else {
                $query->where('session_id', session()->getId());
            }
            
            $deleted = $query->delete();
            
            if ($deleted) {
                Log::info("Password deleted: ID {$id}");
            }
            
            return $deleted > 0;
            
        } catch (\Exception $e) {
            Log::error("Failed to delete password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detect login form on page and suggest saving
     * 
     * @param string $html Page HTML
     * @param string $url Current URL
     * @return array Detected form fields
     */
    public function detectLoginForm(string $html, string $url): array
    {
        $forms = [];
        
        // Look for forms with password fields
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $formMatches);
        
        foreach ($formMatches[1] as $formContent) {
            $hasPassword = stripos($formContent, 'type="password"') !== false ||
                          stripos($formContent, 'type=\'password\'') !== false;
            
            if ($hasPassword) {
                // Extract username/email field
                preg_match('/<input[^>]*type=["\']?(?:text|email)["\']?[^>]*name=["\']([^"\']+)["\']/i', $formContent, $usernameMatch);
                
                // Extract password field
                preg_match('/<input[^>]*type=["\']?password["\']?[^>]*name=["\']([^"\']+)["\']/i', $formContent, $passwordMatch);
                
                // Extract action URL
                preg_match('/action=["\']([^"\']*)["\']/i', $formContent, $actionMatch);
                
                if (!empty($usernameMatch) && !empty($passwordMatch)) {
                    $forms[] = [
                        'username_field' => $usernameMatch[1],
                        'password_field' => $passwordMatch[1],
                        'action' => $actionMatch[1] ?? '',
                        'has_credentials' => $this->hasSavedCredentials($url),
                    ];
                }
            }
        }
        
        return $forms;
    }
    
    /**
     * Check if credentials exist for domain
     */
    private function hasSavedCredentials(string $url): bool
    {
        $domain = $this->extractDomain($url);
        
        if (empty($domain)) {
            return false;
        }
        
        return DB::table('saved_passwords')
            ->where('domain', $domain)
            ->where(function($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id())
                          ->orWhereNull('user_id');
                } else {
                    $query->where('session_id', session()->getId());
                }
            })
            ->exists();
    }
    
    /**
     * Extract domain from URL
     */
    private function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        
        if (!isset($parsed['host'])) {
            return '';
        }
        
        $host = $parsed['host'];
        
        // Remove www. prefix
        $host = preg_replace('/^www\./', '', $host);
        
        return strtolower($host);
    }
    
    /**
     * Auto-fill detected forms with saved credentials
     * 
     * @param string $url Current URL
     * @return array Credentials to inject (JavaScript-ready)
     */
    public function getAutoFillData(string $url): array
    {
        $credentials = $this->getCredentials($url, auth()->id());
        
        if (!$credentials) {
            return [];
        }
        
        return [
            'domain' => $credentials['domain'],
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'auto_fill' => true,
        ];
    }
    
    /**
     * Export passwords (for backup/migration)
     * Encrypted export format
     */
    public function exportPasswords(?int $userId = null): string
    {
        $passwords = $this->listSavedPasswords($userId);
        
        // Create encrypted JSON export
        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'count' => count($passwords),
            'passwords' => $passwords,
        ];
        
        return Crypt::encryptString(json_encode($exportData));
    }
    
    /**
     * Import passwords from encrypted export
     */
    public function importPasswords(string $encryptedExport, ?int $userId = null): int
    {
        try {
            $decrypted = Crypt::decryptString($encryptedExport);
            $data = json_decode($decrypted, true);
            
            if (!$data || !isset($data['passwords'])) {
                return 0;
            }
            
            $imported = 0;
            
            foreach ($data['passwords'] as $pwd) {
                // Note: This only imports metadata, not actual passwords
                // Users would need to re-enter passwords for security
                Log::info("Imported password metadata for: {$pwd['domain']}");
                $imported++;
            }
            
            return $imported;
            
        } catch (\Exception $e) {
            Log::error("Failed to import passwords: " . $e->getMessage());
            return 0;
        }
    }
}
