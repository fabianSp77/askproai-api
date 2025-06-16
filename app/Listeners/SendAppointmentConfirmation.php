<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Mail\AppointmentConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAppointmentConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'notifications';

    /**
     * The time (seconds) before the job should be processed.
     */
    public int $delay = 0;

    /**
     * Handle the event.
     */
    public function handle(AppointmentCreated $event): void
    {
        $appointment = $event->appointment;
        $customer = $appointment->customer;

        // Skip if no email
        if (!$customer->email) {
            Log::info('Skipping appointment confirmation - no customer email', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
            ]);
            return;
        }

        try {
            Mail::to($customer->email)
                ->send(new AppointmentConfirmationMail($appointment));

            Log::info('Appointment confirmation sent', [
                'appointment_id' => $appointment->id,
                'customer_email' => $customer->email,
            ]);

            // Update appointment to mark email sent
            $appointment->update([
                'confirmation_sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send appointment confirmation', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            
            // Retry job
            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 1 minute
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(AppointmentCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to send appointment confirmation after all retries', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}