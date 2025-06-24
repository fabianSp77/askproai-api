<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class ScreenshotWithLogin extends Command
{
    protected $signature = 'screenshot:login 
                            {url : The URL to capture after login}
                            {--email= : Login email}
                            {--password= : Login password}';
    
    protected $description = 'Take screenshot of authenticated pages by logging in first';
    
    public function handle()
    {
        $targetUrl = $this->argument('url');
        $email = $this->option('email') ?? config('screenshot.default_email', 'admin@askproai.de');
        $password = $this->option('password') ?? config('screenshot.default_password', 'password');
        
        $this->info('🔐 Taking authenticated screenshot...');
        
        $loginUrl = config('app.url') . '/admin/login';
        $fullTargetUrl = config('app.url') . $targetUrl;
        
        $timestamp = now()->format('Ymd_His');
        $filename = "authenticated_" . str_replace('/', '_', $targetUrl) . "_{$timestamp}.png";
        
        $screenshotDir = storage_path('app/screenshots/authenticated');
        if (!file_exists($screenshotDir)) {
            mkdir($screenshotDir, 0755, true);
        }
        
        $savePath = $screenshotDir . '/' . $filename;
        
        try {
            Browsershot::url($loginUrl)
                ->noSandbox()
                ->waitUntilNetworkIdle()
                ->type('input[type="email"]', $email)
                ->type('input[type="password"]', $password)
                ->click('button[type="submit"]')
                ->waitForNavigation()
                ->goto($fullTargetUrl)
                ->waitUntilNetworkIdle()
                ->windowSize(1920, 1080)
                ->deviceScaleFactor(2)
                ->fullPage()
                ->save($savePath);
            
            $this->info('✅ Screenshot saved successfully!');
            $this->line('');
            $this->info('📍 Location:');
            $this->line($savePath);
            
            // Also save reference for easy access
            file_put_contents(
                storage_path('app/screenshots/authenticated/latest.txt'),
                $savePath
            );
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to capture screenshot: ' . $e->getMessage());
            Log::error('Screenshot with login failed', [
                'url' => $targetUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
}