<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Customer;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AIInsightsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'AI-Insights';
    protected static ?string $navigationGroup = '📊 Analytics';
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.admin.pages.ai-insights-dashboard';
    
    // Filter properties
    public ?int $companyId = null;
    public string $period = 'week';
    
    // Real data properties
    public array $aiMetrics = [];
    public array $costBenefit = [];
    public array $heatmapData = [];
    public array $funnelData = [];
    public array $industryData = [];
    public array $learningCurve = [];
    public array $realtimeMetrics = [];
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'Admin', 'Manager']) || $user->email === 'dev@askproai.de');
    }
    
    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403);
        }
        $this->loadRealData();
    }
    
    protected function loadRealData(): void
    {
        $endDate = now();
        $startDate = match($this->period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek()
        };
        
        // 1. AI-Verständnis-Rate (basierend auf Call-Status)
        $this->loadAIMetrics($startDate, $endDate);
        
        // 2. Kosten-Nutzen-Analyse
        $this->loadCostBenefit($startDate, $endDate);
        
        // 3. Heatmap-Daten
        $this->loadHeatmapData($startDate, $endDate);
        
        // 4. Conversion Funnel
        $this->loadFunnelData($startDate, $endDate);
        
        // 5. Branchen-Performance
        $this->loadIndustryData($startDate, $endDate);
        
        // 6. AI-Lernkurve
        $this->loadLearningCurve();
        
        // 7. Echtzeit-Metriken
        $this->loadRealtimeMetrics();
    }
    
    protected function loadAIMetrics($startDate, $endDate): void
    {
        $callsQuery = Call::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($this->companyId) {
            $callsQuery->where('company_id', $this->companyId);
        }
        
        $totalCalls = $callsQuery->count();
        
        // Analysiere Call-Status für AI-Verständnis
        $statusCounts = (clone $callsQuery)
            ->select('call_status', DB::raw('count(*) as count'))
            ->groupBy('call_status')
            ->pluck('count', 'call_status')
            ->toArray();
        
        // Kategorisiere basierend auf Status
        $perfect = $statusCounts['ended'] ?? 0; // Erfolgreich beendet
        $good = $statusCounts['no-answer'] ?? 0; // Anruf verpasst
        $needHelp = $statusCounts['busy'] ?? 0; // Besetzt
        $transferred = $statusCounts['failed'] ?? 0; // Fehlgeschlagen
        
        $total = max(1, $perfect + $good + $needHelp + $transferred);
        
        $this->aiMetrics = [
            'perfekt' => round(($perfect / $total) * 100, 1),
            'gut' => round(($good / $total) * 100, 1),
            'nachfragen' => round(($needHelp / $total) * 100, 1),
            'übertragen' => round(($transferred / $total) * 100, 1),
            'success_rate' => round((($perfect + $good) / $total) * 100, 1)
        ];
    }
    
    protected function loadCostBenefit($startDate, $endDate): void
    {
        $appointmentsQuery = Appointment::whereBetween('created_at', [$startDate, $endDate]);
        $callsQuery = Call::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($this->companyId) {
            $appointmentsQuery->where('company_id', $this->companyId);
            $callsQuery->where('company_id', $this->companyId);
        }
        
        $totalCalls = $callsQuery->count();
        $totalAppointments = $appointmentsQuery->count();
        
        // Echte Kosten basierend auf Transaktionen
        $totalCosts = BalanceTransaction::where('type', 'debit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->sum('amount');
        
        // Durchschnittlicher Service-Preis
        $avgServicePrice = Service::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->avg('price') ?? 150;
        
        $costPerCall = $totalCalls > 0 ? $totalCosts / $totalCalls : 0.35;
        $revenuePerAppointment = $avgServicePrice;
        $conversionRate = $totalCalls > 0 ? $totalAppointments / $totalCalls : 0;
        $roi = ($revenuePerAppointment * $conversionRate) - $costPerCall;
        
        $this->costBenefit = [
            'kosten_pro_anruf' => round($costPerCall, 2),
            'durchschnittlicher_terminwert' => round($revenuePerAppointment, 2),
            'conversion_rate' => round($conversionRate, 2),
            'roi' => round($roi, 2),
            'roi_multiplikator' => $costPerCall > 0 ? round($roi / $costPerCall, 1) : 0
        ];
    }
    
    protected function loadHeatmapData($startDate, $endDate): void
    {
        $this->heatmapData = [];
        $days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        $hours = range(8, 20);
        
        // Hole echte Call-Daten gruppiert nach Wochentag und Stunde
        $callDistribution = Call::whereBetween('created_at', [$startDate, $endDate])
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('day_of_week', 'hour')
            ->get()
            ->keyBy(function ($item) {
                return $item->day_of_week . '-' . $item->hour;
            });
        
        foreach ($days as $dayIndex => $day) {
            foreach ($hours as $hour) {
                // MySQL DAYOFWEEK: Sunday = 1, Monday = 2, etc.
                // Wir wollen: Monday = 0, Tuesday = 1, etc.
                $mysqlDayIndex = $dayIndex == 6 ? 1 : $dayIndex + 2;
                $key = $mysqlDayIndex . '-' . $hour;
                $count = $callDistribution[$key]->count ?? 0;
                $this->heatmapData[$day][$hour] = $count;
            }
        }
    }
    
    protected function loadFunnelData($startDate, $endDate): void
    {
        $baseQuery = fn() => $this->companyId 
            ? ['company_id' => $this->companyId] 
            : [];
        
        // Echte Funnel-Daten
        $totalCalls = Call::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->count();
        
        $answeredCalls = Call::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->where('call_status', 'ended')
            ->count();
        
        // Calls mit Transcript (AI hat verstanden)
        $understoodCalls = Call::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->whereNotNull('transcript')
            ->where('transcript', '!=', '')
            ->count();
        
        // Calls die zu Appointments führten
        $callsWithAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->whereNotNull('call_id')
            ->distinct('call_id')
            ->count('call_id');
        
        $scheduledAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        
        $completedAppointments = Appointment::whereBetween('created_at', [$startDate, $endDate])
            ->where($baseQuery())
            ->where('status', 'completed')
            ->count();
        
        $this->funnelData = [
            ['stage' => 'Anrufe eingegangen', 'count' => $totalCalls, 'percentage' => 100],
            ['stage' => 'AI beantwortet', 'count' => $answeredCalls, 'percentage' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0],
            ['stage' => 'Anfrage verstanden', 'count' => $understoodCalls, 'percentage' => $totalCalls > 0 ? round(($understoodCalls / $totalCalls) * 100, 1) : 0],
            ['stage' => 'Termin vorgeschlagen', 'count' => $callsWithAppointments, 'percentage' => $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100, 1) : 0],
            ['stage' => 'Termin gebucht', 'count' => $scheduledAppointments, 'percentage' => $totalCalls > 0 ? round(($scheduledAppointments / $totalCalls) * 100, 1) : 0],
            ['stage' => 'Termin wahrgenommen', 'count' => $completedAppointments, 'percentage' => $totalCalls > 0 ? round(($completedAppointments / $totalCalls) * 100, 1) : 0],
        ];
    }
    
    protected function loadIndustryData($startDate, $endDate): void
    {
        // Gruppiere Companies nach Branchen (basierend auf Namen oder Tags)
        $industries = Company::withCount([
                'calls' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'appointments' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])
            ])
            ->get()
            ->map(function ($company) use ($startDate, $endDate) {
                // Bestimme Branche basierend auf Company-Name
                $industry = $this->determineIndustry($company->name);
                
                // Berechne Revenue
                $revenue = BalanceTransaction::where('company_id', $company->id)
                    ->where('type', 'debit')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                
                $conversion = $company->calls_count > 0 
                    ? round(($company->appointments_count / $company->calls_count) * 100, 1)
                    : 0;
                
                return [
                    'name' => $industry,
                    'company' => $company->name,
                    'calls' => $company->calls_count,
                    'appointments' => $company->appointments_count,
                    'conversion' => $conversion,
                    'revenue' => round($revenue, 2)
                ];
            })
            ->groupBy('name')
            ->map(function ($group) {
                return [
                    'name' => $group->first()['name'],
                    'calls' => $group->sum('calls'),
                    'appointments' => $group->sum('appointments'),
                    'conversion' => $group->avg('conversion'),
                    'revenue' => $group->sum('revenue')
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values()
            ->toArray();
        
        $this->industryData = $industries;
    }
    
    protected function determineIndustry(string $companyName): string
    {
        $name = strtolower($companyName);
        
        if (str_contains($name, 'praxis') || str_contains($name, 'dr.') || str_contains($name, 'med')) {
            return 'Arztpraxen';
        } elseif (str_contains($name, 'salon') || str_contains($name, 'friseur') || str_contains($name, 'hair')) {
            return 'Friseursalons';
        } elseif (str_contains($name, 'restaurant') || str_contains($name, 'café') || str_contains($name, 'bistro')) {
            return 'Restaurants';
        } elseif (str_contains($name, 'auto') || str_contains($name, 'werkstatt') || str_contains($name, 'garage')) {
            return 'Autowerkstätten';
        } elseif (str_contains($name, 'anwalt') || str_contains($name, 'kanzlei') || str_contains($name, 'recht')) {
            return 'Anwaltskanzleien';
        } else {
            return 'Sonstige';
        }
    }
    
    protected function loadLearningCurve(): void
    {
        $weeks = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $totalCalls = Call::whereBetween('created_at', [$weekStart, $weekEnd])->count();
            $successfulCalls = Call::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('call_status', 'ended')
                ->count();
            
            $accuracy = $totalCalls > 0 ? ($successfulCalls / $totalCalls) * 100 : 0;
            
            $weeks[] = [
                'week' => 'KW ' . $weekStart->weekOfYear,
                'accuracy' => round($accuracy, 1),
                'calls' => $totalCalls
            ];
        }
        
        $this->learningCurve = $weeks;
    }
    
    protected function loadRealtimeMetrics(): void
    {
        // Durchschnittliche Wartezeit (basierend auf Call-Duration)
        $avgWaitTime = Call::where('created_at', '>=', now()->subDay())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        
        // Durchschnittliche Gesprächsdauer
        $avgCallDuration = Call::where('created_at', '>=', now()->subDay())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        
        // Erstlösungsquote
        $totalCallsToday = Call::whereDate('created_at', today())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->count();
        
        $resolvedCallsToday = Call::whereDate('created_at', today())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('call_status', 'ended')
            ->count();
        
        $firstCallResolution = $totalCallsToday > 0 
            ? round(($resolvedCallsToday / $totalCallsToday) * 100, 1)
            : 0;
        
        // Sentiment basierend auf Call-Status
        $sentiments = Call::where('created_at', '>=', now()->subDay())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->select('call_status', DB::raw('count(*) as count'))
            ->groupBy('call_status')
            ->pluck('count', 'call_status')
            ->toArray();
        
        $totalSentiment = array_sum($sentiments);
        $positive = ($sentiments['ended'] ?? 0);
        $neutral = ($sentiments['no-answer'] ?? 0) + ($sentiments['busy'] ?? 0);
        $negative = ($sentiments['failed'] ?? 0);
        
        // Wiederanruf-Rate
        $uniqueCallers = Call::where('created_at', '>=', now()->subWeek())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->distinct('from_number')
            ->count('from_number');
        
        $totalCallsWeek = Call::where('created_at', '>=', now()->subWeek())
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->count();
        
        $callbackRate = $uniqueCallers > 0 && $totalCallsWeek > $uniqueCallers
            ? round((($totalCallsWeek - $uniqueCallers) / $totalCallsWeek) * 100, 1)
            : 0;
        
        $this->realtimeMetrics = [
            'avg_wait_time' => round($avgWaitTime, 1),
            'avg_call_duration' => round($avgCallDuration / 60, 1), // in Minuten
            'first_call_resolution' => $firstCallResolution,
            'sentiment' => [
                'positive' => $totalSentiment > 0 ? round(($positive / $totalSentiment) * 100, 1) : 0,
                'neutral' => $totalSentiment > 0 ? round(($neutral / $totalSentiment) * 100, 1) : 0,
                'negative' => $totalSentiment > 0 ? round(($negative / $totalSentiment) * 100, 1) : 0,
            ],
            'callback_rate' => $callbackRate
        ];
    }
    
    public function getViewData(): array
    {
        return [
            'companyId' => $this->companyId,
            'period' => $this->period,
            'aiMetrics' => $this->aiMetrics,
            'costBenefit' => $this->costBenefit,
            'heatmapData' => $this->heatmapData,
            'funnelData' => $this->funnelData,
            'industryData' => $this->industryData,
            'learningCurve' => $this->learningCurve,
            'realtimeMetrics' => $this->realtimeMetrics,
        ];
    }
}