<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Prüfe auf Admin-Token
        $token = $request->get('admin_token');
        
        if ($token) {
            // Token aus Cache holen
            $userId = Cache::pull('admin_token_' . $token); // pull löscht nach Verwendung
            
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    // User einloggen
                    Auth::guard('web')->loginUsingId($user->id, true);
                    
                    // Session regenerieren
                    $request->session()->regenerate();
                    
                    // Filament-spezifische Session-Daten
                    session(['filament.auth.admin.user' => $user->id]);
                    session(['filament.id' => 'admin']);
                    
                    // Token aus URL entfernen
                    return redirect($request->url());
                }
            }
        }
        
        return $next($request);
    }
}