<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class PerformanceOptimizedDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Performance Dashboard';
    protected static ?int $navigationSort = 999;
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.admin.pages.performance-optimized-dashboard';
    
    protected static ?string $title = 'Performance Optimized Dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public function mount(): void
    {
        // Minimal data loading - no heavy queries
        $this->metrics = [
            'users' => \App\Models\User::count(),
            'appointments' => \App\Models\Appointment::whereDate('created_at', today())->count(),
            'calls' => \App\Models\Call::whereDate('created_at', today())->count(),
        ];
    }
    
    public $metrics = [];
    
    // Manual refresh method instead of auto-polling
    public function refresh(): void
    {
        $this->metrics = [
            'users' => \App\Models\User::count(),
            'appointments' => \App\Models\Appointment::whereDate('created_at', today())->count(),
            'calls' => \App\Models\Call::whereDate('created_at', today())->count(),
            'timestamp' => now()->format('H:i:s'),
        ];
        
        $this->dispatch('metrics-updated');
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        // Only show for authorized users in development/local environment
        return static::canAccess() && app()->environment(['local', 'development']);
    }
}