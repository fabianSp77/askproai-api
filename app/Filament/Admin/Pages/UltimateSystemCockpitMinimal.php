<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class UltimateSystemCockpitMinimal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationLabel = 'System Cockpit (Minimal)';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static string $view = 'filament.admin.pages.ultimate-system-cockpit-minimal';
    protected static ?int $navigationSort = 6;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - redundant
    }
    
    public array $systemMetrics = [];
    
    public function mount(): void
    {
        try {
            Log::info('UltimateSystemCockpitMinimal mounting...');
            
            $this->systemMetrics = [
                'overall_health' => 95,
                'active_calls' => 42,
                'queue_size' => 12,
                'error_rate' => 0.02,
                'response_time' => 125,
                'database_health' => 100,
                'uptime' => '15d 8h 32m',
            ];
            
            Log::info('UltimateSystemCockpitMinimal mounted successfully', $this->systemMetrics);
            
        } catch (\Exception $e) {
            Log::error('UltimateSystemCockpitMinimal mount failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}