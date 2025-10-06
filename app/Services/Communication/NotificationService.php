<?php

namespace App\Services\Communication;

use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentCancellation;
use App\Mail\AppointmentReminder;

class NotificationService
{
    private IcsGeneratorService $icsGenerator;

    public function __construct(IcsGeneratorService $icsGenerator)
    {
        $this->icsGenerator = $icsGenerator;
    }

    /**
     * Send composite appointment confirmation
     */
    public function sendCompositeConfirmation(Appointment $appointment): bool
    {
        try {
            if (!$appointment->customer || !$appointment->customer->email) {
                Log::warning('No customer email for appointment', ['id' => $appointment->id]);
                return false;
            }

            // Generate ICS file
            $icsContent = $this->icsGenerator->generateCompositeIcs($appointment);

            // Prepare email data (minimal PII)
            $emailData = [
                'customerName' => $appointment->customer->name,
                'serviceName' => $appointment->service->name,
                'branchName' => $appointment->branch->name,
                'branchAddress' => $appointment->branch->address,
                'startsAt' => $appointment->starts_at,
                'endsAt' => $appointment->ends_at,
                'isComposite' => $appointment->is_composite,
                'totalDuration' => $appointment->starts_at->diffInMinutes($appointment->ends_at),
                'confirmationCode' => substr($appointment->composite_group_uid ?? $appointment->id, 0, 8)
            ];

            // Send email with ICS attachment
            Mail::to($appointment->customer->email)
                ->queue(new AppointmentConfirmation($emailData, $icsContent));

            // Log success (without PII)
            Log::info('Confirmation email queued', [
                'appointment_id' => $appointment->id,
                'is_composite' => $appointment->is_composite
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send confirmation', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send simple appointment confirmation
     */
    public function sendSimpleConfirmation(Appointment $appointment): bool
    {
        if ($appointment->is_composite) {
            return $this->sendCompositeConfirmation($appointment);
        }

        try {
            $emailData = [
                'customerName' => $appointment->customer->name,
                'serviceName' => $appointment->service->name,
                'branchName' => $appointment->branch->name,
                'branchAddress' => $appointment->branch->address,
                'startsAt' => $appointment->starts_at,
                'endsAt' => $appointment->ends_at,
                'staffName' => $appointment->staff->name ?? 'Team',
                'confirmationCode' => substr($appointment->id, 0, 8)
            ];

            // Generate ICS
            $icsContent = $this->icsGenerator->generateSimpleIcs($appointment);

            Mail::to($appointment->customer->email)
                ->queue(new AppointmentConfirmation($emailData, $icsContent));

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send simple confirmation', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send cancellation notification
     */
    public function sendCancellationNotification(Appointment $appointment): bool
    {
        try {
            if (!$appointment->customer || !$appointment->customer->email) {
                return false;
            }

            $emailData = [
                'customerName' => $appointment->customer->name,
                'serviceName' => $appointment->service->name,
                'originalDate' => $appointment->starts_at->format('d.m.Y'),
                'originalTime' => $appointment->starts_at->format('H:i'),
                'confirmationCode' => substr($appointment->composite_group_uid ?? $appointment->id, 0, 8)
            ];

            Mail::to($appointment->customer->email)
                ->queue(new AppointmentCancellation($emailData));

            Log::info('Cancellation email queued', [
                'appointment_id' => $appointment->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send reminder notification
     */
    public function sendReminder(Appointment $appointment, int $hoursBeforeStart = 24): bool
    {
        try {
            // Check reminder policy
            $policy = $appointment->service->reminder_policy ?? 'single';

            if ($policy === 'single') {
                // Only send one reminder for entire appointment
                if ($appointment->metadata['reminder_sent'] ?? false) {
                    return false;
                }
            }

            $emailData = [
                'customerName' => $appointment->customer->name,
                'serviceName' => $appointment->service->name,
                'branchName' => $appointment->branch->name,
                'branchAddress' => $appointment->branch->address,
                'startsAt' => $appointment->starts_at,
                'hoursUntil' => $hoursBeforeStart,
                'confirmationCode' => substr($appointment->composite_group_uid ?? $appointment->id, 0, 8)
            ];

            Mail::to($appointment->customer->email)
                ->queue(new AppointmentReminder($emailData));

            // Mark reminder as sent
            $appointment->update([
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'reminder_sent' => true,
                    'reminder_sent_at' => now()->toIso8601String(),
                    'reminder_type' => "{$hoursBeforeStart}h"
                ])
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send reminder', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification (optional)
     */
    public function sendSmsNotification(Appointment $appointment, string $type = 'confirmation'): bool
    {
        // Check if SMS is enabled for this service
        if (!$appointment->service->reminder_policy ||
            !($appointment->service->metadata['sms_enabled'] ?? false)) {
            return false;
        }

        // Check if customer has phone number
        if (!$appointment->customer->phone) {
            return false;
        }

        try {
            $message = $this->buildSmsMessage($appointment, $type);

            // TODO: Integrate with SMS provider (Twilio, Vonage, etc.)
            Log::info('SMS would be sent', [
                'appointment_id' => $appointment->id,
                'type' => $type,
                'message_length' => strlen($message)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build SMS message
     */
    private function buildSmsMessage(Appointment $appointment, string $type): string
    {
        $date = $appointment->starts_at->format('d.m.');
        $time = $appointment->starts_at->format('H:i');
        $branch = $appointment->branch->name;

        switch ($type) {
            case 'confirmation':
                return "Terminbestätigung: {$appointment->service->name} am {$date} um {$time} Uhr bei {$branch}. Code: " .
                       substr($appointment->composite_group_uid ?? $appointment->id, 0, 8);

            case 'reminder':
                return "Terminerinnerung: Morgen {$time} Uhr - {$appointment->service->name} bei {$branch}";

            case 'cancellation':
                return "Ihr Termin am {$date} wurde storniert. Bei Fragen kontaktieren Sie uns bitte.";

            default:
                return "Termininformation für {$date} um {$time} Uhr";
        }
    }

    /**
     * Bulk send reminders for upcoming appointments
     */
    public function sendBulkReminders(int $hoursBeforeStart = 24): int
    {
        $targetTime = now()->addHours($hoursBeforeStart);

        $appointments = Appointment::where('status', 'booked')
            ->whereBetween('starts_at', [
                $targetTime->copy()->subMinutes(30),
                $targetTime->copy()->addMinutes(30)
            ])
            ->whereRaw("NOT JSON_CONTAINS(metadata, '\"reminder_sent\": true', '$')")
            ->get();

        $sent = 0;
        foreach ($appointments as $appointment) {
            if ($this->sendReminder($appointment, $hoursBeforeStart)) {
                $sent++;
            }
        }

        Log::info('Bulk reminders sent', [
            'count' => $sent,
            'hours_before' => $hoursBeforeStart
        ]);

        return $sent;
    }
}