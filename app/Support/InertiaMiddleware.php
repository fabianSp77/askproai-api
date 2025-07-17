<?php

namespace Inertia;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Middleware
{
    protected $rootView = 'app';
    
    public function handle(Request $request, Closure $next)
    {
        \App\Support\InertiaFacade::share($this->share($request));
        
        if ($request->header('X-Inertia-Version') && $request->header('X-Inertia-Version') !== $this->version($request)) {
            return response('', 409)->header('X-Inertia-Location', $request->fullUrl());
        }
        
        return $next($request);
    }
    
    public function version(Request $request): ?string
    {
        return null;
    }
    
    public function share(Request $request): array
    {
        return [];
    }
}