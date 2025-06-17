<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\AppointmentService;
use Carbon\Carbon;

class ProcessCalcomWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $triggerEvent;
    protected array $payload;

    public function __construct(string $triggerEvent, array $payload)
    {
        $this->triggerEvent = $triggerEvent;
        $this->payload = $payload;
    }

    public function handle()
    {
        Log::info('Processing Cal.com webhook', [
            'trigger_event' => $this->triggerEvent,
            'booking_id' => $this->payload['payload']['id'] ?? null
        ]);

        try {
            switch ($this->triggerEvent) {
                case 'BOOKING_CREATED':
                    $this->handleBookingCreated();
                    break;
                    
                case 'BOOKING_RESCHEDULED':
                    $this->handleBookingRescheduled();
                    break;
                    
                case 'BOOKING_CANCELLED':
                    $this->handleBookingCancelled();
                    break;
                    
                case 'BOOKING_CONFIRMED':
                    $this->handleBookingConfirmed();
                    break;
                    
                case 'BOOKING_REJECTED':
                    $this->handleBookingRejected();
                    break;
                    
                case 'BOOKING_REQUESTED':
                    $this->handleBookingRequested();
                    break;
                    
                default:
                    Log::warning('Unknown Cal.com webhook event', [
                        'trigger_event' => $this->triggerEvent
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Cal.com webhook', [
                'trigger_event' => $this->triggerEvent,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    protected function handleBookingCreated()
    {
        $booking = $this->payload['payload'] ?? [];
        
        // Suche nach existierendem Appointment
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if ($appointment) {
            Log::info('Appointment already exists for Cal.com booking', [
                'booking_id' => $booking['id'],
                'appointment_id' => $appointment->id
            ]);
            return;
        }
        
        // Finde Company über Event Type
        $eventType = CalcomEventType::where('calcom_event_type_id', $booking['eventTypeId'] ?? null)->first();
        if (!$eventType) {
            Log::warning('No event type found for Cal.com booking', [
                'event_type_id' => $booking['eventTypeId'] ?? null
            ]);
            return;
        }
        
        $company = $eventType->company;
        
        // Erstelle oder finde Customer
        $attendee = $booking['attendees'][0] ?? null;
        if (!$attendee) {
            Log::warning('No attendee in Cal.com booking', ['booking_id' => $booking['id']]);
            return;
        }
        
        $customer = Customer::firstOrCreate(
            [
                'email' => $attendee['email'],
                'company_id' => $company->id
            ],
            [
                'name' => $attendee['name'] ?? 'Unbekannt',
                'phone' => $attendee['phoneNumber'] ?? null,
                'source' => 'cal.com',
                'notes' => 'Erstellt durch Cal.com Webhook'
            ]
        );
        
        // Erstelle Appointment
        $appointment = new Appointment();
        $appointment->company_id = $company->id;
        $appointment->branch_id = $eventType->branch_id;
        $appointment->customer_id = $customer->id;
        $appointment->staff_id = $eventType->staff_assignments->first()?->staff_id;
        $appointment->service_id = $eventType->service_id;
        $appointment->calcom_booking_id = $booking['id'];
        $appointment->calcom_v2_booking_id = $booking['id'];
        $appointment->calcom_event_type_id = $booking['eventTypeId'];
        $appointment->external_id = $booking['uid'] ?? null;
        $appointment->starts_at = Carbon::parse($booking['startTime']);
        $appointment->ends_at = Carbon::parse($booking['endTime']);
        $appointment->status = $this->mapCalcomStatus($booking['status'] ?? 'ACCEPTED');
        $appointment->notes = $booking['description'] ?? null;
        $appointment->price = $eventType->service?->price ?? 0;
        
        $appointment->meta = [
            'calcom_webhook' => [
                'received_at' => now()->toIso8601String(),
                'trigger_event' => $this->triggerEvent,
                'booking_data' => $booking
            ]
        ];
        
        $appointment->save();
        
        Log::info('Appointment created from Cal.com webhook', [
            'appointment_id' => $appointment->id,
            'booking_id' => $booking['id']
        ]);
    }
    
    protected function handleBookingRescheduled()
    {
        $booking = $this->payload['payload'] ?? [];
        
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if (!$appointment) {
            Log::warning('No appointment found for rescheduled Cal.com booking', [
                'booking_id' => $booking['id']
            ]);
            // Erstelle neuen Termin
            $this->handleBookingCreated();
            return;
        }
        
        // Update appointment times
        $appointment->starts_at = Carbon::parse($booking['startTime']);
        $appointment->ends_at = Carbon::parse($booking['endTime']);
        $appointment->status = 'confirmed'; // Rescheduled bookings are confirmed
        
        // Update metadata
        $meta = $appointment->meta ?? [];
        $meta['calcom_webhook'][] = [
            'received_at' => now()->toIso8601String(),
            'trigger_event' => $this->triggerEvent,
            'previous_start' => $appointment->getOriginal('starts_at'),
            'new_start' => $booking['startTime']
        ];
        $appointment->meta = $meta;
        
        $appointment->save();
        
        Log::info('Appointment rescheduled from Cal.com webhook', [
            'appointment_id' => $appointment->id,
            'new_time' => $appointment->starts_at
        ]);
    }
    
    protected function handleBookingCancelled()
    {
        $booking = $this->payload['payload'] ?? [];
        
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if (!$appointment) {
            Log::warning('No appointment found for cancelled Cal.com booking', [
                'booking_id' => $booking['id']
            ]);
            return;
        }
        
        $appointment->status = 'cancelled';
        
        // Update metadata
        $meta = $appointment->meta ?? [];
        $meta['calcom_webhook'][] = [
            'received_at' => now()->toIso8601String(),
            'trigger_event' => $this->triggerEvent,
            'cancellation_reason' => $booking['cancellationReason'] ?? null
        ];
        $appointment->meta = $meta;
        
        $appointment->save();
        
        Log::info('Appointment cancelled from Cal.com webhook', [
            'appointment_id' => $appointment->id
        ]);
    }
    
    protected function handleBookingConfirmed()
    {
        $booking = $this->payload['payload'] ?? [];
        
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if (!$appointment) {
            // Erstelle neuen Termin
            $this->handleBookingCreated();
            return;
        }
        
        $appointment->status = 'confirmed';
        $appointment->save();
        
        Log::info('Appointment confirmed from Cal.com webhook', [
            'appointment_id' => $appointment->id
        ]);
    }
    
    protected function handleBookingRejected()
    {
        $booking = $this->payload['payload'] ?? [];
        
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if (!$appointment) {
            Log::warning('No appointment found for rejected Cal.com booking', [
                'booking_id' => $booking['id']
            ]);
            return;
        }
        
        $appointment->status = 'cancelled';
        
        // Update metadata
        $meta = $appointment->meta ?? [];
        $meta['calcom_webhook'][] = [
            'received_at' => now()->toIso8601String(),
            'trigger_event' => $this->triggerEvent,
            'rejection_reason' => $booking['rejectionReason'] ?? 'Rejected by host'
        ];
        $appointment->meta = $meta;
        
        $appointment->save();
        
        Log::info('Appointment rejected from Cal.com webhook', [
            'appointment_id' => $appointment->id
        ]);
    }
    
    protected function handleBookingRequested()
    {
        $booking = $this->payload['payload'] ?? [];
        
        // Bei requested bookings erstellen wir einen pending appointment
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])
            ->orWhere('calcom_v2_booking_id', $booking['id'])
            ->first();
            
        if ($appointment) {
            Log::info('Appointment already exists for requested Cal.com booking', [
                'booking_id' => $booking['id'],
                'appointment_id' => $appointment->id
            ]);
            return;
        }
        
        // Erstelle neuen Termin mit pending status
        $this->handleBookingCreated();
        
        // Update status to pending
        $appointment = Appointment::where('calcom_booking_id', $booking['id'])->first();
        if ($appointment) {
            $appointment->status = 'pending';
            $appointment->save();
        }
    }
    
    protected function mapCalcomStatus(string $calcomStatus): string
    {
        return match (strtoupper($calcomStatus)) {
            'ACCEPTED' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            default => 'pending'
        };
    }
}