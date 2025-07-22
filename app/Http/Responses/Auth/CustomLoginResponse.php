<?php

namespace App\Http\Responses\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // IMPORTANT: At this point, the user is already logged in
        // but Laravel may have destroyed the session data
        
        // Re-save the session to ensure it persists
        $session = app('session.store');
        $session->save();
        
        // Ensure the auth session key is set
        if (auth()->check()) {
            $user = auth()->user();
            $guard = auth()->guard('web');
            
            // Get the actual session key
            $reflection = new \ReflectionMethod($guard, 'getName');
            $reflection->setAccessible(true);
            $sessionKey = $reflection->invoke($guard);
            
            $session->put($sessionKey, $user->id);
            $session->put('password_hash_web', $user->password);
            $session->save();
        }
        
        return redirect()->intended(Filament::getUrl());
    }
}