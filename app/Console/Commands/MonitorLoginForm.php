<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorLoginForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:login-form {--alert : Send alert if form is broken}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor the login form to ensure input fields are rendering correctly';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Monitoring login form status...');
        
        $loginUrls = [
            'https://api.askproai.de/admin/login' => 'Main Login',
            'https://api.askproai.de/admin/login-fix' => 'Backup Login',
        ];
        
        $issues = [];
        
        foreach ($loginUrls as $url => $name) {
            $this->info("Checking $name: $url");
            
            try {
                $response = Http::timeout(10)->get($url);
                $html = $response->body();
                
                // Check for essential form elements
                $hasEmailInput = str_contains($html, 'type="email"') || str_contains($html, "type='email'");
                $hasPasswordInput = str_contains($html, 'type="password"') || str_contains($html, "type='password'");
                $hasForm = str_contains($html, '<form') || str_contains($html, 'wire:submit');
                
                // Check for error indicators (be more specific to avoid false positives)
                $hasError = str_contains($html, '<h1>500</h1>') || str_contains($html, '<h2>Server Error</h2>');
                $hasException = str_contains($html, 'Exception:') || str_contains($html, 'Fatal error:');
                
                if ($hasError || $hasException) {
                    $issues[] = "$name has server error (500)";
                    $this->error("  ❌ Server error detected");
                } elseif (!$hasForm) {
                    $issues[] = "$name has no form element";
                    $this->error("  ❌ No form element found");
                } elseif (!$hasEmailInput) {
                    $issues[] = "$name missing email input field";
                    $this->error("  ❌ Email input field missing");
                } elseif (!$hasPasswordInput) {
                    $issues[] = "$name missing password input field";
                    $this->error("  ❌ Password input field missing");
                } else {
                    $this->info("  ✅ Form is working correctly");
                }
                
                // Additional Livewire checks
                if (str_contains($html, 'wire:snapshot')) {
                    $this->info("  ✅ Livewire component detected");
                    
                    // Check if Livewire data is empty
                    if (str_contains($html, '"email":""') && str_contains($html, '"password":""')) {
                        $this->comment("  ⚠️  Livewire data initialized but may not be rendering");
                    }
                }
                
            } catch (\Exception $e) {
                $issues[] = "$name is unreachable: " . $e->getMessage();
                $this->error("  ❌ Could not reach login page: " . $e->getMessage());
            }
        }
        
        // Summary
        $this->info("\n📊 Summary:");
        
        if (empty($issues)) {
            $this->info("✅ All login forms are working correctly!");
            
            // Log success
            Log::info('Login form monitoring: All forms working correctly');
            
            return Command::SUCCESS;
        } else {
            $this->error("❌ Issues detected:");
            foreach ($issues as $issue) {
                $this->error("  • $issue");
            }
            
            // Log issues
            Log::error('Login form monitoring: Issues detected', [
                'issues' => $issues,
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Send alert if requested
            if ($this->option('alert')) {
                $this->sendAlert($issues);
            }
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Send alert about form issues
     *
     * @param array $issues
     * @return void
     */
    protected function sendAlert(array $issues)
    {
        $message = "🚨 Login Form Issues Detected:\n\n" . implode("\n", $issues);
        
        // Log critical alert
        Log::critical('Login form critical issues', [
            'issues' => $issues,
            'message' => $message,
        ]);
        
        // You can add email/SMS/Slack notifications here
        $this->warn("Alert logged to system (configure external alerts as needed)");
    }
}