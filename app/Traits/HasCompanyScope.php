<?php

namespace App\Traits;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasCompanyScope
{
    /**
     * Boot the has company scope trait for a model.
     */
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope);

        // Automatically set company_id on create
        static::creating(function (Model $model) {
            if (Auth::check() && !$model->company_id) {
                $user = Auth::user();
                if ($user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }

    /**
     * Get the company that owns the model.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Scope a query to only include models of a given company.
     */
    public function scopeOfCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Determine if the model belongs to the given company.
     */
    public function belongsToCompany($companyId): bool
    {
        return $this->company_id === $companyId;
    }

    /**
     * Determine if the model belongs to the authenticated user's company.
     */
    public function belongsToUserCompany(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->company_id === Auth::user()->company_id;
    }
}