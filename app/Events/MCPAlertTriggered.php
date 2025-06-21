<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MCPAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $rule;
    public string $service;
    public string $operation;

    /**
     * Create a new event instance.
     */
    public function __construct(array $rule, string $service, string $operation)
    {
        $this->rule = $rule;
        $this->service = $service;
        $this->operation = $operation;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [];
    }
}