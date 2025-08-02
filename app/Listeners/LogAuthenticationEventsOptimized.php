<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LogAuthenticationEventsOptimized
{
    private function shouldLog($event, $key): bool
    {
        // Erstelle einen eindeutigen Cache-Key basierend auf Event-Typ und User
        $cacheKey = 'auth_log_' . $key;
        
        // Prüfe ob wir dieses Event in den letzten 5 Sekunden bereits geloggt haben
        if (Cache::has($cacheKey)) {
            return false;
        }
        
        // Markiere dass wir dieses Event geloggt haben
        Cache::put($cacheKey, true, 5); // 5 Sekunden Cache
        
        return true;
    }
    
    public function handleAttempting(Attempting $event)
    {
        $key = 'attempting_' . ($event->credentials['email'] ?? 'unknown');
        
        if ($this->shouldLog($event, $key)) {
            Log::channel('auth')->info('AUTH: Login attempt', [
                'email' => $event->credentials['email'] ?? 'not provided',
                'ip' => request()->ip(),
            ]);
        }
    }
    
    public function handleAuthenticated(Authenticated $event)
    {
        // Nur loggen wenn es NICHT von Livewire kommt
        if (request()->hasHeader('X-Livewire')) {
            return;
        }
        
        $key = 'authenticated_' . $event->user->id . '_' . session()->getId();
        
        if ($this->shouldLog($event, $key)) {
            Log::channel('auth')->info('AUTH: User authenticated', [
                'user' => $event->user->email,
                'user_id' => $event->user->id,
            ]);
        }
    }
    
    public function handleFailed(Failed $event)
    {
        // Failed logins sollten immer geloggt werden
        Log::channel('auth')->warning('AUTH: Login failed', [
            'email' => $event->credentials['email'] ?? 'not provided',
            'ip' => request()->ip(),
        ]);
    }
    
    public function handleLogin(Login $event)
    {
        // Login success sollte immer geloggt werden
        Log::channel('auth')->info('AUTH: Login successful', [
            'user' => $event->user->email,
            'user_id' => $event->user->id,
            'remember' => $event->remember,
        ]);
    }
    
    public function handleLogout(Logout $event)
    {
        // Logout sollte immer geloggt werden
        Log::channel('auth')->info('AUTH: User logged out', [
            'user' => $event->user?->email,
        ]);
    }
    
    public function subscribe($events)
    {
        return [
            Attempting::class => 'handleAttempting',
            Authenticated::class => 'handleAuthenticated',
            Failed::class => 'handleFailed',
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
        ];
    }
}