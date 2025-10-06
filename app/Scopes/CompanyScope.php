<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Cached user instance for the current request to avoid repeated Auth::user() calls.
     * This prevents memory exhaustion from loading user 27+ times for navigation badges.
     */
    private static $cachedUser = null;
    private static $cachedUserId = null;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if user is authenticated
        if (!Auth::check()) {
            return;
        }

        // PERFORMANCE FIX: Cache user for request lifecycle to prevent repeated loading
        // Problem: Auth::user() was called 27+ times for navigation badges = memory cascade
        // Solution: Cache user once per request
        $userId = Auth::id();

        if (self::$cachedUserId !== $userId) {
            self::$cachedUser = Auth::user();
            self::$cachedUserId = $userId;
        }

        $user = self::$cachedUser;

        if (!$user) {
            return;
        }

        // Super admins can see all companies
        // SAFE: Navigation badges are now disabled, role caching prevents memory cascade
        if ($user->hasRole('super_admin')) {
            return;
        }

        // Apply company filtering if user has company_id
        if ($user->company_id) {
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        }
    }

    /**
     * Extend the builder with custom methods.
     *
     * MEMORY FIX: Prevent duplicate macro registration during OPcache revalidation cycles.
     * Without this guard, macros get registered multiple times causing memory exhaustion.
     */
    public function extend(Builder $builder): void
    {
        // Defensive check: Prevent duplicate macro registration
        if ($builder->hasMacro('withoutCompanyScope')) {
            return; // Already registered, skip to prevent memory duplication
        }

        $builder->macro('withoutCompanyScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forCompany', function (Builder $builder, $companyId) {
            return $builder->withoutGlobalScope($this)
                ->where('company_id', $companyId);
        });

        $builder->macro('allCompanies', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}