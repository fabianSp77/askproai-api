<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentCancellationRequested;
use App\Services\Notifications\NotificationManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send notifications when appointment is cancelled
 *
 * Notifications sent to:
 * - Customer (confirmation)
 * - Assigned staff (if any)
 * - Branch managers (if configured)
 */
class SendCancellationNotifications implements ShouldQueue
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
    public function handle(AppointmentCancellationRequested $event): void
    {
        try {
            $appointment = $event->appointment;
            $customer = $event->customer;

            Log::info('📧 Sending cancellation notifications', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'fee' => $event->fee,
            ]);

            // Prepare notification data
            $data = [
                'appointment_id' => $appointment->id,
                'customer_name' => $customer->name,
                'service_name' => $appointment->service?->name,
                'appointment_date' => $appointment->starts_at->format('d.m.Y'),
                'appointment_time' => $appointment->starts_at->format('H:i'),
                'cancellation_reason' => $event->reason,
                'fee' => $event->fee,
                'within_policy' => $event->withinPolicy,
            ];

            // Send to customer
            $this->notificationManager->sendAppointmentCancelled(
                customer: $customer,
                appointmentData: $data,
                channel: $customer->preferred_contact_method ?? 'email'
            );

            // Send to assigned staff (if exists)
            if ($appointment->staff) {
                $this->notificationManager->notifyStaffOfCancellation(
                    staff: $appointment->staff,
                    appointmentData: $data
                );
            }

            // ADR-005: ALWAYS notify branch (non-blocking policy, but branch needs to know)
            $this->notifyBranch($appointment, $data);

            Log::info('✅ Cancellation notifications sent successfully', [
                'appointment_id' => $appointment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to send cancellation notifications', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw for queue retry
            throw $e;
        }
    }

    /**
     * Notify branch of cancellation (ADR-005: Always notify, dual channel)
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
            'branch_notif_cancel_%s_%s_%s',
            $branch->id,
            $appointment->id,
            $timeBucket
        );

        if (\Illuminate\Support\Facades\Cache::has($idempotencyKey)) {
            Log::info('⏭️ Skipping duplicate branch notification (idempotent)', [
                'appointment_id' => $appointment->id,
                'branch_id' => $branch->id,
                'idempotency_key' => $idempotencyKey
            ]);
            return;
        }

        // Mark as sent (1-hour TTL)
        \Illuminate\Support\Facades\Cache::put($idempotencyKey, true, now()->addHour());

        // Channel 1: Email to branch managers
        $managers = $branch
            ->staff()
            ->where('role', 'manager')
            ->orWhere('role', 'admin')
            ->get();

        foreach ($managers as $manager) {
            try {
                $this->notificationManager->notifyManagerOfCancellation(
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
                ->title('Termin storniert')
                ->body(sprintf(
                    '%s hat den Termin am %s um %s Uhr storniert. Service: %s',
                    $data['customer_name'],
                    $data['appointment_date'],
                    $data['appointment_time'],
                    $data['service_name'] ?? 'Unbekannt'
                ))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
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
    public function failed(AppointmentCancellationRequested $event, \Throwable $exception): void
    {
        Log::error('🔥 Cancellation notification job permanently failed', [
            'appointment_id' => $event->appointment->id,
            'customer_id' => $event->customer->id,
            'error' => $exception->getMessage(),
        ]);

        // Could trigger fallback notification or alert here
    }
}
