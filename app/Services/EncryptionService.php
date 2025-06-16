<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptionService
{
    /**
     * Encrypt sensitive data
     */
    public function encrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            \Log::error('Encryption failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to encrypt sensitive data');
        }
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
            \Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Check if a value is encrypted
     */
    public function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
    
    /**
     * Rotate encryption key (requires APP_KEY rotation)
     */
    public function rotateKey(array $models, array $fields): void
    {
        \DB::transaction(function () use ($models, $fields) {
            foreach ($models as $model) {
                $records = $model::all();
                
                foreach ($records as $record) {
                    foreach ($fields as $field) {
                        if (!empty($record->$field)) {
                            // Decrypt with old key
                            $decrypted = $this->decrypt($record->$field);
                            if ($decrypted) {
                                // Re-encrypt with new key
                                $record->$field = $this->encrypt($decrypted);
                            }
                        }
                    }
                    $record->save();
                }
            }
        });
    }
}