<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
        
        // Using Browsershot (spatie/browsershot) for headless Chrome
        $this->info("📸 Capturing: {$route}");
        
        // Store capture metadata
        $metadata = [
            'route' => $route,
            'name' => $name,
            'timestamp' => $timestamp,
            'filename' => $filename,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'viewport' => '1920x1080',
        ];
        
        Storage::put("screenshots/metadata/{$name}_latest.json", json_encode($metadata, JSON_PRETTY_PRINT));
        
        // TODO: Implement actual screenshot capture
        $this->warn("Note: Actual screenshot implementation requires spatie/browsershot package");
        
        return $filename;
    }
    
    protected function compareWithLastCapture()
    {
        $this->info('🔍 Comparing with previous captures...');
        // TODO: Implement visual regression testing
    }
}