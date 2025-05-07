<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;                     //  <- neu
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = Str::of($request->getHost())->before('.')->value();
        $tenant = Tenant::whereSlug($slug)->first();

        app()->instance('currentTenant', optional($tenant)->id);

        return $next($request);
    }
}
