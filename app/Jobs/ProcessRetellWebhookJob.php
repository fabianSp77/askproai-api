<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\AppointmentBookingService;
use App\Services\AvailabilityService;
use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use App\Services\PhoneNumberResolver;
use App\Exceptions\BookingException;
use App\Exceptions\AvailabilityException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRetellWebhookJob implements ShouldQueue
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
     * @var array<int>
     */
    public $backoff = [10, 30, 60];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The webhook data
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->queue = 'webhooks';
    }

    /**
     * Execute the job.
     */
    public function handle(AppointmentBookingService $bookingService)
    {
        Log::info('Processing Retell webhook in job', [
            'job_id' => $this->job->uuid(),
            'attempt' => $this->attempts(),
            'data_keys' => array_keys($this->data)
        ]);

        try {
            // 1. Save or update call record
            $call = $this->saveCallRecord();
            
            // 2. Check if appointment booking is needed
            if ($this->hasAppointmentData()) {
                $this->processAppointmentBooking($call, $bookingService);
            }
            
            // 3. Check if it's an availability request
            elseif ($this->isAvailabilityRequest()) {
                $this->processAvailabilityRequest($call);
            }
            
            // 4. Otherwise just log the call
            else {
                Log::info('Call processed without appointment', [
                    'call_id' => $call->id,
                    'phone' => $call->phone_number,
                    'duration' => $call->call_duration
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell webhook', [
                'job_id' => $this->job->uuid(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data
            ]);
            
            // Rethrow to trigger retry
            throw $e;
        }
    }

    /**
     * Save or update call record
     */
    protected function saveCallRecord(): Call
    {
        $callId = $this->data['call_id'] ?? 'retell_' . uniqid();
        
        // Resolve branch and agent from phone number or metadata
        $resolver = new PhoneNumberResolver();
        $resolved = $resolver->resolveFromWebhook($this->data);
        
        return Call::updateOrCreate(
            ['call_id' => $callId],
            [
                'phone_number' => $this->data['from'] ?? $this->data['phone_number'] ?? null,
                'from_number' => $this->data['from'] ?? $this->data['phone_number'] ?? null,
                'to_number' => $this->data['to'] ?? $this->data['to_number'] ?? null,
                'call_status' => $this->data['status'] ?? $this->data['call_status'] ?? 'completed',
                'call_type' => $this->data['type'] ?? $this->data['call_type'] ?? 'inbound',
                'call_time' => isset($this->data['call_time']) ? Carbon::parse($this->data['call_time']) : now(),
                'duration_sec' => $this->data['duration'] ?? $this->data['duration_sec'] ?? 0,
                'duration_minutes' => isset($this->data['duration']) ? round($this->data['duration'] / 60, 2) : 0,
                'raw_data' => json_encode($this->data),
                'transcript' => $this->data['transcript'] ?? null,
                'summary' => $this->data['summary'] ?? null,
                'user_sentiment' => $this->data['user_sentiment'] ?? $this->data['sentiment'] ?? null,
                'successful' => $this->data['call_successful'] ?? true,
                'company_id' => $resolved['company_id'] ?? $this->extractCompanyId(),
                'branch_id' => $resolved['branch_id'] ?? null,
                'agent_id' => $resolved['agent_id'] ?? null,
                'retell_call_id' => $this->data['retell_call_id'] ?? $this->data['call_id'] ?? null,
                'audio_url' => $this->data['audio_url'] ?? $this->data['recording_url'] ?? null,
                'cost' => $this->data['cost'] ?? null,
                'cost_cents' => isset($this->data['cost']) ? intval($this->data['cost'] * 100) : null,
            ]
        );
    }

    /**
     * Check if webhook contains appointment data
     */
    protected function hasAppointmentData(): bool
    {
        return !empty($this->data['_datum__termin']) && !empty($this->data['_uhrzeit__termin']);
    }

    /**
     * Process appointment booking
     */
    protected function processAppointmentBooking(Call $call, AppointmentBookingService $bookingService): void
    {
        Log::info('Processing appointment booking from call', [
            'call_id' => $call->id,
            'date' => $this->data['_datum__termin'],
            'time' => $this->data['_uhrzeit__termin']
        ]);

        try {
            // Parse date and time
            $dateStr = $this->data['_datum__termin'];
            $timeStr = $this->data['_uhrzeit__termin'];
            $startsAt = Carbon::parse($dateStr . ' ' . $timeStr, 'Europe/Berlin');
            
            // Extract booking data
            $bookingData = [
                'customer' => [
                    'phone' => $this->data['phone_number'] ?? $this->data['from'] ?? $this->data['_telefonnummer'] ?? 'unknown',
                    'name' => $this->data['_name'] ?? $this->data['name'] ?? 'Unbekannter Kunde',
                    'email' => $this->data['_email'] ?? $this->data['email'] ?? null,
                    'company_id' => $this->extractCompanyId()
                ],
                'service_id' => $this->extractServiceId(),
                'staff_id' => $this->extractStaffId(),
                'starts_at' => $startsAt->toDateTimeString(),
                'notes' => $this->data['_notizen'] ?? $this->data['_zusammenfassung'] ?? ''
            ];
            
            // Book appointment using service
            $appointment = $bookingService->bookFromPhoneCall($bookingData, $call);
            
            Log::info('Appointment booked successfully', [
                'appointment_id' => $appointment->id,
                'customer' => $appointment->customer->name,
                'datetime' => $appointment->starts_at
            ]);
            
        } catch (AvailabilityException $e) {
            Log::warning('Appointment booking failed due to availability', [
                'call_id' => $call->id,
                'error_code' => $e->getErrorCode(),
                'user_message' => $e->getUserMessage(),
                'alternatives' => $e->getAlternatives(),
                'booking_data' => $bookingData ?? null
            ]);
            
            // Update call with availability error
            $call->update([
                'call_status' => 'no_availability',
                'error_message' => $e->getUserMessage(),
                'error_details' => json_encode([
                    'error_code' => $e->getErrorCode(),
                    'alternatives' => $e->getAlternatives()
                ])
            ]);
            
            // Don't retry for availability issues
            return;
            
        } catch (BookingException $e) {
            Log::error('Failed to book appointment', [
                'call_id' => $call->id,
                'error_code' => $e->getErrorCode(),
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
                'booking_data' => $bookingData ?? null
            ]);
            
            // Update call with error
            $call->update([
                'call_status' => 'booking_failed',
                'error_message' => $e->getUserMessage(),
                'error_details' => json_encode($e->getContext())
            ]);
            
            // Retry for certain error types
            if (in_array($e->getErrorCode(), [
                BookingException::ERROR_CALENDAR_SYNC_FAILED,
                BookingException::ERROR_NOTIFICATION_FAILED
            ])) {
                throw $e; // Will trigger retry
            }
            
            // Don't retry for other booking errors
            return;
            
        } catch (\Exception $e) {
            Log::error('Unexpected error during appointment booking', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_data' => $bookingData ?? null
            ]);
            
            // Update call with error
            $call->update([
                'call_status' => 'failed',
                'error_message' => 'Ein unerwarteter Fehler ist aufgetreten.'
            ]);
            
            // Retry for unexpected errors
            throw $e;
        }
    }

    /**
     * Check if it's an availability request
     */
    protected function isAvailabilityRequest(): bool
    {
        $keywords = ['verfügbar', 'availability', 'freie_termine', 'wann_möglich'];
        
        foreach ($keywords as $keyword) {
            if (isset($this->data[$keyword]) || isset($this->data['_' . $keyword])) {
                return true;
            }
        }
        
        if (isset($this->data['transcript'])) {
            $transcript = strtolower($this->data['transcript']);
            if (strpos($transcript, 'wann') !== false && 
                (strpos($transcript, 'termin') !== false || strpos($transcript, 'zeit') !== false)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Process availability request
     */
    protected function processAvailabilityRequest(Call $call): void
    {
        // This would be handled by a separate service
        Log::info('Availability request detected', [
            'call_id' => $call->id
        ]);
        
        // TODO: Implement availability checking logic
    }

    /**
     * Extract company ID from webhook data
     */
    protected function extractCompanyId(): ?int
    {
        if (isset($this->data['company_id'])) {
            return $this->data['company_id'];
        }
        
        if (isset($this->data['tenant_id'])) {
            $company = \App\Models\Company::where('tenant_id', $this->data['tenant_id'])->first();
            return $company?->id;
        }
        
        // Default company
        return \App\Models\Company::where('is_active', true)->first()?->id;
    }

    /**
     * Extract service ID from webhook data
     */
    protected function extractServiceId(): ?int
    {
        $serviceName = $this->data['_dienstleistung'] ?? $this->data['service'] ?? $this->data['_service'] ?? null;
        
        if (!$serviceName) {
            return null;
        }
        
        $companyId = $this->extractCompanyId();
        if (!$companyId) {
            return null;
        }
        
        $service = \App\Models\Service::where('company_id', $companyId)
            ->where('name', 'LIKE', '%' . $serviceName . '%')
            ->where('is_active', true)
            ->first();
            
        return $service?->id;
    }

    /**
     * Extract staff ID from webhook data
     */
    protected function extractStaffId(): ?int
    {
        $staffName = $this->data['_mitarbeiter'] ?? $this->data['staff'] ?? $this->data['_staff'] ?? null;
        
        if (!$staffName) {
            return null;
        }
        
        $companyId = $this->extractCompanyId();
        if (!$companyId) {
            return null;
        }
        
        $staff = \App\Models\Staff::where('company_id', $companyId)
            ->where(function($query) use ($staffName) {
                $query->where('first_name', 'LIKE', '%' . $staffName . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $staffName . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $staffName . '%']);
            })
            ->where('active', true)
            ->first();
            
        return $staff?->id;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Retell webhook job failed permanently', [
            'job_id' => $this->job?->uuid(),
            'error' => $exception->getMessage(),
            'data' => $this->data
        ]);
        
        // Send alert notification
        // TODO: Implement alert system
    }
}