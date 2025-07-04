<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;
use App\Models\Branch;
use App\Models\ApiCallLog;
use App\Models\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InsightsActionsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.insights-actions-widget-v2';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '30s';
    
    public ?int $companyId = null;
    
    public function mount(): void
    {
        $this->companyId = auth()->user()->company_id;
    }
    
    protected function getViewData(): array
    {
        $insights = $this->generateInsights();
        $quickActions = $this->getQuickActions();
        
        return [
            'insights' => $insights,
            'quickActions' => $quickActions,
            'hasUrgentIssues' => $insights->where('priority', 'urgent')->isNotEmpty(),
        ];
    }
    
    protected function generateInsights(): Collection
    {
        $insights = collect();
        $now = Carbon::now();
        
        // Get company phone numbers for filtering
        $phoneNumbers = [];
        if ($this->companyId) {
            $phoneNumbers = PhoneNumber::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->pluck('number')
                ->toArray();
        }
        
        // Check for high call duration branches
        $highDurationBranches = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when(!empty($phoneNumbers), fn($q) => $q->whereIn('to_number', $phoneNumbers))
            ->whereDate('created_at', today())
            ->whereNotNull('branch_id')
            ->with('branch')
            ->selectRaw('branch_id, AVG(duration_sec) as avg_duration, COUNT(*) as call_count')
            ->groupBy('branch_id')
            ->having('avg_duration', '>', 180) // More than 3 minutes average
            ->get();
        
        foreach ($highDurationBranches as $stat) {
            if ($stat->branch) {
                $avgDuration = gmdate('i:s', $stat->avg_duration);
                $insights->push([
                    'id' => 'high_duration_' . $stat->branch_id,
                    'priority' => 'urgent',
                    'type' => 'performance',
                    'icon' => 'heroicon-o-clock',
                    'color' => 'red',
                    'title' => 'Lange Gesprächsdauer',
                    'message' => "{$stat->branch->name}: Ø {$avgDuration} (70% über Durchschnitt)",
                    'action' => 'Prüfen',
                    'actionUrl' => route('filament.admin.resources.branches.view', $stat->branch_id),
                ]);
            }
        }
        
        // Check for API issues
        $apiErrors = ApiCallLog::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('created_at', '>=', $now->subMinutes(15))
            ->whereNotIn('response_status', [200, 201, 204]) // Non-success status codes
            ->whereNotNull('response_status')
            ->selectRaw('service, COUNT(*) as error_count')
            ->groupBy('service')
            ->having('error_count', '>', 3)
            ->get();
        
        foreach ($apiErrors as $error) {
            $service = str_contains($error->service, 'calcom') ? 'Cal.com' : 'Retell';
            $insights->push([
                'id' => 'api_errors_' . $error->service,
                'priority' => 'urgent',
                'type' => 'system',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'yellow',
                'title' => 'API Verbindungsprobleme',
                'message' => "{$service} Sync verzögert ({$error->error_count} Fehler)",
                'action' => 'Status prüfen',
                'actionUrl' => route('filament.admin.pages.api-health-monitor'),
            ]);
        }
        
        // Check for low conversion branches
        $branchStats = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when(!empty($phoneNumbers), fn($q) => $q->whereIn('to_number', $phoneNumbers))
            ->whereDate('created_at', today())
            ->whereNotNull('branch_id')
            ->with('branch')
            ->selectRaw('
                branch_id,
                COUNT(*) as total_calls,
                SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as converted_calls
            ')
            ->groupBy('branch_id')
            ->having('total_calls', '>=', 5)
            ->get();
        
        foreach ($branchStats as $stat) {
            $conversionRate = $stat->total_calls > 0 
                ? ($stat->converted_calls / $stat->total_calls) * 100
                : 0;
                
            if ($conversionRate < 25 && $stat->branch) {
                $insights->push([
                    'id' => 'low_conversion_' . $stat->branch_id,
                    'priority' => 'high',
                    'type' => 'conversion',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'orange',
                    'title' => 'Niedrige Konversionsrate',
                    'message' => "{$stat->branch->name}: Nur {$conversionRate}% Buchungsquote",
                    'action' => 'Analysieren',
                    'actionUrl' => route('filament.admin.resources.branches.view', ['record' => $stat->branch_id, 'tab' => 'analytics']),
                ]);
            }
        }
        
        // Check for no calls in last 30 minutes
        $lastCall = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when(!empty($phoneNumbers), fn($q) => $q->whereIn('to_number', $phoneNumbers))
            ->latest('created_at')
            ->first();
            
        if ($lastCall && $lastCall->created_at < $now->subMinutes(30)) {
            $insights->push([
                'id' => 'no_recent_calls',
                'priority' => 'medium',
                'type' => 'activity',
                'icon' => 'heroicon-o-phone-x-mark',
                'color' => 'gray',
                'title' => 'Keine aktuellen Anrufe',
                'message' => 'Letzter Anruf vor ' . $lastCall->created_at->diffForHumans(),
                'action' => 'Retell Status',
                'actionUrl' => route('filament.admin.pages.api-health-monitor'),
            ]);
        }
        
        return $insights->sortBy([
            ['priority', 'asc'],
            ['created_at', 'desc'],
        ])->values();
    }
    
    protected function getQuickActions(): array
    {
        return [
            [
                'label' => 'Anrufliste',
                'icon' => 'heroicon-o-phone',
                'url' => route('filament.admin.resources.calls.index'),
                'color' => 'primary',
            ],
            [
                'label' => 'Termine',
                'icon' => 'heroicon-o-calendar',
                'url' => route('filament.admin.resources.appointments.index'),
                'color' => 'success',
            ],
            [
                'label' => 'Filialen',
                'icon' => 'heroicon-o-building-office',
                'url' => route('filament.admin.resources.branches.index'),
                'color' => 'info',
            ],
            // [
            //     'label' => 'System Status',
            //     'icon' => 'heroicon-o-server-stack',
            //     'url' => route('filament.admin.pages.system-monitoring'),
            //     'color' => 'gray',
            // ],
        ];
    }
    
    protected function getPriorityLabel(string $priority): array
    {
        return match($priority) {
            'urgent' => ['label' => 'Sofort', 'color' => 'red'],
            'high' => ['label' => 'Hoch', 'color' => 'orange'],
            'medium' => ['label' => 'Mittel', 'color' => 'yellow'],
            'low' => ['label' => 'Niedrig', 'color' => 'gray'],
            default => ['label' => 'Info', 'color' => 'blue'],
        };
    }
}