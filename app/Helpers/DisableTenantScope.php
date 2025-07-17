<?php

namespace App\Helpers;

use App\Scopes\TenantScope;

trait DisableTenantScope
{
    public static function withoutTenantScope($callback)
    {
        TenantScope::disable();
        try {
            return $callback();
        } finally {
            TenantScope::enable();
        }
    }
}