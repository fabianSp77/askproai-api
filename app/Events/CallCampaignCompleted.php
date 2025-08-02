<?php

namespace App\Events;

use App\Models\RetellAICallCampaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallCampaignCompleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public RetellAICallCampaign $campaign;

    public array $results;

    /**
     * Create a new event instance.
     */
    public function __construct(RetellAICallCampaign $campaign, array $results = [])
    {
        $this->campaign = $campaign;
        $this->results = $results;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->campaign->company_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'status' => $this->campaign->status,
            'total_targets' => $this->campaign->total_targets,
            'calls_completed' => $this->campaign->calls_completed,
            'calls_failed' => $this->campaign->calls_failed,
            'success_rate' => $this->campaign->success_rate,
            'completed_at' => $this->campaign->completed_at->toISOString(),
            'results' => $this->results,
        ];
    }
}
