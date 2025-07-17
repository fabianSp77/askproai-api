<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Empty TenantScope that does nothing - temporary fix
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * TEMPORARILY DISABLED - Does nothing
     */
    public function apply(Builder $builder, Model $model)
    {
        // Do nothing - scope is disabled
        return;
    }
}