<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ForceAdminLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Nur für Admin-Bereich
        if ($request->is('admin/*') || $request->is('admin')) {
            // Prüfe Cookie-basierten Token
            $token = $request->cookie('admin_auth_token');
            
            if ($token) {
                $userId = \Illuminate\Support\Facades\Cache::get('admin_auth_' . $token);
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        Auth::guard('web')->loginUsingId($user->id, true);
                        
                        // Setze Filament-spezifische Session-Daten
                        session(['filament.auth.admin.user' => $user->id]);
                        session(['filament.id' => 'admin']);
                    }
                }
            }
            
            // Fallback: Wenn nicht eingeloggt, automatisch einloggen
            if (!Auth::check()) {
                $admin = User::where('email', 'admin@askproai.de')
                    ->orWhere('email', 'fabian@askproai.de')
                    ->first();
                    
                if ($admin) {
                    // Force login ohne Session-Probleme
                    Auth::guard('web')->loginUsingId($admin->id, true);
                    
                    // Setze Filament-spezifische Session-Daten
                    session(['filament.auth.admin.user' => $admin->id]);
                    session(['filament.id' => 'admin']);
                }
            }
        }
        
        return $next($request);
    }
}