<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Neutralisiert Tenancy komplett – kein currentTenant-Lookup.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // absichtlich leer
    }
}
