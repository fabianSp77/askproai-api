<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $companyId;
    public $stats;
    public $branchId;

    /**
     * Create a new event instance.
     */
    public function __construct($companyId, array $stats, $branchId = null)
    {
        $this->companyId = $companyId;
        $this->stats = $stats;
        $this->branchId = $branchId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('company.' . $this->companyId . '.dashboard')
        ];
        
        if ($this->branchId) {
            $channels[] = new PrivateChannel('branch.' . $this->branchId . '.dashboard');
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'dashboard.stats.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'stats' => $this->stats,
            'updated_at' => now()->toIso8601String()
        ];
    }
}