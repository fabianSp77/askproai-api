<?php

namespace App\Console\Commands;

use App\Services\ScreenshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledScreenshots extends Command
{
    protected $signature = 'screenshots:scheduled';
    
    protected $description = 'Take scheduled screenshots for monitoring and documentation';
    
    protected ScreenshotService $screenshotService;
    
    /**
     * Critical routes to monitor
     */
    protected array $criticalRoutes = [
        'dashboard' => '/admin',
        'appointments' => '/admin/appointments',
        'customers' => '/admin/customers',
        'company_portal' => '/admin/company-integration-portal',
    ];
    
    public function __construct(ScreenshotService $screenshotService)
    {
        parent::__construct();
        $this->screenshotService = $screenshotService;
    }
    
    public function handle()
    {
        $this->info('📸 Running scheduled screenshots...');
        
        $results = [];
        
        foreach ($this->criticalRoutes as $name => $route) {
            try {
                $path = $this->screenshotService->capture($route, [
                    'name' => "scheduled_{$name}",
                    'sync' => true, // Execute synchronously for scheduled tasks
                ]);
                
                $results[$name] = [
                    'status' => 'success',
                    'path' => $path,
                ];
                
                $this->info("✅ Captured: {$name}");
                
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                
                $this->error("❌ Failed: {$name} - " . $e->getMessage());
                
                Log::error('Scheduled screenshot failed', [
                    'route' => $route,
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Store results summary
        $summary = [
            'timestamp' => now()->toIso8601String(),
            'total' => count($this->criticalRoutes),
            'successful' => collect($results)->where('status', 'success')->count(),
            'failed' => collect($results)->where('status', 'failed')->count(),
            'results' => $results,
        ];
        
        file_put_contents(
            storage_path('app/screenshots/scheduled_summary.json'),
            json_encode($summary, JSON_PRETTY_PRINT)
        );
        
        // Clean up old screenshots (keep last 7 days)
        $deleted = $this->screenshotService->cleanup(7);
        if ($deleted > 0) {
            $this->info("🧹 Cleaned up {$deleted} old screenshots");
        }
        
        $this->info('✅ Scheduled screenshots completed!');
        
        return 0;
    }
}