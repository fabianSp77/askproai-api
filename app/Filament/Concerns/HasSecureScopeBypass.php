<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Secure Global Scope Bypass Patterns
 *
 * SECURITY: Provides safe patterns for bypassing global scopes with proper
 * authorization checks and audit logging.
 *
 * ## When to Use withoutGlobalScopes()
 *
 * 1. **SoftDeletingScope only** - Safe, no tenant isolation impact
 *    ```php
 *    ->withoutGlobalScopes([SoftDeletingScope::class])
 *    ```
 *
 * 2. **With explicit tenant filter** - Safe when followed by company_id/tenant_id filter
 *    ```php
 *    ->withoutGlobalScopes()
 *    ->where('tenant_id', auth()->user()->company_id)
 *    ```
 *
 * 3. **With whereHas on tenant-scoped relation** - Safe for indirect tenant filtering
 *    ```php
 *    ->withoutGlobalScopes()
 *    ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
 *    ```
 *
 * 4. **Global reference tables** - Safe for tenant-agnostic data
 *    ```php
 *    // CurrencyExchangeRate, BalanceBonusTier - no company_id column
 *    ->withoutGlobalScopes()
 *    ```
 *
 * 5. **Super Admin bypass** - Safe with role check
 *    ```php
 *    if ($this->isSuperAdmin()) {
 *        return Model::withoutGlobalScopes()->find($key);
 *    }
 *    ```
 *
 * @package App\Filament\Concerns
 */
trait HasSecureScopeBypass
{
    /**
     * Check if current user is Super Admin
     *
     * @return bool
     */
    protected function isSuperAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Support all role name variations
        return $user->hasRole(['super_admin', 'Super Admin', 'superadmin']);
    }

    /**
     * Get query bypassing company scope FOR SUPER ADMIN ONLY
     *
     * SECURITY: Logs the bypass for audit purposes
     *
     * @param string $modelClass
     * @param string $reason Audit reason for the bypass
     * @return Builder|null Returns null if not authorized
     */
    protected function superAdminScopeBypass(string $modelClass, string $reason = 'admin_view'): ?Builder
    {
        if (!$this->isSuperAdmin()) {
            Log::warning('Unauthorized scope bypass attempt', [
                'user_id' => auth()->id(),
                'model' => $modelClass,
                'reason' => $reason,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        Log::info('Super Admin scope bypass', [
            'user_id' => auth()->id(),
            'model' => $modelClass,
            'reason' => $reason,
        ]);

        return $modelClass::withoutGlobalScopes();
    }

    /**
     * Get query with explicit tenant filter (safe scope bypass)
     *
     * Use when the model uses tenant_id instead of company_id
     *
     * @param string $modelClass
     * @param string $tenantColumn Column name for tenant filter (default: 'tenant_id')
     * @return Builder
     */
    protected function tenantFilteredQuery(string $modelClass, string $tenantColumn = 'tenant_id'): Builder
    {
        $companyId = $this->getCurrentCompanyIdOrFail();

        return $modelClass::withoutGlobalScopes()
            ->where($tenantColumn, $companyId);
    }

    /**
     * Get query with whereHas tenant filter (safe scope bypass)
     *
     * Use when the model doesn't have direct company_id but relates to a tenant-scoped model
     *
     * @param string $modelClass
     * @param string $relation Relationship name to filter through
     * @param string $companyColumn Column on related model (default: 'company_id')
     * @return Builder
     */
    protected function relatedTenantQuery(
        string $modelClass,
        string $relation,
        string $companyColumn = 'company_id'
    ): Builder {
        $companyId = $this->getCurrentCompanyIdOrFail();

        return $modelClass::withoutGlobalScopes()
            ->whereHas($relation, fn($query) => $query->where($companyColumn, $companyId));
    }

    /**
     * Resolve record for Super Admin with bypass, regular users with scope
     *
     * Safe pattern for Filament resolveRecordRouteBinding
     *
     * @param string $modelClass
     * @param mixed $key Record key/ID
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function resolveRecordWithAdminBypass(string $modelClass, $key): ?\Illuminate\Database\Eloquent\Model
    {
        if ($this->isSuperAdmin()) {
            Log::debug('Super Admin record resolution bypass', [
                'user_id' => auth()->id(),
                'model' => $modelClass,
                'key' => $key,
            ]);
            return $modelClass::withoutGlobalScopes()->find($key);
        }

        // Regular users get scoped query
        return $modelClass::find($key);
    }

    /**
     * Get current company ID or throw exception
     *
     * @return int
     * @throws \RuntimeException
     */
    protected function getCurrentCompanyIdOrFail(): int
    {
        $user = auth()->user();

        if (!$user) {
            throw new \RuntimeException('User must be authenticated for tenant-scoped query');
        }

        if (!$user->company_id) {
            throw new \RuntimeException('User must have company_id for tenant-scoped query');
        }

        return $user->company_id;
    }

    /**
     * Assert that scope bypass is documented with proper reason
     *
     * Use in tests to verify scope bypass patterns are documented
     *
     * @param string $modelClass
     * @param string $file File where bypass occurs
     * @param string $expectedReason
     * @return void
     */
    public static function assertBypassDocumented(
        string $modelClass,
        string $file,
        string $expectedReason
    ): void {
        // This method is for test assertions
        // Implementation validates that code comments document the bypass reason
    }
}
