<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApiKeyService
{
    /**
     * Generate a new API key for tenant
     */
    public function generateApiKey(Tenant $tenant): array
    {
        $plainKey = 'ask_' . Str::random(32);
        $hashedKey = Hash::make($plainKey);
        
        // Update tenant with new hashed key
        $tenant->update([
            'api_key_hash' => $hashedKey,
            'api_key_generated_at' => now(),
        ]);

        Log::info('New API key generated', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);

        return [
            'api_key' => $plainKey,
            'key_id' => substr($plainKey, 0, 8) . '...' . substr($plainKey, -4),
            'generated_at' => now()->toISOString(),
            'tenant_id' => $tenant->id,
        ];
    }

    /**
     * Rotate API key for tenant
     */
    public function rotateApiKey(Tenant $tenant, string $reason = 'Manual rotation'): array
    {
        $oldKeyId = $tenant->api_key_hash ? substr($tenant->api_key_hash, 0, 12) . '...' : 'none';
        
        $newKeyData = $this->generateApiKey($tenant);

        Log::warning('API key rotated', [
            'tenant_id' => $tenant->id,
            'old_key_id' => $oldKeyId,
            'new_key_id' => $newKeyData['key_id'],
            'reason' => $reason,
        ]);

        return $newKeyData;
    }

    /**
     * Validate API key strength and format
     */
    public function validateApiKeyFormat(string $apiKey): array
    {
        $errors = [];
        
        // Check prefix
        if (!str_starts_with($apiKey, 'ask_')) {
            $errors[] = 'API key must start with "ask_" prefix';
        }

        // Check length
        if (strlen($apiKey) < 36) {
            $errors[] = 'API key must be at least 36 characters long';
        }

        // Check character set (alphanumeric only for security)
        if (!preg_match('/^ask_[a-zA-Z0-9]+$/', $apiKey)) {
            $errors[] = 'API key contains invalid characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get API key usage statistics
     */
    public function getApiKeyStats(Tenant $tenant, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        // This would require an api_requests log table
        // For now, return basic info
        return [
            'tenant_id' => $tenant->id,
            'key_generated_at' => $tenant->api_key_generated_at ?? $tenant->created_at,
            'key_age_days' => $tenant->api_key_generated_at 
                ? $tenant->api_key_generated_at->diffInDays(now())
                : $tenant->created_at->diffInDays(now()),
            'last_used_at' => $tenant->updated_at, // Placeholder
        ];
    }

    /**
     * Check if API key rotation is recommended
     */
    public function shouldRotateApiKey(Tenant $tenant): array
    {
        $keyAge = $tenant->api_key_generated_at 
            ? $tenant->api_key_generated_at->diffInDays(now())
            : $tenant->created_at->diffInDays(now());

        $recommendations = [];
        
        // Recommend rotation after 90 days
        if ($keyAge > 90) {
            $recommendations[] = [
                'severity' => 'medium',
                'reason' => 'API key is older than 90 days',
                'action' => 'Consider rotating for security best practices'
            ];
        }

        // Recommend rotation after 180 days (warning)
        if ($keyAge > 180) {
            $recommendations[] = [
                'severity' => 'high',
                'reason' => 'API key is older than 6 months',
                'action' => 'Immediate rotation recommended'
            ];
        }

        return [
            'should_rotate' => !empty($recommendations),
            'key_age_days' => $keyAge,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Revoke API key (mark as inactive)
     */
    public function revokeApiKey(Tenant $tenant, string $reason = 'Manual revocation'): bool
    {
        $tenant->update([
            'api_key_hash' => null,
            'api_key_revoked_at' => now(),
            'api_key_revoke_reason' => $reason,
        ]);

        Log::warning('API key revoked', [
            'tenant_id' => $tenant->id,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Audit API key usage patterns
     */
    public function auditApiKeyUsage(Tenant $tenant): array
    {
        // This would analyze request logs for suspicious patterns
        // Implementation would depend on logging infrastructure
        
        return [
            'tenant_id' => $tenant->id,
            'audit_date' => now()->toISOString(),
            'patterns_detected' => [],
            'recommendations' => [],
            'security_score' => 85, // Placeholder
        ];
    }
}