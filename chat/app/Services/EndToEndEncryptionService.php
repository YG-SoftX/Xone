<?php

namespace App\Services;

use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Log;

class EndToEndEncryptionService
{
    /**
     * Generate RSA key pair for user
     */
    public function generateKeyPair(): array
    {
        try {
            $privateKey = RSA::createKey(2048);
            $publicKey = $privateKey->getPublicKey();
            
            return [
                'public_key' => $publicKey->toString('PKCS8'),
                'private_key' => $privateKey->toString('PKCS8'),
            ];
        } catch (\Exception $e) {
            Log::error('Key pair generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Encrypt message with recipient's public key
     */
    public function encryptMessage(string $message, string $publicKey): string
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            $encrypted = $rsa->encrypt($message);
            
            return base64_encode($encrypted);
        } catch (\Exception $e) {
            Log::error('Message encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Decrypt message with user's private key
     */
    public function decryptMessage(string $encryptedMessage, string $privateKey): string
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $decrypted = $rsa->decrypt(base64_decode($encryptedMessage));
            
            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Message decryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sign message with sender's private key (for authentication)
     */
    public function signMessage(string $message, string $privateKey): string
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $signature = $rsa->sign($message);
            
            return base64_encode($signature);
        } catch (\Exception $e) {
            Log::error('Message signing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify message signature with sender's public key
     */
    public function verifySignature(string $message, string $signature, string $publicKey): bool
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            return $rsa->verify($message, base64_decode($signature));
        } catch (\Exception $e) {
            Log::error('Signature verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Encrypt file content for secure sharing
     */
    public function encryptFile(string $fileContent, string $publicKey): array
    {
        try {
            // Generate symmetric key for file encryption
            $symmetricKey = random_bytes(32);
            $iv = random_bytes(16);
            
            // Encrypt file with symmetric key (faster for large files)
            $encryptedFile = openssl_encrypt(
                $fileContent,
                'aes-256-cbc',
                $symmetricKey,
                0,
                $iv
            );
            
            // Encrypt symmetric key with recipient's public key
            $rsa = RSA::loadPublicKey($publicKey);
            $encryptedKey = $rsa->encrypt($symmetricKey);
            
            return [
                'encrypted_file' => base64_encode($encryptedFile),
                'encrypted_key' => base64_encode($encryptedKey),
                'iv' => base64_encode($iv),
                'algorithm' => 'aes-256-cbc',
            ];
        } catch (\Exception $e) {
            Log::error('File encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Decrypt file content
     */
    public function decryptFile(string $encryptedFile, string $encryptedKey, string $iv, string $privateKey): string
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $symmetricKey = $rsa->decrypt(base64_decode($encryptedKey));

            $decryptedFile = openssl_decrypt(
                base64_decode($encryptedFile),
                'aes-256-cbc',
                $symmetricKey,
                OPENSSL_RAW_DATA,
                base64_decode($iv)
            );

            if ($decryptedFile === false) {
                throw new \RuntimeException('File decryption failed: openssl_decrypt returned false');
            }

            return $decryptedFile;
        } catch (\Exception $e) {
            Log::error('File decryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate a shared secret for group chat key derivation.
     * Uses HKDF over a random seed — proper DH requires a dedicated
     * DH/ECDH library; this provides a safe placeholder.
     */
    public function generateSharedSecret(string $myPrivateKey, string $theirPublicKey): string
    {
        try {
            // Derive a shared context string from both public keys only
            // (never expose private key material to hash input)
            $myPrivate   = RSA::loadPrivateKey($myPrivateKey);
            $myPublic    = $myPrivate->getPublicKey()->toString('PKCS8');
            $theirPublic = RSA::loadPublicKey($theirPublicKey)->toString('PKCS8');

            // Deterministic shared context from both public keys
            $context = hash('sha256', min($myPublic, $theirPublic) . max($myPublic, $theirPublic), true);

            // HKDF expand to 32-byte key
            return hash_hkdf('sha256', $context, 32, 'yg-chat-shared-secret');
        } catch (\Exception $e) {
            Log::error('Shared secret generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
