<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call)
    {
        $this->call = $call->load(['customer', 'appointment']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->call->company_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call' => [
                'id' => $this->call->id,
                'status' => $this->call->status,
                'duration_seconds' => $this->call->duration_seconds,
                'from_number' => $this->call->from_number,
                'customer_name' => $this->call->customer?->name,
                'appointment_created' => !is_null($this->call->appointment_id),
                'created_at' => $this->call->created_at->toIso8601String(),
            ],
        ];
    }
}