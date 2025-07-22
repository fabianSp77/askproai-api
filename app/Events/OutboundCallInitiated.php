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

class OutboundCallInitiated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call, array $metadata = [])
    {
        $this->call = $call;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->call->company_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'retell_call_id' => $this->call->retell_call_id,
            'to_number' => $this->call->to_number,
            'status' => $this->call->status,
            'initiated_at' => $this->call->created_at->toISOString(),
            'metadata' => $this->metadata,
        ];
    }
}