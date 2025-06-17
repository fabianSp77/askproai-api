<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

class RetryCalendarSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [300, 600, 1800]; // 5 min, 10 min, 30 min

    /**
     * The appointment ID to sync
     *
     * @var int
     */
    protected $appointmentId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $appointment = Appointment::with(['service', 'customer', 'company'])->find($this->appointmentId);
        
        if (!$appointment) {
            Log::warning('Appointment not found for calendar sync retry', [
                'appointment_id' => $this->appointmentId
            ]);
            return;
        }
        
        // Skip if already synced
        if ($appointment->calcom_booking_id || $appointment->external_id) {
            Log::info('Appointment already synced with calendar', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id
            ]);
            return;
        }
        
        // Skip if appointment is cancelled
        if ($appointment->status === 'cancelled') {
            Log::info('Skipping calendar sync for cancelled appointment', [
                'appointment_id' => $appointment->id
            ]);
            return;
        }
        
        // Skip if no service or event type ID
        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            Log::warning('Cannot sync appointment without service or event type', [
                'appointment_id' => $appointment->id,
                'has_service' => (bool) $appointment->service,
                'event_type_id' => $appointment->service?->calcom_event_type_id
            ]);
            return;
        }
        
        try {
            $calcomService = new CalcomV2Service($appointment->company->calcom_api_key);
            
            $calcomBooking = $calcomService->createBooking([
                'eventTypeId' => $appointment->service->calcom_event_type_id,
                'start' => $appointment->starts_at->toIso8601String(),
                'responses' => [
                    'name' => $appointment->customer->name,
                    'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                    'phone' => $appointment->customer->phone,
                    'notes' => $appointment->notes,
                ],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'source' => 'phone_ai',
                    'retry_attempt' => $this->attempts()
                ],
                'timeZone' => 'Europe/Berlin',
            ]);
            
            $appointment->update([
                'calcom_booking_id' => $calcomBooking['id'] ?? null,
                'external_id' => $calcomBooking['uid'] ?? null,
            ]);
            
            Log::info('Successfully synced appointment with calendar on retry', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $calcomBooking['id'] ?? null,
                'attempt' => $this->attempts()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to sync appointment with calendar on retry', [
                'appointment_id' => $appointment->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If this is the last attempt, mark appointment with sync failure
            if ($this->attempts() >= $this->tries) {
                $appointment->update([
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'calendar_sync_failed' => true,
                        'calendar_sync_error' => $e->getMessage(),
                        'calendar_sync_attempts' => $this->attempts()
                    ])
                ]);
                
                // TODO: Send notification to admin about sync failure
            }
            
            throw $e; // Re-throw to trigger retry
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Calendar sync job permanently failed', [
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage()
        ]);
    }
}
