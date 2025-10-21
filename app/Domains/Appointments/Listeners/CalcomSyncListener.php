<?php

namespace App\Domains\Appointments\Listeners;

use App\Shared\Events\EventListener;
use App\Shared\Events\DomainEvent;
use App\Domains\Appointments\Events\AppointmentCreatedEvent;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncToCalcomJob;

/**
 * CalcomSyncListener
 *
 * Listens to AppointmentCreatedEvent and syncs to Cal.com.
 * Critical path: must sync to Cal.com for bidirectional sync.
 *
 * SUBSCRIBES TO:
 * - AppointmentCreatedEvent
 *
 * JOBS DISPATCHED:
 * - SyncToCalcomJob (async)
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class CalcomSyncListener implements EventListener
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

        Log::info('🔄 CalcomSyncListener: Syncing appointment to Cal.com', [
            'appointmentId' => $event->appointmentId,
            'staffId' => $event->staffId,
            'correlationId' => $event->correlationId,
        ]);

        try {
            // Dispatch async job to sync with Cal.com
            // This job will handle the actual Cal.com API call
            dispatch(new SyncToCalcomJob(
                appointmentId: $event->appointmentId,
                customerId: $event->customerId,
                staffId: $event->staffId,
                serviceId: $event->serviceId,
                branchId: $event->branchId,
                appointmentStart: $event->appointmentStart,
                appointmentEnd: $event->appointmentEnd,
                correlationId: $event->correlationId,
            ));

            Log::info('✅ CalcomSyncListener: Sync job dispatched', [
                'appointmentId' => $event->appointmentId,
                'correlationId' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ CalcomSyncListener: Error dispatching sync job', [
                'appointmentId' => $event->appointmentId,
                'error' => $e->getMessage(),
                'correlationId' => $event->correlationId,
            ]);
        }
    }

    public function priority(): int
    {
        // Sync to Cal.com with high priority (critical path)
        return 200;
    }

    public function isAsync(): bool
    {
        // Dispatch job asynchronously (but prioritize it)
        return true;
    }
}
