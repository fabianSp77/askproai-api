<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Billing Threshold Exceeded Event
 * 
 * Triggered when a company's usage exceeds defined billing thresholds
 */
class BillingThresholdExceeded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public Company $company;
    public string $period; // 'daily' or 'monthly'
    public float $currentAmount;
    public float $threshold;
    public float $percentageOver;
    
    /**
     * Create a new event instance.
     */
    public function __construct(
        Company $company,
        string $period,
        float $currentAmount,
        float $threshold
    ) {
        $this->company = $company;
        $this->period = $period;
        $this->currentAmount = $currentAmount;
        $this->threshold = $threshold;
        $this->percentageOver = (($currentAmount - $threshold) / $threshold) * 100;
    }
    
    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->company->id),
            new PrivateChannel('billing-alerts')
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'billing.threshold.exceeded';
    }
    
    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'period' => $this->period,
            'current_amount' => $this->currentAmount,
            'threshold' => $this->threshold,
            'percentage_over' => round($this->percentageOver, 2),
            'severity' => $this->getSeverity(),
            'timestamp' => now()->toIso8601String()
        ];
    }
    
    /**
     * Determine alert severity based on percentage over threshold
     */
    protected function getSeverity(): string
    {
        if ($this->percentageOver >= 100) {
            return 'critical'; // 200% or more of threshold
        } elseif ($this->percentageOver >= 50) {
            return 'high'; // 150% or more of threshold
        } elseif ($this->percentageOver >= 25) {
            return 'medium'; // 125% or more of threshold
        }
        
        return 'low'; // Just over threshold
    }
}