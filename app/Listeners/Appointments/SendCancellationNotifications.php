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

            // Send to branch managers if policy violation or fee charged
            if (!$event->withinPolicy || $event->fee > 0) {
                $this->notifyBranchManagers($appointment, $data);
            }

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
     * Notify branch managers of cancellation
     */
    private function notifyBranchManagers($appointment, array $data): void
    {
        if (!$appointment->branch) {
            return;
        }

        $managers = $appointment->branch
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
            } catch (\Exception $e) {
                Log::warning('Failed to notify manager', [
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other managers
            }
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
