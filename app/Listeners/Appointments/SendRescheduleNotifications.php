<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentRescheduled;
use App\Services\Notifications\NotificationManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Send notifications when appointment is rescheduled
 *
 * ADR-005: Always notify branch via dual channel (Email + Filament UI)
 * Idempotent per (booking_id, action, time-bucket)
 */
class SendRescheduleNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        private NotificationManager $notificationManager
    ) {
    }

    /**
     * Handle the event
     */
    public function handle(AppointmentRescheduled $event): void
    {
        try {
            $appointment = $event->appointment;
            $customer = $appointment->customer;

            Log::info('📧 Sending reschedule notifications', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'old_time' => $event->oldStartTime->format('Y-m-d H:i'),
                'new_time' => $event->newStartTime->format('Y-m-d H:i'),
            ]);

            // Prepare notification data
            $data = [
                'appointment_id' => $appointment->id,
                'customer_name' => $customer->name,
                'service_name' => $appointment->service?->name,
                'old_date' => $event->oldStartTime->format('d.m.Y'),
                'old_time' => $event->oldStartTime->format('H:i'),
                'new_date' => $event->newStartTime->format('d.m.Y'),
                'new_time' => $event->newStartTime->format('H:i'),
                'reason' => $event->reason,
                'fee' => $event->fee,
                'within_policy' => $event->withinPolicy,
            ];

            // Send to customer
            $this->notificationManager->sendAppointmentRescheduled(
                customer: $customer,
                appointmentData: $data,
                channel: $customer->preferred_contact_method ?? 'email'
            );

            // Send to assigned staff (if exists)
            if ($appointment->staff) {
                $this->notificationManager->notifyStaffOfReschedule(
                    staff: $appointment->staff,
                    appointmentData: $data
                );
            }

            // ADR-005: ALWAYS notify branch (non-blocking policy, but branch needs to know)
            $this->notifyBranch($appointment, $data);

            Log::info('✅ Reschedule notifications sent successfully', [
                'appointment_id' => $appointment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to send reschedule notifications', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw for queue retry
            throw $e;
        }
    }

    /**
     * Notify branch of reschedule (ADR-005: Always notify, dual channel)
     *
     * Channels: Email + Filament UI
     * Idempotent: Per (booking_id, action, time-bucket)
     */
    private function notifyBranch($appointment, array $data): void
    {
        if (!$appointment->branch) {
            Log::warning('Cannot notify branch - no branch assigned', [
                'appointment_id' => $appointment->id
            ]);
            return;
        }

        $branch = $appointment->branch;

        // Idempotency check: Prevent duplicate notifications within same hour
        $timeBucket = now()->format('YmdH'); // e.g., 2025110314 for 2025-11-03 14:00
        $idempotencyKey = sprintf(
            'branch_notif_reschedule_%s_%s_%s',
            $branch->id,
            $appointment->id,
            $timeBucket
        );

        if (Cache::has($idempotencyKey)) {
            Log::info('⏭️ Skipping duplicate branch notification (idempotent)', [
                'appointment_id' => $appointment->id,
                'branch_id' => $branch->id,
                'idempotency_key' => $idempotencyKey
            ]);
            return;
        }

        // Mark as sent (1-hour TTL)
        Cache::put($idempotencyKey, true, now()->addHour());

        // Channel 1: Email to branch managers
        $managers = $branch
            ->staff()
            ->where('role', 'manager')
            ->orWhere('role', 'admin')
            ->get();

        foreach ($managers as $manager) {
            try {
                $this->notificationManager->notifyManagerOfReschedule(
                    manager: $manager,
                    appointmentData: $data
                );
                Log::info('📧 Email notification sent to manager', [
                    'manager_id' => $manager->id,
                    'manager_email' => $manager->email
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send email to manager', [
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other managers
            }
        }

        // Channel 2: Filament UI notification
        try {
            \Filament\Notifications\Notification::make()
                ->title('Termin umgebucht')
                ->body(sprintf(
                    '%s hat den Termin umgebucht. Alt: %s %s → Neu: %s %s. Service: %s',
                    $data['customer_name'],
                    $data['old_date'],
                    $data['old_time'],
                    $data['new_date'],
                    $data['new_time'],
                    $data['service_name'] ?? 'Unbekannt'
                ))
                ->icon('heroicon-o-arrow-path')
                ->iconColor('warning')
                ->sendToDatabase(\App\Models\User::whereHas('staff', function($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })->get());

            Log::info('🔔 Filament UI notification sent', [
                'branch_id' => $branch->id,
                'appointment_id' => $appointment->id
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send Filament UI notification', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed job
     */
    public function failed(AppointmentRescheduled $event, \Throwable $exception): void
    {
        Log::error('🔥 Reschedule notification job permanently failed', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
        ]);

        // Could trigger fallback notification or alert here
    }
}
