<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * High Volume Usage Detected Event
 * 
 * Triggered when suspicious or unusually high usage patterns are detected
 */
class HighVolumeUsageDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public Company $company;
    public int $callCount;
    public string $timeWindow;
    public array $metadata;
    
    /**
     * Create a new event instance.
     */
    public function __construct(
        Company $company,
        int $callCount,
        string $timeWindow = '5 minutes',
        array $metadata = []
    ) {
        $this->company = $company;
        $this->callCount = $callCount;
        $this->timeWindow = $timeWindow;
        $this->metadata = $metadata;
    }
    
    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->company->id),
            new PrivateChannel('security-alerts')
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'usage.high.volume.detected';
    }
    
    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'call_count' => $this->callCount,
            'time_window' => $this->timeWindow,
            'severity' => $this->getSeverity(),
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String()
        ];
    }
    
    /**
     * Determine alert severity based on call count
     */
    protected function getSeverity(): string
    {
        if ($this->callCount >= 50) {
            return 'critical';
        } elseif ($this->callCount >= 25) {
            return 'high';
        } elseif ($this->callCount >= 15) {
            return 'medium';
        }
        
        return 'low';
    }
}