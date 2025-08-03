<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class LiveCallMonitor extends Widget
{
    protected static string $view = 'filament.admin.widgets.live-call-monitor';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    public array $activeCalls = [];
    public array $queueMetrics = [];
    public bool $isExpanded = false;
    
    public function mount(): void
    {
        $this->loadCallData();
    }
    
    #[On('refresh-calls')]
    public function loadCallData(): void
    {
        // Get active calls - using different status fields
        $this->activeCalls = Cache::remember('active_calls_monitor', 10, function () {
            return Call::where(function($query) {
                    // Check various status fields
                    $query->where('status', 'in_progress')
                          ->orWhere('call_status', 'in_progress')
                          ->orWhere(function($q) {
                              $q->whereNotNull('started_at')
                                ->whereNull('ended_at');
                          })
                          ->orWhere(function($q) {
                              $q->whereNotNull('start_timestamp')
                                ->whereNull('end_timestamp');
                          });
                })
                ->where('created_at', '>=', Carbon::now()->subHours(1))
                ->with(['branch', 'customer'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($call) {
                    // Get start time from various fields
                    $startTime = null;
                    if ($call->started_at) {
                        $startTime = Carbon::parse($call->started_at);
                    } elseif ($call->start_timestamp) {
                        $startTime = Carbon::parse($call->start_timestamp);
                    } else {
                        $startTime = Carbon::parse($call->created_at);
                    }
                    
                    $duration = $startTime->diffInSeconds(now());
                    
                    // Get phone number from various fields
                    $phone = $call->from_number 
                        ?? $call->caller 
                        ?? $call->customer?->phone 
                        ?? 'Unknown';
                    
                    return [
                        'id' => $call->id,
                        'retell_call_id' => $call->retell_call_id ?? $call->call_id ?? null,
                        'phone' => $this->maskPhoneNumber($phone),
                        'branch' => $call->branch?->name ?? 'Nicht zugeordnet',
                        'duration' => $this->formatDuration($duration),
                        'duration_sec' => $duration,
                        'agent_status' => $this->getAgentStatus($call),
                        'sentiment' => $this->getCallSentiment($call),
                        'topic' => $this->getCallTopic($call),
                        'status' => $call->status ?? $call->call_status ?? 'active',
                    ];
                })
                ->toArray();
        });
        
        // Get real queue metrics
        $this->queueMetrics = [
            'total_waiting' => 0, // No queue system implemented yet
            'avg_wait_time' => 0,
            'longest_wait' => 0,
            'agents_available' => $this->getAvailableAgents(),
            'agents_busy' => count($this->activeCalls),
            'service_level' => $this->calculateServiceLevel(),
        ];
    }
    
    public function toggleExpanded(): void
    {
        $this->isExpanded = !$this->isExpanded;
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
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
    
    private function getAgentStatus($call): string
    {
        // Simulate agent status based on call duration
        $startTime = $call->start_timestamp ? Carbon::parse($call->start_timestamp) : Carbon::parse($call->created_at);
        $duration = $startTime->diffInSeconds(now());
        
        if ($duration < 30) {
            return 'greeting';
        } elseif ($duration < 120) {
            return 'qualifying';
        } elseif ($duration < 300) {
            return 'booking';
        } else {
            return 'closing';
        }
    }
    
    private function getCallSentiment($call): string
    {
        // Check if we have actual sentiment from analysis
        if (isset($call->sentiment)) {
            return $call->sentiment;
        }
        
        if (isset($call->call_analysis['sentiment'])) {
            return $call->call_analysis['sentiment'];
        }
        
        // Default to neutral
        return 'neutral';
    }
    
    private function getCallTopic($call): string
    {
        // Try to get summary from various fields
        if (isset($call->call_analysis['summary'])) {
            return substr($call->call_analysis['summary'], 0, 100) . '...';
        }
        
        if (!empty($call->transcript)) {
            return substr($call->transcript, 0, 100) . '...';
        }
        
        if (!empty($call->summary)) {
            return substr($call->summary, 0, 100) . '...';
        }
        
        // Default message based on duration
        $startTime = $call->started_at ? Carbon::parse($call->started_at) : Carbon::parse($call->created_at);
        $duration = $startTime->diffInSeconds(now());
        
        if ($duration < 30) {
            return 'Begrüßung und Identifikation...';
        } elseif ($duration < 120) {
            return 'Anfrage wird bearbeitet...';
        } else {
            return 'Gespräch läuft...';
        }
    }
    
    private function getAvailableAgents(): int
    {
        // Count active Retell agents/phone numbers
        try {
            return \App\Models\PhoneNumber::where('is_active', true)
                ->whereNotNull('retell_agent_id')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function calculateServiceLevel(): float
    {
        // Calculate % of calls answered within target time (e.g., 30 seconds)
        try {
            $recentCalls = Call::where('created_at', '>=', Carbon::now()->subHours(24))
                ->whereNotNull('started_at')
                ->count();
                
            if ($recentCalls === 0) {
                return 100.0;
            }
            
            $answeredQuickly = Call::where('created_at', '>=', Carbon::now()->subHours(24))
                ->whereNotNull('started_at')
                ->whereRaw('TIMESTAMPDIFF(SECOND, created_at, started_at) <= 30')
                ->count();
                
            return round(($answeredQuickly / $recentCalls) * 100, 1);
        } catch (\Exception $e) {
            return 95.0; // Default value
        }
    }
    
    public function getPollingInterval(): ?string
    {
        return '5s'; // Refresh every 5 seconds for real-time feel
    }
}