<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class LogAuthenticationAttempts
{
    private function logToFile($event, $data)
    {
        $logFile = storage_path('logs/auth-events-' . date('Y-m-d') . '.log');
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'event' => $event,
            'data' => $data,
            'session_id' => session()->getId(),
            'request_url' => request()->fullUrl(),
        ];
        file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    }

    public function handleAttempting(Attempting $event)
    {
        $this->logToFile('attempting', [
            'credentials' => array_keys($event->credentials),
            'remember' => $event->remember ?? false,
            'guard' => $event->guard,
        ]);
    }

    public function handleAuthenticated(Authenticated $event)
    {
        $this->logToFile('authenticated', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'guard' => $event->guard,
        ]);
    }

    public function handleLogin(Login $event)
    {
        $this->logToFile('login', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'remember' => $event->remember ?? false,
            'guard' => $event->guard,
        ]);
    }

    public function handleFailed(Failed $event)
    {
        $this->logToFile('failed', [
            'credentials' => array_keys($event->credentials),
            'user' => $event->user?->email ?? 'null',
            'guard' => $event->guard,
        ]);
    }

    public function handleLogout(Logout $event)
    {
        $this->logToFile('logout', [
            'user_id' => $event->user->id ?? 'null',
            'user_email' => $event->user->email ?? 'null',
            'guard' => $event->guard,
        ]);
    }
}