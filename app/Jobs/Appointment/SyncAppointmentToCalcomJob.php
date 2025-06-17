<?php

namespace App\Jobs\Appointment;

use App\Models\Appointment;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Logging\StructuredLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAppointmentToCalcomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 600, 1800, 3600]; // 1min, 5min, 10min, 30min, 1hr

    /**
     * The maximum number of unhandled exceptions.
     */
    public $maxExceptions = 3;

    protected Appointment $appointment;
    protected string $correlationId;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, ?string $correlationId = null)
    {
        $this->appointment = $appointment;
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid();
        $this->onQueue('calendar-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(
        CalcomV2Service $calcomService,
        CircuitBreaker $circuitBreaker,
        StructuredLogger $logger
    ): void {
        $logger->setCorrelationId($this->correlationId);
        
        $logger->logBookingFlow('calendar_sync_started', [
            'appointment_id' => $this->appointment->id,
            'attempt' => $this->attempts(),
            'has_calcom_id' => !empty($this->appointment->calcom_booking_id),
        ]);

        // Skip if already synced
        if ($this->appointment->calcom_booking_id || $this->appointment->external_id) {
            $logger->info('Appointment already synced with Cal.com', [
                'appointment_id' => $this->appointment->id,
                'calcom_booking_id' => $this->appointment->calcom_booking_id,
            ]);
            return;
        }

        // Skip if appointment is cancelled or completed
        if (in_array($this->appointment->status, ['cancelled', 'completed', 'no_show'])) {
            $logger->info('Skipping sync for appointment with status: ' . $this->appointment->status, [
                'appointment_id' => $this->appointment->id,
                'status' => $this->appointment->status,
            ]);
            return;
        }

        // Ensure we have required data
        if (!$this->appointment->service || !$this->appointment->service->calcom_event_type_id) {
            $logger->warning('Cannot sync appointment without service or event type', [
                'appointment_id' => $this->appointment->id,
                'has_service' => (bool) $this->appointment->service,
                'event_type_id' => $this->appointment->service?->calcom_event_type_id,
            ]);
            
            // Mark as sync failed in metadata
            $this->markSyncFailed('Missing service or event type configuration');
            return;
        }

        try {
            // Use circuit breaker for Cal.com call
            $result = $circuitBreaker->call('calcom', function () use ($calcomService, $logger) {
                $startTime = microtime(true);
                
                $bookingData = [
                    'eventTypeId' => $this->appointment->service->calcom_event_type_id,
                    'start' => $this->appointment->starts_at->toIso8601String(),
                    'end' => $this->appointment->ends_at->toIso8601String(),
                    'responses' => [
                        'name' => $this->appointment->customer->name,
                        'email' => $this->appointment->customer->email ?? 'noreply@askproai.de',
                        'phone' => $this->appointment->customer->phone,
                        'notes' => $this->appointment->notes,
                    ],
                    'metadata' => [
                        'appointment_id' => $this->appointment->id,
                        'source' => 'phone_ai',
                        'company_id' => $this->appointment->company_id,
                        'branch_id' => $this->appointment->branch_id,
                        'sync_attempt' => $this->attempts(),
                        'correlation_id' => $this->correlationId,
                    ],
                    'timeZone' => $this->appointment->company->timezone ?? 'Europe/Berlin',
                ];

                $logger->logApiCall(
                    'calcom',
                    '/bookings',
                    'POST',
                    ['body' => $bookingData, 'start_time' => now()],
                    null,
                    null
                );

                $response = $calcomService->createBooking($bookingData);
                
                $duration = microtime(true) - $startTime;
                
                $logger->logApiCall(
                    'calcom',
                    '/bookings',
                    'POST',
                    ['body' => $bookingData],
                    ['status' => 200, 'body' => $response],
                    $duration
                );

                return $response;
            });

            // Update appointment with Cal.com booking details
            $this->appointment->update([
                'calcom_booking_id' => $result['id'] ?? null,
                'external_id' => $result['uid'] ?? null,
                'metadata' => array_merge($this->appointment->metadata ?? [], [
                    'calcom_sync' => [
                        'synced_at' => now()->toIso8601String(),
                        'booking_id' => $result['id'] ?? null,
                        'booking_uid' => $result['uid'] ?? null,
                        'attempt' => $this->attempts(),
                    ]
                ])
            ]);

            $logger->logBookingFlow('calendar_sync_completed', [
                'appointment_id' => $this->appointment->id,
                'calcom_booking_id' => $result['id'] ?? null,
                'duration_ms' => isset($duration) ? round($duration * 1000, 2) : null,
            ]);

        } catch (\App\Exceptions\CircuitBreakerOpenException $e) {
            // Circuit breaker is open - Cal.com is temporarily unavailable
            $logger->warning('Cal.com circuit breaker is open, will retry later', [
                'appointment_id' => $this->appointment->id,
                'attempt' => $this->attempts(),
            ]);

            // Release the job back to queue with exponential backoff
            $this->release($this->backoff[$this->attempts() - 1] ?? 3600);

        } catch (\Exception $e) {
            $logger->logError($e, [
                'appointment_id' => $this->appointment->id,
                'attempt' => $this->attempts(),
                'context' => 'calendar_sync',
            ]);

            // If this is the last attempt, mark as permanently failed
            if ($this->attempts() >= $this->tries) {
                $this->markSyncFailed($e->getMessage());
                
                // Dispatch notification job to alert admin
                dispatch(new \App\Jobs\NotifyAdminOfSyncFailureJob(
                    $this->appointment,
                    'Cal.com sync failed after ' . $this->tries . ' attempts'
                ));
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Mark appointment as sync failed in metadata
     */
    protected function markSyncFailed(string $reason): void
    {
        $this->appointment->update([
            'metadata' => array_merge($this->appointment->metadata ?? [], [
                'calendar_sync_failed' => true,
                'calendar_sync_error' => $reason,
                'calendar_sync_attempts' => $this->attempts(),
                'calendar_sync_failed_at' => now()->toIso8601String(),
            ])
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Cal.com sync job permanently failed', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->markSyncFailed('Job failed: ' . $exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'appointment:' . $this->appointment->id,
            'company:' . $this->appointment->company_id,
            'sync:calcom',
        ];
    }
}