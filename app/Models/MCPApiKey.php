<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MCP API Key Model
 * 
 * Manages API keys for MCP authentication:
 * - Secure key generation
 * - Permission management
 * - Rate limiting configuration
 * - IP restrictions
 * - Expiration handling
 */
class MCPApiKey extends Model
{
    use HasFactory;
    
    protected $table = 'mcp_api_keys';
    
    protected $fillable = [
        'company_id',
        'reseller_id',
        'name',
        'key',
        'permissions',
        'allowed_ips',
        'rate_limit',
        'is_active',
        'expires_at',
        'last_used_at'
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime'
    ];
    
    protected $hidden = [
        'key' // Don't expose full key in API responses
    ];
    
    protected $appends = [
        'key_preview' // Show only preview of key
    ];
    
    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Reseller relationship
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'reseller_id');
    }
    
    /**
     * Generate a new API key
     */
    public static function generateKey(): string
    {
        return 'mcp_' . Str::random(32);
    }
    
    /**
     * Create a new API key for a company
     */
    public static function createForCompany(
        Company $company,
        string $name,
        array $permissions = [],
        array $options = []
    ): self {
        return self::create([
            'company_id' => $company->id,
            'reseller_id' => $options['reseller_id'] ?? null,
            'name' => $name,
            'key' => self::generateKey(),
            'permissions' => $permissions,
            'allowed_ips' => $options['allowed_ips'] ?? null,
            'rate_limit' => $options['rate_limit'] ?? 1000,
            'is_active' => true,
            'expires_at' => $options['expires_at'] ?? now()->addYear()
        ]);
    }
    
    /**
     * Check if key has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }
        
        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }
    
    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No restrictions
        }
        
        return in_array($ip, $this->allowed_ips);
    }
    
    /**
     * Mark key as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
    
    /**
     * Check if key is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Revoke the key
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }
    
    /**
     * Get key preview (first 8 chars + ...)
     */
    public function getKeyPreviewAttribute(): string
    {
        return substr($this->key, 0, 8) . '...' . substr($this->key, -4);
    }
    
    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
    
    /**
     * Scope for company keys
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
    
    /**
     * Default permissions for hair salon MCP
     */
    public static function getDefaultHairSalonPermissions(): array
    {
        return [
            'mcp.services.read',
            'mcp.staff.read',
            'mcp.availability.read',
            'mcp.appointments.create',
            'mcp.customers.read',
            'mcp.customers.create',
            'mcp.callbacks.create'
        ];
    }
    
    /**
     * Admin permissions for full MCP access
     */
    public static function getAdminPermissions(): array
    {
        return [
            '*' // All permissions
        ];
    }
}