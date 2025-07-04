<?php

namespace App\Events;

use App\Models\CommandExecution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandExecutionStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CommandExecution $execution;

    /**
     * Create a new event instance.
     */
    public function __construct(CommandExecution $execution)
    {
        $this->execution = $execution->load(['commandTemplate', 'user']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->execution->user_id),
            new PrivateChannel('execution.' . $this->execution->id),
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
            'execution_id' => $this->execution->id,
            'command_id' => $this->execution->command_template_id,
            'command_title' => $this->execution->commandTemplate->title,
            'status' => $this->execution->status,
            'started_at' => $this->execution->started_at,
            'completed_at' => $this->execution->completed_at,
            'execution_time_ms' => $this->execution->execution_time_ms,
            'output' => $this->execution->output,
            'error_message' => $this->execution->error_message,
            'progress' => $this->getProgress(),
        ];
    }

    /**
     * Get progress percentage
     */
    protected function getProgress(): int
    {
        switch ($this->execution->status) {
            case CommandExecution::STATUS_PENDING:
                return 0;
            case CommandExecution::STATUS_RUNNING:
                return 50;
            case CommandExecution::STATUS_SUCCESS:
            case CommandExecution::STATUS_FAILED:
            case CommandExecution::STATUS_CANCELLED:
                return 100;
            default:
                return 0;
        }
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'command.execution.status';
    }
}