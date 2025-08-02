<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FilamentSessionFix
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // For Filament login routes, disable session regeneration
        if ($request->is('admin/login') || $request->is('livewire/*')) {
            config(['session.regenerate' => false]);
        }

        // If we have a user in session but Auth doesn't recognize it, restore it
        $userId = Session::get('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
        if ($userId && ! Auth::check()) {
            Auth::loginUsingId($userId, true);
        }

        $response = $next($request);

        // Force session persistence after authentication
        if (Auth::check() && $request->is('admin/login')) {
            Session::put('login_web_' . sha1(get_class(Auth::user())), Auth::id());
            // Password hash storage removed for security
            Session::save();
        }

        return $response;
    }
}
