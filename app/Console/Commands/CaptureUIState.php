<?php

namespace App\Console\Commands;

use App\Jobs\CaptureScreenshotJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Spatie\Browsershot\Browsershot;

class CaptureUIState extends Command
{
    protected $signature = 'ui:capture 
                            {--route= : Specific route to capture}
                            {--all : Capture all main routes}
                            {--compare : Compare with last capture}';
    
    protected $description = 'Capture screenshots of UI states for testing/documentation';
    
    protected $routes = [
        'dashboard' => '/admin',
        'appointments' => '/admin/appointments',
        'customers' => '/admin/customers',
        'staff' => '/admin/staff',
        'calls' => '/admin/calls',
        'services' => '/admin/services',
    ];
    
    public function handle()
    {
        $this->info('🎬 Starting UI State Capture...');
        
        if ($this->option('route')) {
            $this->captureRoute($this->option('route'));
        } elseif ($this->option('all')) {
            foreach ($this->routes as $name => $route) {
                $this->captureRoute($route, $name);
            }
        }
        
        if ($this->option('compare')) {
            $this->compareWithLastCapture();
        }
        
        $this->info('✅ UI Capture completed!');
    }
    
    protected function captureRoute($route, $name = null)
    {
        $name = $name ?? str_replace('/', '_', $route);
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "ui_state_{$name}_{$timestamp}.png";
        
        $this->info("📸 Capturing: {$route}");
        
        // Build full URL
        $url = config('app.url') . $route;
        
        // Ensure screenshots directory exists
        $screenshotDir = storage_path('app/screenshots');
        if (!file_exists($screenshotDir)) {
            mkdir($screenshotDir, 0755, true);
        }
        
        $savePath = $screenshotDir . '/' . $filename;
        
        // Get authentication session (if needed)
        $options = [
            'width' => 1920,
            'height' => 1080,
            'fullPage' => true,
            'waitUntilNetworkIdle' => true,
        ];
        
        // Add authentication cookies if available
        if ($sessionCookie = $this->getAuthSessionCookie()) {
            $options['cookies'] = [$sessionCookie];
        }
        
        // Option 1: Queue the screenshot job (recommended for production)
        if (config('queue.default') !== 'sync') {
            CaptureScreenshotJob::dispatch($url, $savePath, $options);
            $this->info("📷 Screenshot job queued for: {$name}");
        } else {
            // Option 2: Take screenshot directly (for development)
            try {
                $screenshot = Browsershot::url($url)
                    ->windowSize($options['width'], $options['height'])
                    ->waitUntilNetworkIdle()
                    ->fullPage()
                    ->noSandbox(); // Required when running as root
                
                if (isset($options['cookies'])) {
                    foreach ($options['cookies'] as $cookie) {
                        $screenshot->useCookies($cookie);
                    }
                }
                
                $screenshot->save($savePath);
                $this->info("✅ Screenshot saved: {$filename}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to capture screenshot: " . $e->getMessage());
                return null;
            }
        }
        
        // Store capture metadata
        $metadata = [
            'route' => $route,
            'name' => $name,
            'timestamp' => $timestamp,
            'filename' => $filename,
            'path' => $savePath,
            'url' => $url,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'viewport' => '1920x1080',
            'captured_at' => Carbon::now()->toIso8601String(),
        ];
        
        Storage::put("screenshots/metadata/{$name}_latest.json", json_encode($metadata, JSON_PRETTY_PRINT));
        
        return $filename;
    }
    
    protected function compareWithLastCapture()
    {
        $this->info('🔍 Comparing with previous captures...');
        // TODO: Implement visual regression testing
    }
    
    /**
     * Get authentication session cookie for authenticated screenshots
     */
    protected function getAuthSessionCookie()
    {
        // Option 1: Use a dedicated service account
        if (config('ui_capture.service_account.email')) {
            // You would need to implement login logic here
            // This is a placeholder for the actual implementation
            return [
                'name' => config('session.cookie'),
                'value' => 'your-session-id-here',
                'domain' => parse_url(config('app.url'), PHP_URL_HOST),
                'path' => '/',
            ];
        }
        
        // Option 2: Use current CLI session if available
        // In production, you might want to create a dedicated admin token
        return null;
    }
}