<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Process Domain Event Job
 *
 * Handles asynchronous processing of domain events.
 * When an event is too expensive to process immediately,
 * it's queued for background processing via this job.
 *
 * @author Architecture Refactoring
 * @date 2025-10-18
 */
class ProcessDomainEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    public function __construct(
        public array $eventData,
        public string $listenerClass,
        public string $correlationId,
    ) {
        $this->onQueue('events');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('⏳ ProcessDomainEvent: Starting async event processing', [
            'eventId' => $this->eventData['eventId'],
            'eventName' => $this->eventData['eventName'],
            'listener' => $this->listenerClass,
            'correlationId' => $this->correlationId,
        ]);

        try {
            // Instantiate listener
            if (!class_exists($this->listenerClass)) {
                throw new Exception("Listener class not found: {$this->listenerClass}");
            }

            $listener = app($this->listenerClass);

            // Recreate event from stored data
            $eventClass = $this->eventData['eventClass'];
            if (!class_exists($eventClass)) {
                throw new Exception("Event class not found: {$eventClass}");
            }

            $event = $eventClass::fromArray($this->eventData);

            // Execute listener
            $startTime = microtime(true);
            $listener->handle($event);
            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('✅ ProcessDomainEvent: Async event processed successfully', [
                'eventId' => $this->eventData['eventId'],
                'listener' => $this->listenerClass,
                'duration_ms' => round($duration, 2),
                'correlationId' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            Log::error('❌ ProcessDomainEvent: Error processing event', [
                'eventId' => $this->eventData['eventId'],
                'listener' => $this->listenerClass,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'correlationId' => $this->correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $delay = pow(2, $this->attempts()) * 60; // 1min, 2min, 4min
                Log::info("⏰ ProcessDomainEvent: Retrying in {$delay} seconds", [
                    'eventId' => $this->eventData['eventId'],
                    'attempt' => $this->attempts(),
                ]);
                $this->release($delay);
            } else {
                // Max retries exceeded
                Log::critical('🔥 ProcessDomainEvent: Max retries exceeded', [
                    'eventId' => $this->eventData['eventId'],
                    'listener' => $this->listenerClass,
                    'correlationId' => $this->correlationId,
                ]);

                // Could store in failed_events table for manual review
                throw $e;
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::emergency('💥 ProcessDomainEvent: Job failed permanently', [
            'eventId' => $this->eventData['eventId'] ?? 'unknown',
            'listener' => $this->listenerClass,
            'error' => $exception->getMessage(),
            'correlationId' => $this->correlationId,
        ]);

        // TODO: Store in failed_events table for manual review
        // db('failed_events')->insert([...])
    }
}
