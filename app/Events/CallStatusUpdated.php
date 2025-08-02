<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Call;

class CallStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $status;
    public $data;
    private $call;

    /**
     * Create a new event instance.
     */
    public function __construct($callId, $status, array $data = [])
    {
        $this->callId = $callId;
        $this->status = $status;
        $this->data = $data;
        $this->call = Call::withoutGlobalScopes()->find($callId);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        if ($this->call) {
            // Broadcast to company channel
            $channels[] = new PrivateChannel('company.' . $this->call->company_id);
            
            // Broadcast to branch channel if available
            if ($this->call->branch_id) {
                $channels[] = new PrivateChannel('branch.' . $this->call->branch_id);
            }
            
            // Broadcast to the calls channel
            $channels[] = new PrivateChannel('calls');
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'status' => $this->status,
            'company_id' => $this->call->company_id ?? null,
            'branch_id' => $this->call->branch_id ?? null,
            'updated_at' => now()->toIso8601String(),
            'data' => $this->data
        ];
    }
}