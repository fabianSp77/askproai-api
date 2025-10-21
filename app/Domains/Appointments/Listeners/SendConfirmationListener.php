<?php

namespace App\Domains\Appointments\Listeners;

use App\Shared\Events\EventListener;
use App\Shared\Events\DomainEvent;
use App\Domains\Appointments\Events\AppointmentCreatedEvent;
use App\Domains\Notifications\Events\SendConfirmationRequiredEvent;
use Illuminate\Support\Facades\Log;

/**
 * SendConfirmationListener
 *
 * Listens to AppointmentCreatedEvent and triggers confirmation notification.
 * Demonstrates loosely-coupled event-driven communication.
 *
 * SUBSCRIBES TO:
 * - AppointmentCreatedEvent
 *
 * PUBLISHES:
 * - SendConfirmationRequiredEvent
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class SendConfirmationListener implements EventListener
{
    public static function subscribesTo(): array
    {
        return [AppointmentCreatedEvent::class];
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof AppointmentCreatedEvent) {
            return;
        }

        Log::info('📧 SendConfirmationListener: Processing appointment confirmation', [
            'appointmentId' => $event->appointmentId,
            'customerId' => $event->customerId,
            'correlationId' => $event->correlationId,
        ]);

        try {
            // Get customer details
            $customer = \App\Models\Customer::find($event->customerId);
            if (!$customer) {
                Log::warning('⚠️ SendConfirmationListener: Customer not found', [
                    'customerId' => $event->customerId,
                ]);
                return;
            }

            // Publish confirmation required event
            $confirmationEvent = new SendConfirmationRequiredEvent(
                appointmentId: $event->appointmentId,
                customerId: $event->customerId,
                customerEmail: $customer->email,
                customerPhone: $customer->phone,
                appointmentDetails: [
                    'staffName' => 'TBD', // Would be fetched from staff
                    'serviceName' => 'TBD', // Would be fetched from service
                    'appointmentStart' => $event->appointmentStart->toFormattedDateString(),
                    'appointmentEnd' => $event->appointmentEnd->toFormattedDateString(),
                ],
                correlationId: $event->correlationId,
                causedBy: $event->causedBy,
                metadata: $event->metadata
            );

            app(\App\Shared\Events\EventBus::class)->publish($confirmationEvent);

            Log::info('✅ SendConfirmationListener: Confirmation published', [
                'appointmentId' => $event->appointmentId,
                'customerEmail' => $customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ SendConfirmationListener: Error sending confirmation', [
                'appointmentId' => $event->appointmentId,
                'error' => $e->getMessage(),
                'correlationId' => $event->correlationId,
            ]);
        }
    }

    public function priority(): int
    {
        // Send confirmations early (before other listeners)
        return 100;
    }

    public function isAsync(): bool
    {
        // Notifications can be async (don't block appointment creation)
        return true;
    }
}
