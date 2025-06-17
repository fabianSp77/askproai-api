<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class SystemHealthMonitorDebug extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Debug-Version
    }
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'System Health (Debug)';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.system-health-monitor-debug';
    protected static ?int $navigationSort = 4;
    
    public string $debugInfo = '';
    public array $systemData = [];
    
    public function mount(): void
    {
        try {
            Log::info('SystemHealthMonitorDebug mount() started');
            
            // Test basic functionality
            $this->systemData = [
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
                'time' => now()->toDateTimeString(),
            ];
            
            $this->debugInfo = "System basic check passed";
            
            Log::info('SystemHealthMonitorDebug mount() completed', $this->systemData);
            
        } catch (\Exception $e) {
            Log::error('SystemHealthMonitorDebug mount() failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->debugInfo = "Error: " . $e->getMessage();
            $this->systemData = ['error' => true];
        }
    }
}