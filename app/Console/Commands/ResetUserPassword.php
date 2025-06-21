<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email} {password}';
    protected $description = 'Reset user password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }
        
        // Update password directly
        $user->password = Hash::make($password);
        
        // Disable timestamps for this update
        $user->timestamps = false;
        $user->save();
        
        $this->info("Password updated successfully for {$email}");
        
        // Test authentication
        if (\Auth::attempt(['email' => $email, 'password' => $password])) {
            $this->info("✓ Authentication test passed!");
        } else {
            $this->error("✗ Authentication test failed!");
        }
        
        return 0;
    }
}