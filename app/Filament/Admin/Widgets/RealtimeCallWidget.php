<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class RealtimeCallWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.realtime-call-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    // Enable real-time updates every 5 seconds
    protected static ?string $pollingInterval = '30s';
    
    public Collection $activeCalls;
    public Collection $recentCalls;
    public int $totalCallsToday = 0;
    public float $avgCallDuration = 0;
    public int $missedCalls = 0;
    
    public function mount(): void
    {
        $this->loadCallData();
    }
    
    public function loadCallData(): void
    {
        // Get active calls (calls within last 30 minutes without end time)
        $this->activeCalls = Call::where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->whereNull('ended_at')
            ->orWhere('status', 'in_progress')
            ->with(['customer', 'agent'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'customer_name' => $call->customer?->name ?? 'Unbekannt',
                    'phone' => $call->from_number ?? $call->phone_number ?? 'N/A',
                    'agent' => $call->agent?->name ?? 'AI Agent',
                    'duration' => $this->formatDuration($call->created_at),
                    'status' => $this->getCallStatus($call),
                    'status_color' => $this->getStatusColor($call),
                    'animated' => $call->status === 'in_progress',
                ];
            });
        
        // Get recent completed calls
        $this->recentCalls = Call::whereDate('created_at', Carbon::today())
            ->where(function ($query) {
                $query->whereNotNull('ended_at')
                    ->orWhere('status', 'completed');
            })
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'customer_name' => $call->customer?->name ?? 'Unbekannt',
                    'phone' => $call->from_number ?? $call->phone_number ?? 'N/A',
                    'duration' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '00:00',
                    'time' => $call->created_at->format('H:i'),
                    'status' => $call->status ?? 'completed',
                    'appointment_created' => $call->appointment_id || $call->appointmentViaCallId()->exists(),
                ];
            });
        
        // Calculate statistics
        $todaysCalls = Call::whereDate('created_at', Carbon::today());
        $this->totalCallsToday = $todaysCalls->count();
        
        $this->avgCallDuration = $todaysCalls->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        
        $this->missedCalls = $todaysCalls->where('status', 'missed')->count();
    }
    
    private function formatDuration(Carbon $startTime): string
    {
        $duration = $startTime->diffInSeconds(Carbon::now());
        return gmdate('i:s', $duration);
    }
    
    private function getCallStatus($call): string
    {
        if ($call->status === 'in_progress') {
            return 'Im Gespräch';
        } elseif ($call->status === 'completed') {
            return 'Beendet';
        } elseif ($call->status === 'missed') {
            return 'Verpasst';
        } elseif ($call->status === 'failed') {
            return 'Fehlgeschlagen';
        }
        
        // Fallback based on timestamps
        if (!$call->ended_at && $call->created_at->diffInMinutes(Carbon::now()) < 30) {
            return 'Aktiv';
        }
        
        return 'Beendet';
    }
    
    private function getStatusColor($call): string
    {
        $status = $call->status ?? '';
        
        return match($status) {
            'in_progress' => 'success',
            'completed' => 'info',
            'missed' => 'warning',
            'failed' => 'danger',
            default => 'gray',
        };
    }
    
    public function poll(): void
    {
        $this->loadCallData();
    }
}