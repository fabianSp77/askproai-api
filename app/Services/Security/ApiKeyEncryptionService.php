<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class ApiKeyEncryptionService
{
    /**
     * Encrypt an API key for secure storage
     */
    public function encrypt(?string $key): ?string
    {
        if (empty($key)) {
            return null;
        }

        try {
            return Crypt::encryptString($key);
        } catch (\Exception $e) {
            Log::error('Failed to encrypt API key', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt an API key for use
     */
    public function decrypt(?string $encryptedKey): ?string
    {
        if (empty($encryptedKey)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedKey);
        } catch (DecryptException $e) {
            // If decryption fails, it might be plain text (legacy)
            // Check if it looks like an encrypted value
            if ($this->isEncrypted($encryptedKey)) {
                Log::error('Failed to decrypt API key', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            
            // Return as-is if it's not encrypted (legacy support)
            return $encryptedKey;
        }
    }

    /**
     * Check if a value is encrypted
     */
    public function isEncrypted(string $value): bool
    {
        // Laravel encrypted values start with "eyJ"
        return str_starts_with($value, 'eyJ');
    }

    /**
     * Rotate an API key by encrypting plain text keys
     */
    public function rotateIfNeeded(string $key): string
    {
        if (!$this->isEncrypted($key)) {
            return $this->encrypt($key);
        }
        
        return $key;
    }

    /**
     * Generate a secure API key
     */
    public function generateSecureKey(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash an API key for comparison (one-way)
     */
    public function hash(string $key): string
    {
        return hash('sha256', $key);
    }
}