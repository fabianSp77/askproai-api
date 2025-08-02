<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class SimpleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?string $navigationGroup = 'Dashboard';
    
    protected static ?string $title = 'Dashboard';

    protected static string $view = 'filament.admin.pages.simple-dashboard';
    
    protected static ?int $navigationSort = -2;
    
    protected static ?string $slug = 'dashboard';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getTitle(): string
    {
        return __('admin.dashboards.simple');
    }

    public function getHeading(): string
    {
        return __('admin.dashboards.simple');
    }
    
    protected function getViewData(): array
    {
        $companyId = auth()->user()->company_id;
        
        // Get today's stats
        $todaysCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();
            
        $todaysAppointments = Appointment::where('company_id', $companyId)
            ->whereDate('date', today())
            ->count();
            
        $totalCustomers = Customer::where('company_id', $companyId)->count();
        
        $activeBranches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();
            
        // Quick Stats
        $stats = [
            [
                'label' => __('admin.widgets.call_stats'),
                'value' => $todaysCalls,
                'icon' => 'heroicon-o-phone',
                'color' => 'primary',
                'change' => '+12%',
            ],
            [
                'label' => __('admin.resources.appointments'),
                'value' => $todaysAppointments,
                'icon' => 'heroicon-o-calendar',
                'color' => 'success',
                'change' => '+5%',
            ],
            [
                'label' => __('admin.resources.customers'),
                'value' => $totalCustomers,
                'icon' => 'heroicon-o-users',
                'color' => 'warning',
                'change' => '+8%',
            ],
            [
                'label' => __('admin.resources.branches'),
                'value' => $activeBranches,
                'icon' => 'heroicon-o-building-office',
                'color' => 'info',
                'change' => '0%',
            ],
        ];
        
        // Quick Links with proper Filament routes
        $quickLinks = [
            [
                'label' => __('admin.quick_actions.calls'),
                'icon' => 'heroicon-o-phone',
                'url' => route('filament.admin.resources.calls.index'),
                'color' => 'primary',
                'description' => 'Alle Anrufe anzeigen und verwalten',
            ],
            [
                'label' => __('admin.quick_actions.appointments'),
                'icon' => 'heroicon-o-calendar',
                'url' => route('filament.admin.resources.appointments.index'),
                'color' => 'success',
                'description' => 'Termine anzeigen und bearbeiten',
            ],
            [
                'label' => __('admin.quick_actions.customers'),
                'icon' => 'heroicon-o-users',
                'url' => route('filament.admin.resources.customers.index'),
                'color' => 'warning',
                'description' => 'Kundendaten verwalten',
            ],
            [
                'label' => __('admin.resources.branches'),
                'icon' => 'heroicon-o-building-office',
                'url' => route('filament.admin.resources.branches.index'),
                'color' => 'info',
                'description' => 'Filialen und Standorte verwalten',
            ],
            [
                'label' => __('admin.dashboards.ai_call_center'),
                'icon' => 'heroicon-o-cpu-chip',
                'url' => route('filament.admin.pages.a-i-call-center'),
                'color' => 'purple',
                'description' => 'AI-gesteuerte Anrufverwaltung',
            ],
            [
                'label' => __('admin.dashboards.system_monitoring'),
                'icon' => 'heroicon-o-server-stack',
                'url' => route('filament.admin.pages.system-monitoring-dashboard'),
                'color' => 'gray',
                'description' => 'Systemstatus und Monitoring',
            ],
        ];
        
        // Recent activity
        $recentCalls = Call::where('company_id', $companyId)
            ->with(['customer', 'branch'])
            ->latest()
            ->limit(5)
            ->get();
            
        $recentAppointments = Appointment::where('company_id', $companyId)
            ->with(['customer', 'branch', 'service'])
            ->latest()
            ->limit(5)
            ->get();
        
        return [
            'stats' => $stats,
            'quickLinks' => $quickLinks,
            'recentCalls' => $recentCalls,
            'recentAppointments' => $recentAppointments,
        ];
    }
}