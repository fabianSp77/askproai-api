<?php

namespace App\Traits;

use App\Services\EncryptionService;

trait HasEncryptedAttributes
{
    /**
     * The attributes that should be encrypted
     */
    protected function getEncryptedAttributes(): array
    {
        return $this->encrypted ?? [];
    }
    
    /**
     * Boot the trait
     */
    public static function bootHasEncryptedAttributes(): void
    {
        $encryptionService = app(EncryptionService::class);
        
        // Encrypt on save
        static::saving(function ($model) use ($encryptionService) {
            foreach ($model->getEncryptedAttributes() as $attribute) {
                if ($model->isDirty($attribute) && !empty($model->$attribute)) {
                    if (!$encryptionService->isEncrypted($model->$attribute)) {
                        $model->$attribute = $encryptionService->encrypt($model->$attribute);
                    }
                }
            }
        });
        
        // Decrypt on retrieval
        static::retrieved(function ($model) use ($encryptionService) {
            foreach ($model->getEncryptedAttributes() as $attribute) {
                if (!empty($model->$attribute)) {
                    $decrypted = $encryptionService->decrypt($model->$attribute);
                    if ($decrypted !== null) {
                        $model->$attribute = $decrypted;
                    }
                }
            }
        });
    }
    
    /**
     * Get decrypted attribute value
     */
    public function getDecryptedAttribute(string $attribute): ?string
    {
        if (!in_array($attribute, $this->getEncryptedAttributes())) {
            return $this->$attribute;
        }
        
        $encryptionService = app(EncryptionService::class);
        return $encryptionService->decrypt($this->attributes[$attribute] ?? null);
    }
}