<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ApiKeyService
{
    /**
     * Safely encrypt an API key
     */
    public static function encrypt(?string $key): ?string
    {
        if (empty($key)) {
            return null;
        }
        
        try {
            return Crypt::encryptString($key);
        } catch (\Exception $e) {
            Log::error('Failed to encrypt API key', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Safely decrypt an API key
     */
    public static function decrypt(?string $encryptedKey): ?string
    {
        if (empty($encryptedKey)) {
            return null;
        }
        
        // Check if already decrypted (for backward compatibility)
        if (strlen($encryptedKey) < 50 || !str_starts_with($encryptedKey, 'eyJ')) {
            return $encryptedKey;
        }
        
        try {
            return Crypt::decryptString($encryptedKey);
        } catch (\Exception $e) {
            Log::warning('API key decryption failed, attempting legacy format', [
                'key_length' => strlen($encryptedKey),
            ]);
            
            // Try legacy decrypt method
            try {
                return decrypt($encryptedKey);
            } catch (\Exception $e2) {
                // Return as-is, might be plain text
                return $encryptedKey;
            }
        }
    }
    
    /**
     * Mask API key for logging
     */
    public static function mask(?string $key): string
    {
        if (empty($key)) {
            return '[empty]';
        }
        
        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }
    
    /**
     * Validate API key format
     */
    public static function isValid(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }
        
        // Retell API keys start with "key_"
        if (str_contains($key, 'retell')) {
            return str_starts_with($key, 'key_') && strlen($key) > 10;
        }
        
        // Cal.com keys are usually UUIDs or similar
        return strlen($key) >= 32;
    }
}