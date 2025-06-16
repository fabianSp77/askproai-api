<?php

namespace App\Events;

use App\Models\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerMerged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Customer $primary;
    public Customer $duplicate;

    /**
     * Create a new event instance.
     */
    public function __construct(Customer $primary, Customer $duplicate)
    {
        $this->primary = $primary;
        $this->duplicate = $duplicate;
    }
}