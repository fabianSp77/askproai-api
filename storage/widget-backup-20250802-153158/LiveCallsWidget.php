<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class LiveCallsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.live-calls-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -100; // Show at top
    
    protected static ?string $pollingInterval = '5s'; // Slower polling when using Pusher
    
    public $activeCalls = [];
    public $realtimeEnabled = true;
    public $lastUpdate = null;
    public $displayLimit = 5;
    public $showAll = false;
    
    protected $listeners = [
        'call-created' => 'handleCallCreated',
        'call-updated' => 'handleCallUpdated',
        'call-completed' => 'handleCallCompleted',
        'pusher-connected' => 'handlePusherConnected',
        'pusher-disconnected' => 'handlePusherDisconnected',
    ];
    
    public function mount(): void
    {
        $this->loadActiveCalls();
    }
    
    public function loadActiveCalls(): void
    {
        // Get active calls (no end timestamp)
        // Filter out calls that are in_progress for more than 15 minutes
        $this->activeCalls = Call::query()
            ->whereNull('end_timestamp')
            ->where('created_at', '>', now()->subHours(2))
            ->where(function($query) {
                $query->where('call_status', '!=', 'in_progress')
                      ->orWhere('start_timestamp', '>', now()->subMinutes(15));
            })
            ->with(['customer', 'branch'])
            ->orderBy('start_timestamp', 'desc')
            ->get()
            ->map(function ($call) {
                // Calculate live duration
                $duration = $call->start_timestamp 
                    ? now()->diffInSeconds($call->start_timestamp) 
                    : 0;
                
                return [
                    'id' => $call->id,
                    'call_id' => $call->call_id,
                    'from_number' => $call->from_number,
                    'customer_name' => $call->customer?->name ?? 'Unbekannt',
                    'status' => $call->call_status ?? 'in_progress',
                    'duration' => $this->formatDuration($duration),
                    'duration_seconds' => $duration,
                    'agent_id' => $call->agent_id,
                    'start_time' => $call->start_timestamp?->format('H:i:s'),
                    'is_new' => $call->created_at->gt(now()->subSeconds(10))
                ];
            })
            ->toArray();
        
        $this->lastUpdate = now()->format('H:i:s');
        
        // Check for updates from cache
        $latestUpdate = Cache::get('latest_call_update');
        if ($latestUpdate) {
            $this->dispatch('call-updated', $latestUpdate);
        }
    }
    
    protected function formatDuration($seconds): string
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
    
    public function refreshCalls(): void
    {
        $this->loadActiveCalls();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Anrufe aktualisiert'
        ]);
    }
    
    public function syncNow(): void
    {
        // Trigger manual sync
        try {
            $company = auth()->user()->company ?? \App\Models\Company::first();
            \App\Jobs\FetchRetellCallsJob::dispatch($company)->onQueue('high');
            
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Synchronisation gestartet...'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Synchronisation fehlgeschlagen'
            ]);
        }
    }
    
    public function toggleRealtime(): void
    {
        $this->realtimeEnabled = !$this->realtimeEnabled;
        
        if ($this->realtimeEnabled) {
            $this->dispatch('enable-realtime');
        } else {
            $this->dispatch('disable-realtime');
        }
    }
    
    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
        
        if (!$this->showAll) {
            // Scroll back to top when collapsing
            $this->dispatch('scroll-to-top');
        }
    }
    
    public function getDisplayedCalls(): array
    {
        if ($this->showAll || count($this->activeCalls) <= $this->displayLimit) {
            return $this->activeCalls;
        }
        
        return array_slice($this->activeCalls, 0, $this->displayLimit);
    }
    
    public function getRemainingCallsCount(): int
    {
        return max(0, count($this->activeCalls) - $this->displayLimit);
    }
    
    public static function canView(): bool
    {
        return true;
    }
    
    // Pusher event handlers
    public function handleCallCreated($data)
    {
        $this->loadActiveCalls();
        
        // Flash effect for new call
        if (isset($data['call']['id'])) {
            foreach ($this->activeCalls as &$call) {
                if ($call['id'] == $data['call']['id']) {
                    $call['is_new'] = true;
                }
            }
        }
    }
    
    public function handleCallUpdated($data)
    {
        $this->loadActiveCalls();
    }
    
    public function handleCallCompleted($data)
    {
        $this->loadActiveCalls();
    }
    
    public function handlePusherConnected()
    {
        $this->realtimeEnabled = true;
        $this->dispatch('notify', [
            'title' => 'Echtzeit-Updates aktiviert',
            'type' => 'success',
        ]);
    }
    
    public function handlePusherDisconnected()
    {
        $this->dispatch('notify', [
            'title' => 'Echtzeit-Verbindung getrennt',
            'type' => 'warning',
        ]);
    }
}