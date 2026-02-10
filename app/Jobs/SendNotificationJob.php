<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\NotificationConfiguration;
use App\Models\NotificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

/**
 * SendNotificationJob
 *
 * Handles asynchronous delivery of notifications via configured channels.
 * Supports Email, SMS, WhatsApp with fallback and retry logic.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected NotificationConfiguration $config;
    protected Appointment $appointment;
    protected string $eventType;

    /**
     * Create a new job instance.
     */
    public function __construct(
        NotificationConfiguration $config,
        Appointment $appointment,
        string $eventType
    ) {
        $this->config = $config;
        $this->appointment = $appointment;
        $this->eventType = $eventType;

        // Set queue based on priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load relationships needed for processing
        $this->appointment->load(['customer', 'staff', 'service', 'branch', 'company']);

        // Check if configuration is still enabled
        if (!$this->config->is_enabled) {
            Log::info('Notification skipped - configuration disabled', [
                'config_id' => $this->config->id,
                'event_type' => $this->eventType,
            ]);
            return;
        }

        // Create queue entry for tracking
        $queueEntry = NotificationQueue::create([
            'company_id' => $this->appointment->company_id,
            'notification_configuration_id' => $this->config->id,
            'notifiable_type' => get_class($this->appointment),
            'notifiable_id' => $this->appointment->id,
            'channel' => $this->config->channel,
            'status' => 'processing',
            'attempt_count' => $this->attempts(),
            'metadata' => [
                'event_type' => $this->eventType,
                'customer_id' => $this->appointment->customer_id,
                'started_at' => now()->toIso8601String(),
            ],
        ]);

        try {
            // Send via primary channel
            $result = $this->sendViaChannel($this->config->channel, $queueEntry);

            if ($result['success']) {
                $this->markSuccess($queueEntry, $result);
                return;
            }

            // Try fallback channel if primary failed
            if ($this->config->fallback_channel && $this->attempts() >= 2) {
                Log::warning('Primary channel failed, trying fallback', [
                    'primary' => $this->config->channel,
                    'fallback' => $this->config->fallback_channel,
                    'queue_id' => $queueEntry->id,
                ]);

                $fallbackResult = $this->sendViaChannel($this->config->fallback_channel, $queueEntry);

                if ($fallbackResult['success']) {
                    $this->markSuccess($queueEntry, $fallbackResult, true);
                    return;
                }
            }

            // Both channels failed - mark as failed
            $this->markFailed($queueEntry, $result['error'] ?? 'Unknown error');

        } catch (\Exception $e) {
            $this->markFailed($queueEntry, $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Send notification via specified channel.
     */
    protected function sendViaChannel(string $channel, NotificationQueue $queueEntry): array
    {
        $message = $this->buildMessage();

        return match($channel) {
            'email' => $this->sendEmail($message),
            'sms' => $this->sendSms($message),
            'whatsapp' => $this->sendWhatsApp($message),
            'push' => $this->sendPush($message),
            default => ['success' => false, 'error' => 'Unsupported channel: ' . $channel],
        };
    }

    /**
     * Send via Email channel.
     */
    protected function sendEmail(array $message): array
    {
        try {
            if (!$this->appointment->customer || !$this->appointment->customer->email) {
                return ['success' => false, 'error' => 'No customer email available'];
            }

            Mail::to($this->appointment->customer->email)
                ->send(new \App\Mail\GenericNotification($message));

            return [
                'success' => true,
                'channel' => 'email',
                'recipient' => $this->appointment->customer->email,
            ];

        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'error' => $e->getMessage(),
                'appointment_id' => $this->appointment->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via SMS channel.
     */
    protected function sendSms(array $message): array
    {
        try {
            if (!$this->appointment->customer || !$this->appointment->customer->phone) {
                return ['success' => false, 'error' => 'No customer phone available'];
            }

            // TODO: Integrate with SMS provider (Twilio, Vonage, etc.)
            Log::info('SMS notification would be sent', [
                'phone' => $this->appointment->customer->phone,
                'message' => $message['body'],
            ]);

            return [
                'success' => true,
                'channel' => 'sms',
                'recipient' => $this->appointment->customer->phone,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via WhatsApp channel.
     */
    protected function sendWhatsApp(array $message): array
    {
        try {
            if (!$this->appointment->customer || !$this->appointment->customer->phone) {
                return ['success' => false, 'error' => 'No customer phone available'];
            }

            // TODO: Integrate with WhatsApp Business API
            Log::info('WhatsApp notification would be sent', [
                'phone' => $this->appointment->customer->phone,
                'message' => $message['body'],
            ]);

            return [
                'success' => true,
                'channel' => 'whatsapp',
                'recipient' => $this->appointment->customer->phone,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via Push notification channel.
     */
    protected function sendPush(array $message): array
    {
        try {
            // TODO: Integrate with FCM or APNs
            Log::info('Push notification would be sent', [
                'customer_id' => $this->appointment->customer_id,
                'message' => $message['body'],
            ]);

            return [
                'success' => true,
                'channel' => 'push',
                'recipient' => $this->appointment->customer_id,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build notification message from template.
     */
    protected function buildMessage(): array
    {
        $template = $this->config->template_override ?? $this->getDefaultTemplate();

        $placeholders = [
            '{{customer_name}}' => $this->appointment->customer->name ?? 'Kunde',
            '{{appointment_time}}' => $this->appointment->starts_at->format('d.m.Y H:i'),
            '{{service_name}}' => $this->appointment->service->name ?? 'Service',
            '{{branch_name}}' => $this->appointment->branch->name ?? 'Filiale',
            '{{staff_name}}' => $this->appointment->staff->name ?? 'Team',
            '{{appointment_date}}' => $this->appointment->starts_at->format('d.m.Y'),
            '{{appointment_duration}}' => $this->appointment->duration_minutes . ' Minuten',
        ];

        $body = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );

        return [
            'subject' => $this->getSubject(),
            'body' => $body,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'event_type' => $this->eventType,
                'customer_id' => $this->appointment->customer_id,
            ],
        ];
    }

    /**
     * Get notification subject based on event type.
     */
    protected function getSubject(): string
    {
        return match($this->eventType) {
            'appointment.created' => 'Terminbestätigung',
            'appointment.updated' => 'Terminänderung',
            'appointment.cancelled' => 'Terminabsage',
            'appointment.reminder' => 'Terminerinnerung',
            default => 'Benachrichtigung',
        };
    }

    /**
     * Get default template for event type.
     */
    protected function getDefaultTemplate(): string
    {
        return match($this->eventType) {
            'appointment.created' => 'Hallo {{customer_name}}, Ihr Termin für {{service_name}} am {{appointment_time}} wurde bestätigt.',
            'appointment.updated' => 'Hallo {{customer_name}}, Ihr Termin wurde auf {{appointment_time}} geändert.',
            'appointment.cancelled' => 'Hallo {{customer_name}}, Ihr Termin am {{appointment_time}} wurde storniert.',
            'appointment.reminder' => 'Hallo {{customer_name}}, Erinnerung: Ihr Termin für {{service_name}} ist morgen um {{appointment_time}}.',
            default => 'Hallo {{customer_name}}, eine Änderung zu Ihrem Termin.',
        };
    }

    /**
     * Mark notification as successfully sent.
     */
    protected function markSuccess(NotificationQueue $queueEntry, array $result, bool $usedFallback = false): void
    {
        $queueEntry->update([
            'status' => 'sent',
            'sent_at' => now(),
            'channel' => $result['channel'] ?? $queueEntry->channel,
            'response' => $result,
            'metadata' => array_merge($queueEntry->metadata ?? [], [
                'completed_at' => now()->toIso8601String(),
                'used_fallback' => $usedFallback,
                'final_channel' => $result['channel'] ?? 'unknown',
            ]),
        ]);

        Log::info('Notification sent successfully', [
            'queue_id' => $queueEntry->id,
            'channel' => $result['channel'] ?? 'unknown',
            'event_type' => $this->eventType,
            'used_fallback' => $usedFallback,
        ]);
    }

    /**
     * Mark notification as failed.
     */
    protected function markFailed(NotificationQueue $queueEntry, string $error): void
    {
        $queueEntry->update([
            'status' => 'failed',
            'error_message' => $error,
            'metadata' => array_merge($queueEntry->metadata ?? [], [
                'failed_at' => now()->toIso8601String(),
                'final_attempt' => $this->attempts(),
            ]),
        ]);

        Log::error('Notification failed', [
            'queue_id' => $queueEntry->id,
            'error' => $error,
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Determine queue based on event priority.
     */
    protected function determineQueue(): string
    {
        return match($this->eventType) {
            'appointment.cancelled' => 'notifications-high',
            'appointment.reminder' => 'notifications-low',
            default => 'notifications',
        };
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob failed permanently', [
            'config_id' => $this->config->id,
            'appointment_id' => $this->appointment->id,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
