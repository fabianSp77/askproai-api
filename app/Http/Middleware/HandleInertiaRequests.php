<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
// use Inertia\Middleware;

class HandleInertiaRequests
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';
    
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        // For now, just pass through
        return $next($request);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return null;
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            'auth' => [
                'user' => $request->user('portal') ? [
                    'id' => $request->user('portal')->id,
                    'name' => $request->user('portal')->name,
                    'email' => $request->user('portal')->email,
                    'role' => $request->user('portal')->role,
                    'company' => $request->user('portal')->company,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}