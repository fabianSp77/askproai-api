<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\ApiCallLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LiveActivityFeedWidget extends FilterableWidget
{
    protected static string $view = 'filament.admin.widgets.live-activity-feed-widget';
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '10s';
    
    protected function getViewData(): array
    {
        $activities = $this->getRecentActivities();
        
        return [
            'activities' => $activities,
            'hasActivities' => $activities->isNotEmpty(),
        ];
    }
    
    protected function getRecentActivities(): Collection
    {
        $activities = collect();
        $now = Carbon::now();
        
        // Recent calls
        $recentCalls = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('created_at', '>=', $now->subMinutes(30))
            ->with(['branch', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                $type = 'call';
                $icon = 'heroicon-o-phone';
                $color = 'blue';
                
                if ($call->appointment_id) {
                    $message = "Anruf {$call->branch?->name} → Termin gebucht ✓";
                    $color = 'green';
                } else {
                    $duration = $call->duration_sec ?? 0;
                    $message = "Anruf {$call->branch?->name} ({$this->formatDuration($duration)}) → Kein Termin ✗";
                    $color = 'gray';
                }
                
                // Check for anomalies
                if (($call->duration_sec ?? 0) > 300) { // > 5 minutes
                    $type = 'anomaly';
                    $icon = 'heroicon-o-exclamation-triangle';
                    $color = 'red';
                    $duration = $call->duration_sec ?? 0;
                    $message = "{$call->branch?->name}: Anruf {$this->formatDuration($duration)} (Anomalie!)";
                }
                
                return [
                    'type' => $type,
                    'icon' => $icon,
                    'color' => $color,
                    'message' => $message,
                    'time' => $call->created_at->format('H:i'),
                    'timestamp' => $call->created_at,
                ];
            });
        
        // Recent appointments
        $recentAppointments = Appointment::query()
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->where('branches.company_id', $this->companyId)
            ->where('appointments.created_at', '>=', $now->subMinutes(30))
            ->with(['branch', 'customer', 'service'])
            ->select('appointments.*')
            ->orderBy('appointments.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                $customerName = $appointment->customer 
                    ? $appointment->customer->first_name . ' ' . substr($appointment->customer->last_name, 0, 1) . '.'
                    : 'Unbekannt';
                    
                return [
                    'type' => 'appointment',
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'green',
                    'message' => "{$appointment->starts_at->diffInDays(now())} neue Termine für {$appointment->starts_at->format('d.m.')} ({$customerName})",
                    'time' => $appointment->created_at->format('H:i'),
                    'timestamp' => $appointment->created_at,
                ];
            });
        
        // API issues
        $apiErrors = ApiCallLog::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('created_at', '>=', $now->subMinutes(15))
            ->whereNotIn('response_status', [200, 201, 204]) // Non-success status codes
            ->whereNotNull('response_status')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($log) {
                $service = str_contains($log->service, 'calcom') ? 'Cal.com' : 'Retell';
                $responseTime = $log->duration_ms ?? 0;
                
                return [
                    'type' => 'api_issue',
                    'icon' => 'heroicon-o-exclamation-circle',
                    'color' => 'yellow',
                    'message' => "{$service} API langsam ({$responseTime}ms response)",
                    'time' => $log->created_at->format('H:i'),
                    'timestamp' => $log->created_at,
                ];
            });
        
        // Combine and sort all activities
        $activities = $activities
            ->concat($recentCalls)
            ->concat($recentAppointments)
            ->concat($apiErrors)
            ->sortByDesc('timestamp')
            ->take(15);
        
        return $activities;
    }
    
    protected function formatDuration(?int $seconds): string
    {
        if (!$seconds || $seconds < 60) {
            return $seconds ? "{$seconds}s" : "0s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf("%d:%02d", $minutes, $remainingSeconds);
    }
}