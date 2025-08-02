<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class OptimizedDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $title = 'Optimized Dashboard';
    protected static ?string $navigationLabel = 'Dashboard (Optimized)';
    protected static ?int $navigationSort = 998;
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.admin.pages.optimized-dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    // Public properties for Livewire
    public array $stats = [];
    public string $lastRefreshed = '';
    
    public function mount(): void
    {
        $this->loadStats();
    }
    
    protected function loadStats(): void
    {
        try {
            $this->stats = [
                'total_users' => \App\Models\User::count(),
                'total_companies' => \App\Models\Company::count(),
                'total_branches' => \App\Models\Branch::count(),
                'today_appointments' => \App\Models\Appointment::whereDate('created_at', today())->count(),
                'today_calls' => \App\Models\Call::whereDate('created_at', today())->count(),
                'pending_appointments' => \App\Models\Appointment::where('status', 'scheduled')
                    ->where('starts_at', '>=', today()->startOfDay())
                    ->count(),
            ];
            
            $this->lastRefreshed = now()->format('H:i:s');
        } catch (\Exception $e) {
            \Log::error('OptimizedDashboard error: ' . $e->getMessage());
            
            $this->stats = [
                'total_users' => 0,
                'total_companies' => 0,
                'total_branches' => 0,
                'today_appointments' => 0,
                'today_calls' => 0,
                'pending_appointments' => 0,
            ];
            
            $this->lastRefreshed = now()->format('H:i:s') . ' (Error)';
        }
    }
}