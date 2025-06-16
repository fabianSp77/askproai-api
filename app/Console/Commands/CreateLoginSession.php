<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreateLoginSession extends Command
{
    protected $signature = 'auth:create-session {email}';
    protected $description = 'Create a login session for debugging';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }
        
        // Create a session token
        $token = bin2hex(random_bytes(32));
        
        // Store session data
        session(['login_token' => $token]);
        session(['user_id' => $user->id]);
        session(['auth_time' => now()]);
        
        $this->info("Login session created for: {$email}");
        $this->info("Session ID: " . session()->getId());
        $this->info("Token: {$token}");
        $this->info("Session data stored successfully");
        
        // Also output a direct login URL
        $loginUrl = url('/admin/debug-login?token=' . $token);
        $this->info("Direct login URL: {$loginUrl}");
        
        return 0;
    }
}