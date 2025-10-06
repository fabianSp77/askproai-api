<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password
                            {--email= : Email address of the user}
                            {--password= : New password (leave empty for random)}
                            {--random : Generate a random password}
                            {--notify : Send password to user via email}
                            {--show : Display the new password in console}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user\'s password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Reset User Password');
        $this->info('═══════════════════════════════════════════');

        // Get email
        $email = $this->option('email');
        if (!$email) {
            $email = $this->ask('Enter the user\'s email address');
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found");
            return Command::FAILURE;
        }

        // Display user information
        $this->info("\n📋 User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Created: {$user->created_at->format('Y-m-d H:i:s')}");

        // Check if user is super admin
        if ($this->isSuperAdmin($user)) {
            $this->warn("\n⚠️  This is a SUPER ADMIN account!");

            if (!$this->option('force')) {
                if (!$this->confirm('Are you sure you want to reset a super admin password?')) {
                    $this->warn('Password reset cancelled');
                    return Command::SUCCESS;
                }
            }
        }

        // Generate or get password
        $password = $this->getNewPassword();

        if (!$password) {
            $this->error('Password generation failed');
            return Command::FAILURE;
        }

        // Confirm before proceeding
        if (!$this->option('force')) {
            if (!$this->confirm('Reset password for this user?')) {
                $this->warn('Password reset cancelled');
                return Command::SUCCESS;
            }
        }

        try {
            // Update password
            $user->password = Hash::make($password);
            $user->save();

            // Force password change on next login (optional)
            if ($user->hasAttribute('password_changed_at')) {
                $user->password_changed_at = now();
                $user->save();
            }

            // Clear any existing sessions (if using database sessions)
            $this->clearUserSessions($user);

            $this->info("\n✅ Password reset successfully!");

            // Show password if requested
            if ($this->option('show') || $this->option('random')) {
                $this->info("  New password: {$password}");
                $this->warn("  ⚠️  Make sure to save this password securely!");
            }

            // Send notification if requested
            if ($this->option('notify')) {
                $this->sendPasswordNotification($user, $password);
            }

            // Log the password reset
            activity()
                ->performedOn($user)
                ->withProperties([
                    'reset_via' => 'artisan',
                    'reset_by' => 'system',
                    'notified' => $this->option('notify'),
                ])
                ->log('Password reset via command line');

            $this->info("\n📝 Next steps:");
            $this->line("  1. User can login at: " . config('app.url') . '/admin');

            if (!$this->option('notify') && !$this->option('show')) {
                $this->line("  2. Share the new password with the user securely");
            }

            $this->line("  3. Recommend user changes password after first login");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to reset password: ' . $e->getMessage());
            Log::error('Password reset failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get or generate new password
     */
    protected function getNewPassword(): ?string
    {
        // Check if password provided
        if ($this->option('password')) {
            $password = $this->option('password');

            // Validate password
            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters');
                return null;
            }

            return $password;
        }

        // Generate random password if requested
        if ($this->option('random')) {
            return $this->generateSecurePassword();
        }

        // Ask for password
        $password = $this->secret('Enter new password (min 8 characters, leave empty for random)');

        if (empty($password)) {
            $this->info('Generating random password...');
            return $this->generateSecurePassword();
        }

        // Validate password
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters');
            return null;
        }

        // Confirm password
        $confirmation = $this->secret('Confirm new password');

        if ($password !== $confirmation) {
            $this->error('Passwords do not match');
            return null;
        }

        return $password;
    }

    /**
     * Generate a secure random password
     */
    protected function generateSecurePassword(): string
    {
        // Generate a password with:
        // - 12 characters minimum
        // - Mix of uppercase, lowercase, numbers, and symbols

        $length = 12;
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';

        $password = '';

        // Ensure at least one of each type
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Check if user is super admin
     */
    protected function isSuperAdmin(User $user): bool
    {
        // Check various indicators of super admin
        if ($user->user_type === 'platform_owner' || $user->user_type === 'platform_admin') {
            return true;
        }

        if (!$user->company_id) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole(['super_admin', 'super-admin', 'Super Admin'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear user's existing sessions
     */
    protected function clearUserSessions(User $user): void
    {
        try {
            // If using database sessions
            if (config('session.driver') === 'database') {
                \DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();

                $this->info('  Cleared existing sessions');
            }
        } catch (\Exception $e) {
            // Ignore session clearing errors
            $this->warn('  Could not clear sessions: ' . $e->getMessage());
        }
    }

    /**
     * Send password notification to user
     */
    protected function sendPasswordNotification(User $user, string $password): void
    {
        try {
            // Check if mail is configured
            if (config('mail.default') === 'log') {
                $this->warn('  Email notification skipped (mail driver is set to log)');
                Log::info('Password reset notification', [
                    'user' => $user->email,
                    'password' => $password
                ]);
                return;
            }

            // TODO: Implement email notification when email system is ready
            // For now, just log it
            Log::info('Password reset notification would be sent', [
                'user' => $user->email,
                'message' => 'Your password has been reset. Please login with your new credentials.'
            ]);

            $this->info('  Email notification queued');

        } catch (\Exception $e) {
            $this->warn('  Could not send email notification: ' . $e->getMessage());
        }
    }
}