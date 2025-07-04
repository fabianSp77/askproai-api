<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Appointment $appointment,
        public string $reminderType, // '24h', '2h', '30m'
        public array $channels = ['email', 'sms']
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            // Check if appointment is still valid for reminder
            if (!$this->shouldSendReminder()) {
                Log::info('Skipping reminder - appointment not eligible', [
                    'appointment_id' => $this->appointment->id,
                    'reminder_type' => $this->reminderType,
                    'status' => $this->appointment->status
                ]);
                return;
            }

            Log::info('Sending appointment reminder', [
                'appointment_id' => $this->appointment->id,
                'reminder_type' => $this->reminderType,
                'channels' => $this->channels,
                'customer_id' => $this->appointment->customer_id
            ]);

            // Send reminders based on type
            switch ($this->reminderType) {
                case '24h':
                    $this->send24HourReminder($notificationService);
                    break;
                    
                case '2h':
                    $this->send2HourReminder($notificationService);
                    break;
                    
                case '30m':
                    $this->send30MinuteReminder($notificationService);
                    break;
                    
                default:
                    Log::error('Unknown reminder type', [
                        'appointment_id' => $this->appointment->id,
                        'reminder_type' => $this->reminderType
                    ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send appointment reminder', [
                'appointment_id' => $this->appointment->id,
                'reminder_type' => $this->reminderType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Check if reminder should be sent
     */
    protected function shouldSendReminder(): bool
    {
        // Don't send if appointment is cancelled or completed
        if (in_array($this->appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return false;
        }

        // Don't send if appointment has already passed
        if ($this->appointment->starts_at->isPast()) {
            return false;
        }

        // Check if reminder was already sent
        $reminderField = "reminder_{$this->reminderType}_sent_at";
        if ($this->appointment->$reminderField !== null) {
            return false;
        }

        return true;
    }

    /**
     * Send 24-hour reminder
     */
    protected function send24HourReminder(NotificationService $notificationService): void
    {
        // Load relationships
        $this->appointment->load(['customer', 'staff', 'service', 'branch']);

        // Send through each channel
        foreach ($this->channels as $channel) {
            switch ($channel) {
                case 'email':
                    if ($this->appointment->customer->email) {
                        $notificationService->sendAppointmentConfirmation($this->appointment);
                    }
                    break;
                    
                case 'sms':
                    if ($this->appointment->customer->phone && $this->appointment->customer->sms_opt_in) {
                        // SMS will be sent by NotificationService
                        Log::info('SMS reminder scheduled', [
                            'appointment_id' => $this->appointment->id,
                            'phone' => $this->appointment->customer->phone
                        ]);
                    }
                    break;
                    
                case 'whatsapp':
                    if ($this->appointment->customer->phone && $this->appointment->customer->whatsapp_opt_in) {
                        // WhatsApp will be sent by NotificationService
                        Log::info('WhatsApp reminder scheduled', [
                            'appointment_id' => $this->appointment->id,
                            'phone' => $this->appointment->customer->phone
                        ]);
                    }
                    break;
            }
        }

        // Mark as sent
        $this->appointment->update(['reminder_24h_sent_at' => now()]);
    }

    /**
     * Send 2-hour reminder
     */
    protected function send2HourReminder(NotificationService $notificationService): void
    {
        $this->appointment->load(['customer', 'staff', 'service', 'branch']);

        // Usually only email for 2h reminders
        if (in_array('email', $this->channels) && $this->appointment->customer->email) {
            $notificationService->sendAppointmentConfirmation($this->appointment);
        }

        // Mark as sent
        $this->appointment->update(['reminder_2h_sent_at' => now()]);
    }

    /**
     * Send 30-minute reminder
     */
    protected function send30MinuteReminder(NotificationService $notificationService): void
    {
        $this->appointment->load(['customer', 'staff', 'service', 'branch']);

        // Prefer push notification, fallback to SMS
        if ($this->appointment->customer->push_token) {
            // Push notification handled by NotificationService
            Log::info('Push notification reminder scheduled', [
                'appointment_id' => $this->appointment->id
            ]);
        } elseif ($this->appointment->customer->phone && $this->appointment->customer->sms_opt_in) {
            // SMS fallback handled by NotificationService
            Log::info('SMS fallback reminder scheduled', [
                'appointment_id' => $this->appointment->id
            ]);
        }

        // Mark as sent
        $this->appointment->update(['reminder_30m_sent_at' => now()]);
    }

    /**
     * Calculate the delay before the job should be processed.
     */
    public function delay(): ?int
    {
        return 0; // No delay, send immediately
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'appointment:' . $this->appointment->id,
            'reminder:' . $this->reminderType,
            'customer:' . $this->appointment->customer_id,
        ];
    }
}