<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if we have a company context
        if ($companyId = $this->getCompanyId()) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    /**
     * Get the current company ID from various sources
     */
    protected function getCompanyId(): ?int
    {
        // Try to get from authenticated user
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }

        // Try to get from session
        if (session()->has('company_id')) {
            return session('company_id');
        }

        // Try to get from request header (for API calls)
        if (request()->hasHeader('X-Company-Id')) {
            return (int) request()->header('X-Company-Id');
        }

        return null;
    }
}