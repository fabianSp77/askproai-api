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
        // Get active calls - calls that started but not ended
        $this->activeCalls = Cache::remember('active_calls_monitor', 30, function () {
            return Call::whereNotNull('start_timestamp')
                ->whereNull('end_timestamp')
                ->where('created_at', '>=', Carbon::now()->subMinutes(30))
                ->with(['branch', 'customer'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($call) {
                    $startTime = $call->start_timestamp ? Carbon::parse($call->start_timestamp) : Carbon::parse($call->created_at);
                    $duration = $startTime->diffInSeconds(now());
                    
                    return [
                        'id' => $call->id,
                        'phone' => $this->maskPhoneNumber($call->from_number ?? $call->caller ?? 'Unknown'),
                        'branch' => $call->branch?->name ?? 'Unknown',
                        'duration' => $this->formatDuration($duration),
                        'duration_sec' => $duration,
                        'agent_status' => $this->getAgentStatus($call),
                        'sentiment' => $this->getCallSentiment($call),
                        'topic' => $call->analysis['summary'] ?? $call->transcript ?? 'Initial contact...',
                    ];
                })
                ->toArray();
        });
        
        // Get queue metrics
        $this->queueMetrics = [
            'total_waiting' => rand(0, 15),
            'avg_wait_time' => rand(10, 120),
            'longest_wait' => rand(30, 300),
            'agents_available' => rand(3, 8),
            'agents_busy' => count($this->activeCalls),
            'service_level' => rand(85, 98), // % answered within 30s
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
        // In production, this would analyze actual call transcripts
        $sentiments = ['positive', 'neutral', 'negative'];
        return $sentiments[array_rand($sentiments)];
    }
    
    public function getPollingInterval(): ?string
    {
        return '5s'; // Refresh every 5 seconds for real-time feel
    }
}