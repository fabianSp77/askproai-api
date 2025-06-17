<?php

namespace App\Jobs\Appointment;

use App\Models\Appointment;
use App\Services\NotificationService;
use App\Services\Logging\StructuredLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendAppointmentNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120]; // 30s, 1min, 2min

    protected Appointment $appointment;
    protected array $channels;
    protected string $correlationId;
    protected string $notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Appointment $appointment,
        array $channels = ['email'],
        string $notificationType = 'confirmation',
        ?string $correlationId = null
    ) {
        $this->appointment = $appointment;
        $this->channels = $channels;
        $this->notificationType = $notificationType;
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid();
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        $logger->setCorrelationId($this->correlationId);
        
        $logger->logBookingFlow('notifications_started', [
            'appointment_id' => $this->appointment->id,
            'channels' => $this->channels,
            'type' => $this->notificationType,
            'attempt' => $this->attempts(),
        ]);

        $successfulChannels = [];
        $failedChannels = [];
        $errors = [];

        foreach ($this->channels as $channel) {
            try {
                $this->sendNotification($channel, $notificationService, $logger);
                $successfulChannels[] = $channel;
            } catch (\Exception $e) {
                $failedChannels[] = $channel;
                $errors[$channel] = $e->getMessage();
                
                $logger->warning("Failed to send {$channel} notification", [
                    'appointment_id' => $this->appointment->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log notification results
        $this->logNotificationResults($successfulChannels, $failedChannels, $errors);

        // If all channels failed, throw exception to trigger retry
        if (empty($successfulChannels) && !empty($failedChannels)) {
            throw new \Exception('All notification channels failed: ' . json_encode($errors));
        }

        $logger->logBookingFlow('notifications_completed', [
            'appointment_id' => $this->appointment->id,
            'successful_channels' => $successfulChannels,
            'failed_channels' => $failedChannels,
        ]);
    }

    /**
     * Send notification through specific channel
     */
    protected function sendNotification(
        string $channel,
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        switch ($channel) {
            case 'email':
                $this->sendEmailNotification($notificationService, $logger);
                break;
                
            case 'sms':
                $this->sendSmsNotification($notificationService, $logger);
                break;
                
            case 'whatsapp':
                $this->sendWhatsAppNotification($notificationService, $logger);
                break;
                
            case 'staff':
                $this->sendStaffNotification($notificationService, $logger);
                break;
                
            default:
                throw new \InvalidArgumentException("Unknown notification channel: {$channel}");
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        if (!$this->appointment->customer->email) {
            $logger->info('Skipping email notification - no email address', [
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
            ]);
            return;
        }

        switch ($this->notificationType) {
            case 'confirmation':
                $notificationService->sendAppointmentConfirmation($this->appointment);
                break;
                
            case 'reminder':
                $notificationService->sendAppointmentReminder($this->appointment);
                break;
                
            case 'reschedule':
                $notificationService->sendRescheduleNotification($this->appointment);
                break;
                
            case 'cancellation':
                $notificationService->sendCancellationNotification($this->appointment);
                break;
                
            default:
                $notificationService->sendAppointmentConfirmation($this->appointment);
        }
    }

    /**
     * Send SMS notification
     */
    protected function sendSmsNotification(
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        if (!$this->appointment->customer->phone) {
            $logger->info('Skipping SMS notification - no phone number', [
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
            ]);
            return;
        }

        // Check if SMS is enabled for the company
        if (!$this->appointment->company->settings['sms_enabled'] ?? false) {
            $logger->info('Skipping SMS notification - SMS not enabled for company', [
                'appointment_id' => $this->appointment->id,
                'company_id' => $this->appointment->company_id,
            ]);
            return;
        }

        $notificationService->sendAppointmentSms($this->appointment, $this->notificationType);
    }

    /**
     * Send WhatsApp notification
     */
    protected function sendWhatsAppNotification(
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        if (!$this->appointment->customer->phone) {
            $logger->info('Skipping WhatsApp notification - no phone number', [
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
            ]);
            return;
        }

        // Check if WhatsApp is enabled for the company
        if (!$this->appointment->company->settings['whatsapp_enabled'] ?? false) {
            $logger->info('Skipping WhatsApp notification - WhatsApp not enabled for company', [
                'appointment_id' => $this->appointment->id,
                'company_id' => $this->appointment->company_id,
            ]);
            return;
        }

        $notificationService->sendWhatsAppNotification($this->appointment, $this->notificationType);
    }

    /**
     * Send staff notification
     */
    protected function sendStaffNotification(
        NotificationService $notificationService,
        StructuredLogger $logger
    ): void {
        if (!$this->appointment->staff || !$this->appointment->staff->email) {
            $logger->info('Skipping staff notification - no staff or email', [
                'appointment_id' => $this->appointment->id,
                'staff_id' => $this->appointment->staff_id,
            ]);
            return;
        }

        $notificationService->notifyStaffNewAppointment($this->appointment);
    }

    /**
     * Log notification results to database
     */
    protected function logNotificationResults(
        array $successfulChannels,
        array $failedChannels,
        array $errors
    ): void {
        try {
            DB::table('notification_logs')->insert([
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
                'company_id' => $this->appointment->company_id,
                'type' => $this->notificationType,
                'channels' => json_encode($this->channels),
                'successful_channels' => json_encode($successfulChannels),
                'failed_channels' => json_encode($failedChannels),
                'errors' => json_encode($errors),
                'status' => empty($failedChannels) ? 'sent' : (empty($successfulChannels) ? 'failed' : 'partial'),
                'sent_at' => empty($successfulChannels) ? null : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification results', [
                'error' => $e->getMessage(),
                'appointment_id' => $this->appointment->id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job permanently failed', [
            'appointment_id' => $this->appointment->id,
            'type' => $this->notificationType,
            'channels' => $this->channels,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Log failure in database
        $this->logNotificationResults([], $this->channels, [
            'all' => 'Job failed: ' . $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'appointment:' . $this->appointment->id,
            'company:' . $this->appointment->company_id,
            'notification:' . $this->notificationType,
        ];
    }
}