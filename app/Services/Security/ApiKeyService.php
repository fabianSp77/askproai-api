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
        
        // SECURITY: Do NOT accept plain text keys
        // All keys must be encrypted
        
        try {
            return Crypt::decryptString($encryptedKey);
        } catch (\Exception $e) {
            // Log security incident if plain text key is attempted
            if (self::looksLikePlainTextKey($encryptedKey)) {
                Log::critical('SECURITY: Plain text API key detected', [
                    'key_pattern' => self::mask($encryptedKey),
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
                ]);
            }
            
            Log::error('API key decryption failed', [
                'error' => $e->getMessage(),
            ]);
            
            // Do NOT return the key as-is
            return null;
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
    
    /**
     * Check if a string looks like a plain text API key
     */
    private static function looksLikePlainTextKey(string $value): bool
    {
        // Retell keys
        if (str_starts_with($value, 'key_')) {
            return true;
        }
        
        // Cal.com style UUIDs
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $value)) {
            return true;
        }
        
        // Stripe style keys
        if (str_starts_with($value, 'sk_') || str_starts_with($value, 'pk_')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if an API key is encrypted
     */
    public static function isEncrypted(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }
        
        // Laravel encrypted strings typically start with 'eyJ'
        // and are base64 encoded JSON
        return str_starts_with($key, 'eyJ') && strlen($key) > 100;
    }
}