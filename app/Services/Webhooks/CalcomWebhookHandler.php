<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\CalcomEventType;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CalcomWebhookHandler extends BaseWebhookHandler
{
    /**
     * Get supported event types
     *
     * @return array
     */
    public function getSupportedEvents(): array
    {
        return [
            'BOOKING_CREATED',
            'BOOKING_RESCHEDULED', 
            'BOOKING_CANCELLED',
            'BOOKING_CONFIRMED',
            'BOOKING_REJECTED',
            'BOOKING_REQUESTED',
            'BOOKING_PAYMENT_INITIATED',
            'FORM_SUBMITTED',
            'MEETING_ENDED',
            'RECORDING_READY'
        ];
    }
    
    /**
     * Handle BOOKING_CREATED event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleBookingCreated(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload['payload'] ?? [];
        
        $this->logInfo('Processing BOOKING_CREATED event', [
            'booking_uid' => $payload['uid'] ?? null,
            'booking_id' => $payload['id'] ?? null
        ]);
        
        return $this->withCorrelationId($correlationId, function () use ($payload, $correlationId) {
            // Find or create appointment
            $appointment = $this->syncAppointmentFromCalcom($payload, $correlationId);
            
            if (!$appointment) {
                $this->logError('Failed to sync appointment from Cal.com');
                return [
                    'success' => false,
                    'error' => 'Failed to sync appointment'
                ];
            }
            
            // Send confirmation if needed
            if ($appointment->wasRecentlyCreated) {
                $this->sendBookingConfirmation($appointment);
            }
            
            $this->logInfo('Booking created successfully', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id
            ]);
            
            return [
                'success' => true,
                'appointment_id' => $appointment->id,
                'message' => 'Booking created successfully'
            ];
        });
    }
    
    /**
     * Handle BOOKING_RESCHEDULED event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleBookingRescheduled(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload['payload'] ?? [];
        
        $this->logInfo('Processing BOOKING_RESCHEDULED event', [
            'booking_uid' => $payload['uid'] ?? null,
            'booking_id' => $payload['id'] ?? null
        ]);
        
        // Find existing appointment
        $appointment = Appointment::where('calcom_booking_id', $payload['id'])->first();
        
        if (!$appointment) {
            $appointment = Appointment::where('calcom_booking_uid', $payload['uid'])->first();
        }
        
        if (!$appointment) {
            $this->logWarning('Appointment not found for rescheduling', [
                'calcom_booking_id' => $payload['id'],
                'calcom_booking_uid' => $payload['uid']
            ]);
            
            // Create new appointment from webhook data
            $appointment = $this->syncAppointmentFromCalcom($payload, $correlationId);
        }
        
        if ($appointment) {
            // Store old time for notification
            $oldStartTime = $appointment->scheduled_at;
            
            // Update appointment times
            $appointment->update([
                'scheduled_at' => Carbon::parse($payload['startTime']),
                'scheduled_end_at' => Carbon::parse($payload['endTime']),
                'status' => 'rescheduled',
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'rescheduled_at' => now()->toIso8601String(),
                    'old_start_time' => $oldStartTime->toIso8601String(),
                    'reschedule_reason' => $payload['rescheduleReason'] ?? null
                ])
            ]);
            
            // Send rescheduling notification
            $this->sendReschedulingNotification($appointment, $oldStartTime);
            
            $this->logInfo('Booking rescheduled successfully', [
                'appointment_id' => $appointment->id,
                'old_time' => $oldStartTime->toIso8601String(),
                'new_time' => $appointment->scheduled_at->toIso8601String()
            ]);
            
            return [
                'success' => true,
                'appointment_id' => $appointment->id,
                'message' => 'Booking rescheduled successfully'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to reschedule booking'
        ];
    }
    
    /**
     * Handle BOOKING_CANCELLED event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleBookingCancelled(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload['payload'] ?? [];
        
        $this->logInfo('Processing BOOKING_CANCELLED event', [
            'booking_uid' => $payload['uid'] ?? null,
            'booking_id' => $payload['id'] ?? null
        ]);
        
        // Find appointment
        $appointment = Appointment::where('calcom_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'])
            ->first();
        
        if (!$appointment) {
            $this->logWarning('Appointment not found for cancellation', [
                'calcom_booking_id' => $payload['id'],
                'calcom_booking_uid' => $payload['uid']
            ]);
            
            return [
                'success' => true,
                'message' => 'Appointment not found, possibly already cancelled'
            ];
        }
        
        // Update appointment status
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $payload['cancellationReason'] ?? null,
            'metadata' => array_merge($appointment->metadata ?? [], [
                'cancelled_by' => $payload['cancelledBy'] ?? 'unknown',
                'cancellation_note' => $payload['cancellationNote'] ?? null
            ])
        ]);
        
        // Send cancellation notification
        $this->sendCancellationNotification($appointment);
        
        $this->logInfo('Booking cancelled successfully', [
            'appointment_id' => $appointment->id
        ]);
        
        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'message' => 'Booking cancelled successfully'
        ];
    }
    
    /**
     * Handle BOOKING_CONFIRMED event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleBookingConfirmed(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload['payload'] ?? [];
        
        $this->logInfo('Processing BOOKING_CONFIRMED event', [
            'booking_uid' => $payload['uid'] ?? null
        ]);
        
        // Find appointment
        $appointment = Appointment::where('calcom_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'])
            ->first();
        
        if ($appointment) {
            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);
            
            $this->logInfo('Booking confirmed', [
                'appointment_id' => $appointment->id
            ]);
        }
        
        return [
            'success' => true,
            'appointment_id' => $appointment->id ?? null,
            'message' => 'Booking confirmed'
        ];
    }
    
    /**
     * Handle MEETING_ENDED event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleMeetingEnded(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload['payload'] ?? [];
        
        $this->logInfo('Processing MEETING_ENDED event', [
            'booking_uid' => $payload['uid'] ?? null
        ]);
        
        // Find appointment
        $appointment = Appointment::where('calcom_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'])
            ->first();
        
        if ($appointment) {
            $appointment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'meeting_duration' => $payload['duration'] ?? null,
                    'meeting_ended_at' => now()->toIso8601String()
                ])
            ]);
            
            $this->logInfo('Meeting ended', [
                'appointment_id' => $appointment->id
            ]);
        }
        
        return [
            'success' => true,
            'appointment_id' => $appointment->id ?? null,
            'message' => 'Meeting ended'
        ];
    }
    
    /**
     * Sync appointment from Cal.com webhook data
     *
     * @param array $bookingData
     * @param string $correlationId
     * @return Appointment|null
     */
    protected function syncAppointmentFromCalcom(array $bookingData, string $correlationId): ?Appointment
    {
        try {
            // Extract key data
            $eventTypeId = $bookingData['eventTypeId'] ?? null;
            $userId = $bookingData['userId'] ?? null;
            $attendees = $bookingData['attendees'] ?? [];
            
            // Find event type
            $eventType = CalcomEventType::where('calcom_event_type_id', $eventTypeId)->first();
            
            if (!$eventType) {
                $this->logError('Event type not found', [
                    'calcom_event_type_id' => $eventTypeId
                ]);
                return null;
            }
            
            // Find staff member
            $staff = null;
            if ($userId) {
                $staff = Staff::where('calcom_user_id', $userId)->first();
            }
            
            // Find or create customer
            $customer = null;
            if (!empty($attendees)) {
                $attendee = $attendees[0]; // Primary attendee
                $customer = $this->findOrCreateCustomer(
                    $eventType->company_id,
                    $attendee,
                    $correlationId
                );
            }
            
            // Create or update appointment
            $appointment = Appointment::updateOrCreate(
                [
                    'calcom_booking_id' => $bookingData['id']
                ],
                [
                    'company_id' => $eventType->company_id,
                    'branch_id' => $eventType->branch_id,
                    'customer_id' => $customer->id ?? null,
                    'staff_id' => $staff->id ?? null,
                    'calcom_event_type_id' => $eventType->id,
                    'calcom_booking_uid' => $bookingData['uid'],
                    'title' => $bookingData['title'] ?? $eventType->title,
                    'description' => $bookingData['description'] ?? null,
                    'scheduled_at' => Carbon::parse($bookingData['startTime']),
                    'scheduled_end_at' => Carbon::parse($bookingData['endTime']),
                    'duration' => $eventType->length,
                    'status' => $this->mapCalcomStatus($bookingData['status'] ?? 'ACCEPTED'),
                    'location' => $bookingData['location'] ?? null,
                    'meeting_url' => $bookingData['meetingUrl'] ?? null,
                    'notes' => $bookingData['notes'] ?? null,
                    'metadata' => [
                        'calcom_data' => $bookingData,
                        'correlation_id' => $correlationId,
                        'synced_at' => now()->toIso8601String()
                    ]
                ]
            );
            
            return $appointment;
            
        } catch (\Exception $e) {
            $this->logError('Failed to sync appointment from Cal.com', [
                'error' => $e->getMessage(),
                'booking_id' => $bookingData['id'] ?? null
            ]);
            return null;
        }
    }
    
    /**
     * Find or create customer from attendee data
     *
     * @param int $companyId
     * @param array $attendeeData
     * @param string $correlationId
     * @return Customer
     */
    protected function findOrCreateCustomer(int $companyId, array $attendeeData, string $correlationId): Customer
    {
        $email = $attendeeData['email'] ?? null;
        $phone = $attendeeData['phoneNumber'] ?? null;
        $name = $attendeeData['name'] ?? 'Unknown';
        
        // Try to find existing customer
        $query = Customer::where('company_id', $companyId);
        
        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        }
        
        $customer = $query->first();
        
        if (!$customer) {
            // Create new customer
            $customer = Customer::create([
                'company_id' => $companyId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'source' => 'calcom_webhook',
                'metadata' => [
                    'created_from_webhook' => true,
                    'correlation_id' => $correlationId,
                    'timezone' => $attendeeData['timeZone'] ?? null,
                    'locale' => $attendeeData['locale'] ?? null
                ]
            ]);
        }
        
        return $customer;
    }
    
    /**
     * Map Cal.com status to internal status
     *
     * @param string $calcomStatus
     * @return string
     */
    protected function mapCalcomStatus(string $calcomStatus): string
    {
        return match (strtoupper($calcomStatus)) {
            'ACCEPTED' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'rejected',
            default => 'scheduled'
        };
    }
    
    /**
     * Send booking confirmation
     *
     * @param Appointment $appointment
     * @return void
     */
    protected function sendBookingConfirmation(Appointment $appointment): void
    {
        try {
            // TODO: Implement email notification service
            $this->logInfo('Booking confirmation would be sent', [
                'appointment_id' => $appointment->id,
                'customer_email' => $appointment->customer->email ?? null
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to send booking confirmation', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send rescheduling notification
     *
     * @param Appointment $appointment
     * @param Carbon $oldStartTime
     * @return void
     */
    protected function sendReschedulingNotification(Appointment $appointment, Carbon $oldStartTime): void
    {
        try {
            // TODO: Implement email notification service
            $this->logInfo('Rescheduling notification would be sent', [
                'appointment_id' => $appointment->id,
                'old_time' => $oldStartTime->toIso8601String(),
                'new_time' => $appointment->scheduled_at->toIso8601String()
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to send rescheduling notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send cancellation notification
     *
     * @param Appointment $appointment
     * @return void
     */
    protected function sendCancellationNotification(Appointment $appointment): void
    {
        try {
            // TODO: Implement email notification service
            $this->logInfo('Cancellation notification would be sent', [
                'appointment_id' => $appointment->id
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to send cancellation notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
}