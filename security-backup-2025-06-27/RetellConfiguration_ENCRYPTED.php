<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Security\ApiKeyService;

class RetellConfiguration extends Model
{
    protected $fillable = [
        'company_id',
        'agent_id',
        'agent_name',
        'webhook_secret',
        'voice_settings',
        'llm_settings',
        'general_settings',
        'is_active'
    ];

    protected $hidden = [
        'webhook_secret'
    ];

    protected $casts = [
        'voice_settings' => 'array',
        'llm_settings' => 'array',
        'general_settings' => 'array',
        'is_active' => 'boolean'
    ];

    protected static function booted(): void
    {
        // Encrypt webhook_secret before saving
        static::saving(function (self $config) {
            if ($config->isDirty('webhook_secret') && !empty($config->webhook_secret)) {
                // Check if not already encrypted (encrypted values start with 'eyJ')
                if (!str_starts_with($config->webhook_secret, 'eyJ')) {
                    $apiKeyService = app(ApiKeyService::class);
                    $config->webhook_secret = $apiKeyService->encrypt($config->webhook_secret);
                }
            }
        });
    }

    /**
     * Get the decrypted webhook secret
     */
    public function getWebhookSecretAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Check if encrypted (starts with 'eyJ')
        if (str_starts_with($value, 'eyJ')) {
            try {
                $apiKeyService = app(ApiKeyService::class);
                return $apiKeyService->decrypt($value);
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt webhook secret', [
                    'retell_configuration_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        // Return as-is if not encrypted (for backward compatibility during migration)
        return $value;
    }

    /**
     * Set the webhook secret (will be encrypted on save)
     */
    public function setWebhookSecretAttribute($value): void
    {
        // Don't encrypt here, let the saving event handle it
        // This prevents double encryption
        $this->attributes['webhook_secret'] = $value;
    }

    /**
     * Verify a webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhook_secret)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get the raw encrypted webhook secret (for debugging/verification)
     */
    public function getRawWebhookSecret(): ?string
    {
        return $this->attributes['webhook_secret'] ?? null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}