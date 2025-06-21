<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class EventAnalyticsDashboard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Dashboard';
    protected static ?string $navigationLabel = 'Analytics Dashboard';
    protected static ?int $navigationSort = 35;
    protected static string $view = 'filament.admin.pages.event-analytics-dashboard';
    
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $companyId = null;
    public ?string $staffId = null;
    
    // Analytics Data
    public array $stats = [];
    public array $chartData = [];
    public array $heatmapData = [];
    public array $topPerformers = [];
    public array $eventTypeStats = [];
    
    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        
        // Auto-select company if only one
        $companies = Company::all();
        if ($companies->count() === 1) {
            $this->companyId = $companies->first()->id;
        }
        
        $this->loadAnalytics();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Select::make('companyId')
                    ->label('Unternehmen')
                    ->options(Company::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),
                    
                Select::make('staffId')
                    ->label('Mitarbeiter (optional)')
                    ->options(fn() => $this->companyId 
                        ? Staff::where('company_id', $this->companyId)->pluck('name', 'id')
                        : []
                    )
                    ->placeholder('Alle Mitarbeiter')
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),
                    
                DatePicker::make('dateFrom')
                    ->label('Von')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),
                    
                DatePicker::make('dateTo')
                    ->label('Bis')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),
            ])
            ->columns(4);
    }
    
    public function loadAnalytics(): void
    {
        if (!$this->companyId || !$this->dateFrom || !$this->dateTo) {
            return;
        }
        
        $this->calculateStats();
        $this->generateChartData();
        $this->generateHeatmap();
        $this->getTopPerformers();
        $this->getEventTypeStats();
    }
    
    /**
     * Berechne Haupt-Statistiken
     */
    protected function calculateStats(): void
    {
        $cacheKey = "analytics_stats_{$this->companyId}_{$this->staffId}_{$this->dateFrom}_{$this->dateTo}";
        
        $this->stats = Cache::remember($cacheKey, 300, function () {
            $query = Appointment::query()
                ->whereHas('staff', function (Builder $query) {
                    $query->where('company_id', $this->companyId);
                })
                ->whereBetween('starts_at', [$this->dateFrom, $this->dateTo]);
                
            if ($this->staffId) {
                $query->where('staff_id', $this->staffId);
            }
            
            // Gesamt-Termine
            $totalAppointments = $query->count();
            
            // Status-Verteilung (sicher mit Eloquent)
            $statusCounts = $query->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Umsatz (sicher mit Eloquent)
            $revenue = Appointment::query()
                ->join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
                ->whereHas('staff', function (Builder $query) {
                    $query->where('company_id', $this->companyId);
                })
                ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
                ->where('appointments.status', 'completed')
                ->when($this->staffId, fn($q) => $q->where('appointments.staff_id', $this->staffId))
                ->sum('calcom_event_types.price');
            
            // Durchschnittliche Auslastung
            $workingDays = Carbon::parse($this->dateFrom)->diffInWeekdays(Carbon::parse($this->dateTo));
            $staffCount = Staff::where('company_id', $this->companyId)
                ->where('active', true)
                ->when($this->staffId, fn($q) => $q->where('id', $this->staffId))
                ->count();
            $slotsPerDay = 8; // Konfigurierbar machen
            $theoreticalCapacity = $workingDays * $staffCount * $slotsPerDay;
            $utilization = $theoreticalCapacity > 0 ? round(($totalAppointments / $theoreticalCapacity) * 100, 1) : 0;
        
        // No-Show Rate
        $noShowRate = $totalAppointments > 0 
            ? round((($statusCounts['no_show'] ?? 0) / $totalAppointments) * 100, 1)
            : 0;
            
            // Durchschnittliche Buchungsvorlaufzeit (sicher)
            $avgLeadTime = Appointment::query()
                ->whereHas('staff', function (Builder $query) {
                    $query->where('company_id', $this->companyId);
                })
                ->whereBetween('starts_at', [$this->dateFrom, $this->dateTo])
                ->when($this->staffId, fn($q) => $q->where('staff_id', $this->staffId))
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, starts_at)) as avg_hours')
                ->value('avg_hours');
            
            return [
                'total_appointments' => $totalAppointments,
                'completed' => $statusCounts['completed'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
                'no_show' => $statusCounts['no_show'] ?? 0,
                'revenue' => $revenue,
                'utilization' => $utilization,
                'no_show_rate' => $noShowRate,
                'avg_lead_time_hours' => round($avgLeadTime ?? 0),
                'completion_rate' => $totalAppointments > 0 
                    ? round((($statusCounts['completed'] ?? 0) / $totalAppointments) * 100, 1)
                    : 0,
            ];
        });
    }
    
    /**
     * Generiere Chart-Daten
     */
    protected function generateChartData(): void
    {
        // Termine pro Tag
        $appointmentsPerDay = DB::table('appointments')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $this->companyId)
            ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
            ->when($this->staffId, fn($q) => $q->where('appointments.staff_id', $this->staffId))
            ->selectRaw('DATE(starts_at) as date, COUNT(*) as count, 
                         SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                         SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_show')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $this->chartData['appointments_timeline'] = [
            'labels' => $appointmentsPerDay->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d.m'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Abgeschlossen',
                    'data' => $appointmentsPerDay->pluck('completed')->toArray(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Abgesagt',
                    'data' => $appointmentsPerDay->pluck('cancelled')->toArray(),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'No-Show',
                    'data' => $appointmentsPerDay->pluck('no_show')->toArray(),
                    'backgroundColor' => '#ef4444',
                ],
            ],
        ];
        
        // Umsatz pro Woche
        $revenuePerWeek = DB::table('appointments')
            ->join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $this->companyId)
            ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
            ->where('appointments.status', 'completed')
            ->when($this->staffId, fn($q) => $q->where('appointments.staff_id', $this->staffId))
            ->selectRaw('YEARWEEK(starts_at) as week, SUM(calcom_event_types.price) as revenue')
            ->groupBy('week')
            ->orderBy('week')
            ->get();
            
        $this->chartData['revenue_timeline'] = [
            'labels' => $revenuePerWeek->map(fn($w) => 'KW ' . substr($w->week, -2))->toArray(),
            'datasets' => [
                [
                    'label' => 'Umsatz (€)',
                    'data' => $revenuePerWeek->pluck('revenue')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f680',
                ],
            ],
        ];
    }
    
    /**
     * Generiere Heatmap-Daten
     */
    protected function generateHeatmap(): void
    {
        $heatmapQuery = DB::table('appointments')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $this->companyId)
            ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
            ->when($this->staffId, fn($q) => $q->where('appointments.staff_id', $this->staffId))
            ->selectRaw('DAYOFWEEK(starts_at) - 1 as day_of_week, 
                         HOUR(starts_at) as hour, 
                         COUNT(*) as count')
            ->groupBy('day_of_week', 'hour')
            ->get();
            
        // Erstelle 7x24 Matrix
        $matrix = [];
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $count = $heatmapQuery
                    ->where('day_of_week', $day)
                    ->where('hour', $hour)
                    ->first()
                    ->count ?? 0;
                    
                $matrix[] = [
                    'x' => $hour,
                    'y' => $day,
                    'value' => $count,
                ];
            }
        }
        
        $this->heatmapData = $matrix;
    }
    
    /**
     * Hole Top-Performer
     */
    protected function getTopPerformers(): void
    {
        $this->topPerformers = DB::table('appointments')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $this->companyId)
            ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
            ->selectRaw('
                staff.id,
                staff.name,
                COUNT(*) as total_appointments,
                SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN appointments.status = "no_show" THEN 1 ELSE 0 END) as no_shows,
                AVG(CASE WHEN appointments.status = "completed" THEN 
                    TIMESTAMPDIFF(MINUTE, appointments.starts_at, appointments.ends_at) 
                ELSE NULL END) as avg_duration_minutes
            ')
            ->groupBy('staff.id', 'staff.name')
            ->orderByDesc('completed')
            ->limit(10)
            ->get()
            ->map(function ($staff) {
                $staff->completion_rate = $staff->total_appointments > 0
                    ? round(($staff->completed / $staff->total_appointments) * 100, 1)
                    : 0;
                $staff->no_show_rate = $staff->total_appointments > 0
                    ? round(($staff->no_shows / $staff->total_appointments) * 100, 1)
                    : 0;
                return $staff;
            })
            ->toArray();
    }
    
    /**
     * Event-Type Statistiken
     */
    protected function getEventTypeStats(): void
    {
        $this->eventTypeStats = DB::table('appointments')
            ->join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $this->companyId)
            ->whereBetween('appointments.starts_at', [$this->dateFrom, $this->dateTo])
            ->when($this->staffId, fn($q) => $q->where('appointments.staff_id', $this->staffId))
            ->selectRaw('
                calcom_event_types.id,
                calcom_event_types.name,
                calcom_event_types.duration_minutes,
                calcom_event_types.price,
                COUNT(*) as bookings,
                SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN appointments.status = "completed" THEN calcom_event_types.price ELSE 0 END) as revenue
            ')
            ->groupBy('calcom_event_types.id', 'calcom_event_types.name', 
                     'calcom_event_types.duration_minutes', 'calcom_event_types.price')
            ->orderByDesc('bookings')
            ->get()
            ->toArray();
    }
}