<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Services\Security\ApiKeyService;

class Tenant extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    // slug  + api_key jetzt mass-assignable
    protected $fillable = ['name', 'slug', 'api_key'];
    
    // Hide sensitive fields
    protected $hidden = ['api_key'];

    /* -------------------------------------------------------------------- */
    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            $tenant->id       ??= (string) Str::uuid();
            $tenant->slug     ??= Str::slug($tenant->name);
            $tenant->api_key  ??= Str::random(32);
        });
        
        // Encrypt API key before saving
        static::saving(function (self $tenant) {
            if ($tenant->isDirty('api_key') && !empty($tenant->api_key)) {
                // Check if not already encrypted (encrypted values start with 'eyJ')
                if (!str_starts_with($tenant->api_key, 'eyJ')) {
                    $apiKeyService = app(ApiKeyService::class);
                    $tenant->api_key = $apiKeyService->encrypt($tenant->api_key);
                }
            }
        });
    }

    /**
     * Get the decrypted API key
     */
    public function getApiKeyAttribute($value): ?string
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
                \Log::error('Failed to decrypt tenant API key', [
                    'tenant_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        // Return as-is if not encrypted (for backward compatibility during migration)
        return $value;
    }

    /**
     * Set the API key (will be encrypted on save)
     */
    public function setApiKeyAttribute($value): void
    {
        // Don't encrypt here, let the saving event handle it
        // This prevents double encryption
        $this->attributes['api_key'] = $value;
    }

    /**
     * Get the raw encrypted API key (for debugging/verification)
     */
    public function getRawApiKey(): ?string
    {
        return $this->attributes['api_key'] ?? null;
    }

    /**
     * Generate a new API key
     */
    public function regenerateApiKey(): string
    {
        $newKey = Str::random(32);
        $this->api_key = $newKey;
        $this->save();
        
        return $newKey;
    }

    /**
     * Verify an API key matches this tenant's key
     */
    public function verifyApiKey(string $providedKey): bool
    {
        return hash_equals($this->api_key ?? '', $providedKey);
    }

    /* -------------------------------------------------------------------- */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}