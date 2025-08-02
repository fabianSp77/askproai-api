<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerEventWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public string $eventName,
        public array $payload,
        public ?int $companyId = null
    ) {}

    public function handle(): void
    {
        $subscriptions = DB::table('event_subscriptions')
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('company_id')
                      ->orWhere('company_id', $this->companyId);
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $eventNames = json_decode($subscription->event_names, true);
            $filters = json_decode($subscription->filters, true) ?? [];

            // Check if this subscription should receive this event
            if (!in_array($this->eventName, $eventNames) && !in_array('*', $eventNames)) {
                continue;
            }

            // Apply filters if any
            if (!$this->passesFilters($filters)) {
                continue;
            }

            // Dispatch individual webhook call
            CallWebhook::dispatch(
                $subscription->webhook_url,
                [
                    'event' => $this->eventName,
                    'payload' => $this->payload,
                    'timestamp' => now()->toIso8601String(),
                    'subscription_id' => $subscription->id
                ]
            );

            // Update last triggered timestamp
            DB::table('event_subscriptions')
                ->where('id', $subscription->id)
                ->update(['last_triggered_at' => now()]);
        }
    }

    private function passesFilters(array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        // Check entity type filter
        if (isset($filters['entity_type']) && isset($this->payload['entity_type'])) {
            if ($filters['entity_type'] !== $this->payload['entity_type']) {
                return false;
            }
        }

        // Check branch filter
        if (isset($filters['branch_id']) && isset($this->payload['branch_id'])) {
            if ($filters['branch_id'] != $this->payload['branch_id']) {
                return false;
            }
        }

        // Check minimum value filters (e.g., minimum call duration)
        if (isset($filters['min_duration']) && isset($this->payload['duration'])) {
            if ($this->payload['duration'] < $filters['min_duration']) {
                return false;
            }
        }

        return true;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to trigger event webhooks', [
            'event_name' => $this->eventName,
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);
    }
}