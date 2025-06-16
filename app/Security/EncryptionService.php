<?php

namespace App\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptionService
{
    /**
     * Fields that should be encrypted in database
     */
    private array $encryptedFields = [
        'calcom_api_key',
        'retell_api_key',
        'stripe_secret_key',
        'webhook_secret',
        'smtp_password',
    ];

    /**
     * Encrypt sensitive data
     */
    public function encrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Log decryption failure but don't expose error
            \Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if field should be encrypted
     */
    public function shouldEncrypt(string $field): bool
    {
        return in_array($field, $this->encryptedFields);
    }

    /**
     * Add field to encryption list
     */
    public function addEncryptedField(string $field): void
    {
        if (!in_array($field, $this->encryptedFields)) {
            $this->encryptedFields[] = $field;
        }
    }
}