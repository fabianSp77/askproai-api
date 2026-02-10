<?php

namespace App\Traits;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToCompany
 *
 * Provides automatic multi-tenant isolation by company_id.
 *
 * Features:
 * - Applies CompanyScope global scope for automatic query filtering
 * - Auto-fills company_id on model creation from authenticated user
 * - Provides company() relationship
 *
 * Usage:
 * - Add 'company_id' column to table migration
 * - Use trait in model: use BelongsToCompany;
 * - Super admins bypass scope via CompanyScope logic
 */
trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait
     */
    protected static function bootBelongsToCompany(): void
    {
        // Apply global scope for automatic company filtering
        static::addGlobalScope(new CompanyScope);

        // Auto-fill company_id on creation
        static::creating(function (Model $model) {
            if (!$model->company_id && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }

            // Prevent saving without company_id (tenant isolation guard)
            if (!$model->company_id) {
                throw new \RuntimeException(
                    'TENANT ISOLATION: ' . class_basename($model) . ' cannot be created without company_id. '
                    . 'Set company_id explicitly or ensure user is authenticated.'
                );
            }
        });
    }

    /**
     * Get the company that owns the model
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
