<?php

namespace App\Events;

use App\Models\Call;
use App\Models\Customer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerLinkedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;
    public Customer $customer;
    public string $method;
    public float $confidence;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call, Customer $customer, string $method, float $confidence)
    {
        $this->call = $call;
        $this->customer = $customer;
        $this->method = $method;
        $this->confidence = $confidence;
    }
}
