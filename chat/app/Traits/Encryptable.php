<?php

namespace App\Traits;

use App\Services\EncryptionService;

trait Encryptable
{
    /**
     * Fields that should be encrypted
     */
    protected array $encryptedFields = [];

    /**
     * Boot the encryptable trait
     */
    public static function bootEncryptable(): void
    {
        static::creating(function ($model) {
            $model->encryptAttributes();
        });

        static::updating(function ($model) {
            $model->encryptAttributes();
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    /**
     * Encrypt specified attributes
     */
    protected function encryptAttributes(): void
    {
        if (empty($this->encryptedFields)) {
            return;
        }
        
        $encryptionService = app(EncryptionService::class);
        
        foreach ($this->encryptedFields as $field) {
            if (isset($this->attributes[$field]) && $this->attributes[$field]) {
                // Only encrypt if not already encrypted
                if (!$encryptionService->isEncrypted($this->attributes[$field])) {
                    $this->attributes[$field] = $encryptionService->encrypt($this->attributes[$field]);
                }
            }
        }
    }

    /**
     * Decrypt specified attributes
     */
    protected function decryptAttributes(): void
    {
        if (empty($this->encryptedFields)) {
            return;
        }

        $encryptionService = app(EncryptionService::class);

        foreach ($this->encryptedFields as $field) {
            if (isset($this->attributes[$field]) && $this->attributes[$field]) {
                try {
                    $this->attributes[$field] = $encryptionService->decrypt($this->attributes[$field]);
                } catch (\Exception $e) {
                    // Leave the value as-is rather than corrupting it on decrypt failure
                    \Illuminate\Support\Facades\Log::warning('Encryptable: failed to decrypt field', [
                        'model' => static::class,
                        'field' => $field,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get encrypted fields
     */
    public function getEncryptedFields(): array
    {
        return $this->encryptedFields;
    }
}
