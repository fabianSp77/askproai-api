<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The call instance.
     *
     * @var \App\Models\Call
     */
    public $call;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('company.' . $this->call->company_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->call->id,
            'retell_call_id' => $this->call->retell_call_id,
            'from_number' => $this->call->from_number,
            'to_number' => $this->call->to_number,
            'status' => $this->call->status,
            'duration' => $this->call->duration,
            'created_at' => $this->call->created_at->toIso8601String(),
            'customer_name' => $this->call->customer ? $this->call->customer->name : null,
        ];
    }
}