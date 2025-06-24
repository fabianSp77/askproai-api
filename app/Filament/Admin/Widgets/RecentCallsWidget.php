<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class RecentCallsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.recent-calls';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    public array $recentCalls = [];
    public array $callStats = [];
    
    public function mount(): void
    {
        $this->loadRecentCalls();
    }
    
    public function loadRecentCalls(): void
    {
        // Get recent completed calls
        $this->recentCalls = Cache::remember('recent_calls_widget', 60, function () {
            return Call::where('created_at', '>=', Carbon::now()->subHours(24))
                ->whereNotIn('status', ['in_progress', 'active'])
                ->with(['branch', 'customer', 'appointment'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($call) {
                    // Calculate duration from various fields
                    $duration = 0;
                    
                    // Check different duration fields
                    if (isset($call->duration_sec) && $call->duration_sec > 0) {
                        $duration = $call->duration_sec;
                    } elseif (isset($call->duration_seconds) && $call->duration_seconds > 0) {
                        $duration = $call->duration_seconds;
                    } elseif (isset($call->duration) && $call->duration > 0) {
                        $duration = $call->duration;
                    } elseif ($call->started_at && $call->ended_at) {
                        $duration = Carbon::parse($call->started_at)->diffInSeconds(Carbon::parse($call->ended_at));
                    } elseif ($call->start_timestamp && $call->end_timestamp) {
                        $duration = Carbon::parse($call->start_timestamp)->diffInSeconds(Carbon::parse($call->end_timestamp));
                    }
                    
                    return [
                        'id' => $call->id,
                        'time' => Carbon::parse($call->created_at)->format('H:i'),
                        'phone' => $this->maskPhoneNumber($call->from_number ?? $call->caller ?? 'Unknown'),
                        'branch' => $call->branch?->name ?? 'Nicht zugeordnet',
                        'duration' => $this->formatDuration((int)$duration),
                        'status' => $this->getCallStatus($call),
                        'appointment_booked' => !is_null($call->appointment_id),
                        'sentiment' => $call->sentiment ?? 'neutral',
                    ];
                })
                ->toArray();
        });
        
        // Calculate stats
        $this->calculateCallStats();
    }
    
    private function calculateCallStats(): void
    {
        $stats = Cache::remember('call_stats_24h', 300, function () {
            $calls24h = Call::where('created_at', '>=', Carbon::now()->subHours(24));
            
            // Try different duration columns
            $avgDuration = 0;
            try {
                // First try duration_sec
                $avgDuration = $calls24h->clone()->avg('duration_sec') ?? 0;
            } catch (\Exception $e) {
                try {
                    // Then try duration_seconds
                    $avgDuration = $calls24h->clone()->avg('duration_seconds') ?? 0;
                } catch (\Exception $e2) {
                    // Calculate from timestamps
                    $callsWithDuration = $calls24h->clone()
                        ->whereNotNull('started_at')
                        ->whereNotNull('ended_at')
                        ->get();
                    
                    if ($callsWithDuration->count() > 0) {
                        $totalDuration = 0;
                        foreach ($callsWithDuration as $call) {
                            $totalDuration += Carbon::parse($call->started_at)->diffInSeconds(Carbon::parse($call->ended_at));
                        }
                        $avgDuration = $totalDuration / $callsWithDuration->count();
                    }
                }
            }
            
            return [
                'total_calls' => $calls24h->clone()->count(),
                'appointments_booked' => $calls24h->clone()->whereNotNull('appointment_id')->count(),
                'avg_duration' => $avgDuration,
                'missed_calls' => $calls24h->clone()->where('status', 'missed')->count(),
            ];
        });
        
        $this->callStats = [
            'total_calls' => $stats['total_calls'],
            'appointments_booked' => $stats['appointments_booked'],
            'conversion_rate' => $stats['total_calls'] > 0 
                ? round(($stats['appointments_booked'] / $stats['total_calls']) * 100, 1) 
                : 0,
            'avg_duration' => $this->formatDuration((int)$stats['avg_duration']),
            'missed_calls' => $stats['missed_calls'],
        ];
    }
    
    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 8) {
            return $phone;
        }
        
        return substr($phone, 0, 4) . '****' . substr($phone, -3);
    }
    
    private function formatDuration(int $seconds): string
    {
        if ($seconds === 0) {
            return '0:00';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
    
    private function getCallStatus($call): string
    {
        $status = $call->status ?? $call->call_status ?? 'completed';
        
        return match($status) {
            'completed' => 'Abgeschlossen',
            'missed' => 'Verpasst',
            'failed' => 'Fehlgeschlagen',
            'cancelled' => 'Abgebrochen',
            default => ucfirst($status)
        };
    }
    
    public function getPollingInterval(): ?string
    {
        return '30s';
    }
}