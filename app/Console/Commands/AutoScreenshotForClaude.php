<?php

namespace App\Console\Commands;

use App\Jobs\CaptureScreenshotJob;
use App\Services\ScreenshotAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AutoScreenshotForClaude extends Command
{
    protected $signature = 'claude:screenshot 
                            {route : The route to capture}
                            {--wait : Wait for job completion}
                            {--auth : Include authentication}';
    
    protected $description = 'Automatically capture screenshot and provide path for Claude to analyze';
    
    public function handle()
    {
        $route = $this->argument('route');
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "claude_auto_" . str_replace('/', '_', $route) . "_{$timestamp}.png";
        
        $this->info("🤖 Auto-capturing screenshot for Claude...");
        
        // Build URL
        $url = config('app.url') . $route;
        
        // Screenshot path
        $screenshotDir = storage_path('app/screenshots/claude');
        if (!file_exists($screenshotDir)) {
            mkdir($screenshotDir, 0755, true);
        }
        
        $savePath = $screenshotDir . '/' . $filename;
        
        // Options
        $options = [
            'width' => 1920,
            'height' => 1080,
            'fullPage' => true,
            'waitUntilNetworkIdle' => true,
            'deviceScaleFactor' => 2,
        ];
        
        // Add authentication if requested
        if ($this->option('auth')) {
            $authService = app(ScreenshotAuthService::class);
            $authCookies = $authService->getAuthCookies();
            
            if (!empty($authCookies)) {
                $options['cookies'] = $authCookies;
                $this->info("🔐 Authentication added");
            } else {
                $this->warn("⚠️  No authentication credentials available");
            }
        }
        
        // Dispatch job
        $job = new CaptureScreenshotJob($url, $savePath, $options);
        
        if ($this->option('wait') || config('queue.default') === 'sync') {
            // Execute synchronously
            try {
                $job->handle();
                $this->info("✅ Screenshot captured successfully!");
                $this->line("");
                $this->info("📍 Screenshot location:");
                $this->line($savePath);
                $this->line("");
                $this->comment("You can now use the Read tool to view this screenshot");
                
                // Also save a reference in a known location
                Storage::put('screenshots/claude/latest.txt', $savePath);
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to capture screenshot: " . $e->getMessage());
                return 1;
            }
        } else {
            // Queue the job
            dispatch($job);
            $this->info("📷 Screenshot job queued");
            $this->line("Expected location: " . $savePath);
            $this->comment("Run with --wait flag to wait for completion");
        }
        
        return 0;
    }
}