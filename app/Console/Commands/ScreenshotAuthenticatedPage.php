<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class ScreenshotAuthenticatedPage extends Command
{
    protected $signature = 'screenshot:auth 
                            {url : The URL path to capture}
                            {--email= : Login email}
                            {--password= : Login password}';
    
    protected $description = 'Take screenshot of authenticated page using session cookie';
    
    public function handle()
    {
        $targetPath = $this->argument('url');
        $email = $this->option('email') ?? 'fabian@askproai.de';
        $password = $this->option('password') ?? 'Qwe421as1!1';
        
        $this->info('📸 Taking authenticated screenshot...');
        
        // Generate URLs
        $baseUrl = config('app.url');
        $loginUrl = $baseUrl . '/admin/login';
        $targetUrl = $baseUrl . $targetPath;
        
        // Prepare paths
        $timestamp = now()->format('Ymd_His');
        $filename = "auth_" . str_replace('/', '_', $targetPath) . "_{$timestamp}.png";
        
        $screenshotDir = storage_path('app/screenshots/authenticated');
        if (!file_exists($screenshotDir)) {
            mkdir($screenshotDir, 0755, true);
        }
        
        $savePath = $screenshotDir . '/' . $filename;
        
        try {
            // Create a script that will login and navigate
            $loginScript = "
                // Wait for login form
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Fill in credentials
                const emailInput = document.querySelector('input[type=\"email\"]');
                const passwordInput = document.querySelector('input[type=\"password\"]');
                const submitButton = document.querySelector('button[type=\"submit\"]');
                
                if (emailInput) emailInput.value = '{$email}';
                if (passwordInput) passwordInput.value = '{$password}';
                
                // Click submit
                if (submitButton) submitButton.click();
                
                // Wait for navigation
                await new Promise(resolve => setTimeout(resolve, 3000));
            ";
            
            // Take screenshot using login flow
            Browsershot::url($loginUrl)
                ->noSandbox()
                ->windowSize(1920, 1080)
                ->deviceScaleFactor(2)
                ->waitUntilNetworkIdle()
                ->evaluate($loginScript)
                ->goto($targetUrl)
                ->waitUntilNetworkIdle()
                ->fullPage()
                ->setOption('newHeadless', true)
                ->save($savePath);
            
            $this->info('✅ Screenshot saved successfully!');
            $this->line('');
            $this->info('📍 Location:');
            $this->line($savePath);
            
        } catch (\Exception $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}