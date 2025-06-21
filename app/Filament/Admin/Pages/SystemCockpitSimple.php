<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SystemCockpitSimple extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'System Overview';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static string $view = 'filament.admin.pages.system-cockpit-simple';
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - redundante Monitoring-Seite
    }
    
    public $callsToday = 0;
    public $appointmentsToday = 0;
    public $activeCompanies = 0;
    public $totalCustomers = 0;
    public $queueSize = 0;
    public $recentCalls = [];
    public $recentAppointments = [];
    
    public function mount(): void
    {
        try {
            // Basic metrics
            $this->callsToday = Call::whereDate('created_at', today())->count();
            $this->appointmentsToday = Appointment::whereDate('starts_at', today())->count();
            $this->activeCompanies = Company::where('billing_status', 'active')->count();
            $this->totalCustomers = Customer::count();
            $this->queueSize = DB::table('jobs')->count();
            
            // Recent calls
            $this->recentCalls = Call::with('customer')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($call) {
                    return [
                        'customer' => $call->customer->name ?? 'Unknown',
                        'duration' => $call->duration_sec ?? 0,
                        'time' => $call->created_at->diffForHumans()
                    ];
                })->toArray();
            
            // Recent appointments
            $this->recentAppointments = Appointment::with(['customer', 'staff'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($apt) {
                    return [
                        'customer' => $apt->customer->name ?? 'Unknown',
                        'staff' => $apt->staff->name ?? 'No staff',
                        'time' => $apt->starts_at ? $apt->starts_at->format('Y-m-d H:i') : 'No time',
                        'status' => $apt->status
                    ];
                })->toArray();
                
        } catch (\Exception $e) {
            // Fail silently, show zeros
        }
    }
}