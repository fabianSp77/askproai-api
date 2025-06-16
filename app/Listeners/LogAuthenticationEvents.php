<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class LogAuthenticationEvents
{
    public function handleAttempting(Attempting $event)
    {
        Log::channel('single')->info('=== AUTH EVENT: ATTEMPTING ===', [
            'guard' => $event->guard,
            'credentials' => [
                'email' => $event->credentials['email'] ?? 'not provided',
                'has_password' => isset($event->credentials['password']),
            ],
            'remember' => $event->remember ?? false,
            'session_id' => session()->getId(),
            'request_ip' => request()->ip(),
        ]);
    }
    
    public function handleAuthenticated(Authenticated $event)
    {
        Log::channel('single')->info('=== AUTH EVENT: AUTHENTICATED ===', [
            'guard' => $event->guard,
            'user' => $event->user->email,
            'user_id' => $event->user->id,
            'session_id' => session()->getId(),
        ]);
    }
    
    public function handleFailed(Failed $event)
    {
        Log::channel('single')->warning('=== AUTH EVENT: FAILED ===', [
            'guard' => $event->guard,
            'credentials' => [
                'email' => $event->credentials['email'] ?? 'not provided',
            ],
            'user' => $event->user?->email,
            'session_id' => session()->getId(),
        ]);
    }
    
    public function handleLogin(Login $event)
    {
        Log::channel('single')->info('=== AUTH EVENT: LOGIN SUCCESS ===', [
            'guard' => $event->guard,
            'user' => $event->user->email,
            'user_id' => $event->user->id,
            'remember' => $event->remember,
            'session_id' => session()->getId(),
            'session_regenerated' => true,
        ]);
    }
    
    public function handleLogout(Logout $event)
    {
        Log::channel('single')->info('=== AUTH EVENT: LOGOUT ===', [
            'guard' => $event->guard,
            'user' => $event->user?->email,
            'session_id' => session()->getId(),
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