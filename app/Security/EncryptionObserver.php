<?php

namespace App\Security;

use Illuminate\Database\Eloquent\Model;

class EncryptionObserver
{
    private EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Handle the model "saving" event - encrypt before save
     */
    public function saving(Model $model): void
    {
        foreach ($model->getAttributes() as $key => $value) {
            if ($this->encryptionService->shouldEncrypt($key) && !empty($value)) {
                // Check if already encrypted (prevents double encryption)
                if (!$this->isEncrypted($value)) {
                    $model->setAttribute($key, $this->encryptionService->encrypt($value));
                }
            }
        }
    }

    /**
     * Handle the model "retrieved" event - decrypt after retrieval
     */
    public function retrieved(Model $model): void
    {
        foreach ($model->getAttributes() as $key => $value) {
            if ($this->encryptionService->shouldEncrypt($key) && !empty($value)) {
                if ($this->isEncrypted($value)) {
                    $model->setAttribute($key, $this->encryptionService->decrypt($value));
                }
            }
        }
    }

    /**
     * Check if value appears to be encrypted
     */
    private function isEncrypted(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Laravel encrypted strings start with "eyJ"
        return strpos($value, 'eyJ') === 0;
    }
}