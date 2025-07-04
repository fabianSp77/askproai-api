<?php

namespace App\Events;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchContextChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public ?Branch $branch;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, ?Branch $branch)
    {
        $this->user = $user;
        $this->branch = $branch;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'is_all_branches' => $this->branch === null,
        ];
    }
}