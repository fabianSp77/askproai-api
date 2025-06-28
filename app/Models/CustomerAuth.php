<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\Security\ApiKeyService;
use Illuminate\Support\Str;

class CustomerAuth extends Authenticatable
{
    use HasFactory;

    protected $table = 'customer_auth';

    protected $fillable = [
        'customer_id',
        'email',
        'password',
        'portal_access_token',
        'portal_access_token_expires_at',
        'last_login_at',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'portal_access_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'portal_access_token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed', // Laravel 11 feature
    ];

    protected static function booted(): void
    {
        // Encrypt portal_access_token before saving
        static::saving(function (self $auth) {
            if ($auth->isDirty('portal_access_token') && !empty($auth->portal_access_token)) {
                // Check if not already encrypted (encrypted values start with 'eyJ')
                if (!str_starts_with($auth->portal_access_token, 'eyJ')) {
                    $apiKeyService = app(ApiKeyService::class);
                    $auth->portal_access_token = $apiKeyService->encrypt($auth->portal_access_token);
                }
            }
        });
    }

    /**
     * Get the decrypted portal access token
     */
    public function getPortalAccessTokenAttribute($value): ?string
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
                \Log::error('Failed to decrypt portal access token', [
                    'customer_auth_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        // Return as-is if not encrypted (for backward compatibility during migration)
        return $value;
    }

    /**
     * Set the portal access token (will be encrypted on save)
     */
    public function setPortalAccessTokenAttribute($value): void
    {
        // Don't encrypt here, let the saving event handle it
        // This prevents double encryption
        $this->attributes['portal_access_token'] = $value;
    }

    /**
     * Generate a new portal access token
     */
    public function generatePortalAccessToken(): string
    {
        $token = Str::random(60);
        $this->portal_access_token = $token;
        $this->portal_access_token_expires_at = now()->addDays(30);
        $this->save();

        return $token;
    }

    /**
     * Check if portal access token is valid
     */
    public function isPortalAccessTokenValid(): bool
    {
        if (empty($this->portal_access_token)) {
            return false;
        }

        if ($this->portal_access_token_expires_at && $this->portal_access_token_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verify a provided token matches this auth's token
     */
    public function verifyPortalAccessToken(string $providedToken): bool
    {
        if (!$this->isPortalAccessTokenValid()) {
            return false;
        }

        return hash_equals($this->portal_access_token ?? '', $providedToken);
    }

    /**
     * Revoke the portal access token
     */
    public function revokePortalAccessToken(): void
    {
        $this->portal_access_token = null;
        $this->portal_access_token_expires_at = null;
        $this->save();
    }

    /**
     * Get the raw encrypted portal access token (for debugging/verification)
     */
    public function getRawPortalAccessToken(): ?string
    {
        return $this->attributes['portal_access_token'] ?? null;
    }

    /**
     * Update last login timestamp
     */
    public function recordLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get the customer relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the name attribute for the authenticatable
     */
    public function getNameAttribute(): string
    {
        return $this->customer->name ?? $this->email;
    }

    /**
     * Get the unique identifier for authentication
     */
    public function getAuthIdentifierName(): string
    {
        return 'email';
    }

    /**
     * Get the password for authentication
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }
}