<?php

namespace App\Services;

use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Log;

class MailEncryptionService
{
    /**
     * Generate RSA key pair for email encryption
     */
    public function generateKeyPair(): array
    {
        try {
            $privateKey = RSA::createKey(4096); // Use 4096-bit for stronger email encryption
            $publicKey = $privateKey->getPublicKey();
            
            return [
                'public_key' => $publicKey->toString('PKCS8'),
                'private_key' => $privateKey->toString('PKCS8'),
            ];
        } catch (\Exception $e) {
            Log::error('Mail encryption key generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Encrypt email content with recipient's public key
     */
    public function encryptEmail(string $content, string $publicKey): string
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            $encrypted = $rsa->encrypt($content);
            
            return base64_encode($encrypted);
        } catch (\Exception $e) {
            Log::error('Email encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Decrypt email content with user's private key
     */
    public function decryptEmail(string $encryptedContent, string $privateKey): string
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $decrypted = $rsa->decrypt(base64_decode($encryptedContent));
            
            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Email decryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Encrypt email with hybrid encryption (for large emails)
     * Uses RSA for key exchange + AES-256 for content
     */
    public function encryptEmailHybrid(string $content, string $publicKey): array
    {
        try {
            // Generate symmetric key for content encryption
            $symmetricKey = random_bytes(32); // 256-bit key
            $iv = random_bytes(16); // 128-bit IV
            
            // Encrypt email content with AES-256-CBC (fast for large content)
            $encryptedContent = openssl_encrypt(
                $content,
                'aes-256-cbc',
                $symmetricKey,
                0,
                $iv
            );
            
            // Encrypt symmetric key with recipient's RSA public key
            $rsa = RSA::loadPublicKey($publicKey);
            $encryptedKey = $rsa->encrypt($symmetricKey);
            
            return [
                'encrypted_content' => base64_encode($encryptedContent),
                'encrypted_key' => base64_encode($encryptedKey),
                'iv' => base64_encode($iv),
                'algorithm' => 'aes-256-cbc',
                'key_size' => 256,
            ];
        } catch (\Exception $e) {
            Log::error('Hybrid email encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Decrypt email with hybrid decryption
     */
    public function decryptEmailHybrid(
        string $encryptedContent,
        string $encryptedKey,
        string $iv,
        string $privateKey
    ): string {
        try {
            // Decrypt symmetric key with private key
            $rsa = RSA::loadPrivateKey($privateKey);
            $symmetricKey = $rsa->decrypt(base64_decode($encryptedKey));
            
            // Decrypt email content with symmetric key
            $decryptedContent = openssl_decrypt(
                base64_decode($encryptedContent),
                'aes-256-cbc',
                $symmetricKey,
                0,
                base64_decode($iv)
            );
            
            return $decryptedContent;
        } catch (\Exception $e) {
            Log::error('Hybrid email decryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sign email with sender's private key (for authentication)
     */
    public function signEmail(string $content, string $privateKey): string
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $signature = $rsa->sign($content);
            
            return base64_encode($signature);
        } catch (\Exception $e) {
            Log::error('Email signing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify email signature with sender's public key
     */
    public function verifyEmailSignature(string $content, string $signature, string $publicKey): bool
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            return $rsa->verify($content, base64_decode($signature));
        } catch (\Exception $e) {
            Log::error('Email signature verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Encrypt email attachments
     */
    public function encryptAttachment(string $fileContent, string $publicKey): array
    {
        return $this->encryptEmailHybrid($fileContent, $publicKey);
    }

    /**
     * Decrypt email attachments
     */
    public function decryptAttachment(
        string $encryptedContent,
        string $encryptedKey,
        string $iv,
        string $privateKey
    ): string {
        return $this->decryptEmailHybrid($encryptedContent, $encryptedKey, $iv, $privateKey);
    }

    /**
     * Check if content is encrypted
     */
    public function isEncrypted(string $content): bool
    {
        // Check for base64 encoded encrypted data patterns
        return preg_match('/^[A-Za-z0-9+\/]+=*$/', $content) && strlen($content) > 100;
    }

    /**
     * Generate secure email token for one-time access
     */
    public function generateSecureToken(string $emailId, string $userId): string
    {
        $data = "{$emailId}:{$userId}:" . time();
        $hash = hash_hmac('sha256', $data, config('app.key'));
        
        return base64_encode("{$emailId}:{$hash}");
    }

    /**
     * Verify secure email token
     */
    public function verifySecureToken(string $token, string $expectedEmailId, string $expectedUserId): bool
    {
        try {
            $decoded = base64_decode($token);
            [$emailId, $hash] = explode(':', $decoded, 2);
            
            $expectedHash = hash_hmac('sha256', "{$emailId}:{$expectedUserId}:" . time(), config('app.key'));
            
            return hash_equals($hash, $expectedHash) && $emailId === $expectedEmailId;
        } catch (\Exception $e) {
            return false;
        }
    }
}
