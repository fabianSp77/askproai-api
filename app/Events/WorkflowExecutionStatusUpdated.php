<?php

namespace App\Events;

use App\Models\WorkflowExecution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowExecutionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $execution;
    public $executionData;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkflowExecution $execution)
    {
        $this->execution = $execution;
        
        // Prepare data to broadcast
        $this->executionData = [
            'execution_id' => $execution->id,
            'workflow_id' => $execution->workflow_id,
            'status' => $execution->status,
            'current_command_index' => $execution->current_command_index,
            'total_commands' => $execution->workflow->commands()->count(),
            'output' => $execution->output,
            'error_message' => $execution->error_message,
            'started_at' => $execution->started_at,
            'completed_at' => $execution->completed_at,
            'duration_ms' => $execution->duration_ms,
            'current_command' => $execution->getCurrentCommand(),
        ];
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
            new PrivateChannel('workflow-execution.' . $this->execution->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'workflow.execution.status';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->executionData;
    }
}