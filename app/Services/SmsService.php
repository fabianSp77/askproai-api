<?php

namespace App\Services;

use Twilio\Rest\Client;
use App\Models\Customer;
use App\Models\SmsMessageLog;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    protected Client $twilioClient;
    protected string $fromNumber;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('services.twilio.enabled', false);

        if ($this->enabled) {
            $this->twilioClient = new Client(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );
            $this->fromNumber = config('services.twilio.from_number');
        }
    }

    /**
     * Send SMS to a customer
     */
    public function sendToCustomer(Customer $customer, string $message, array $options = []): ?SmsMessageLog
    {
        if (!$this->enabled) {
            Log::info('SMS service is disabled. Message not sent.', [
                'customer_id' => $customer->id,
                'message' => $message
            ]);
            return null;
        }

        if (!$customer->phone) {
            Log::warning('Customer has no phone number', ['customer_id' => $customer->id]);
            return null;
        }

        return $this->send($customer->phone, $message, array_merge($options, [
            'customer_id' => $customer->id,
            'recipient_type' => 'customer'
        ]));
    }

    /**
     * Send SMS to a phone number
     */
    public function send(string $to, string $message, array $options = []): ?SmsMessageLog
    {
        if (!$this->enabled) {
            Log::info('SMS service is disabled. Message not sent.', [
                'to' => $to,
                'message' => $message
            ]);
            return null;
        }

        // Normalize phone number
        $to = $this->normalizePhoneNumber($to);

        // Create log entry
        $smsLog = SmsMessageLog::create([
            'to' => $to,
            'from' => $this->fromNumber,
            'message' => $message,
            'customer_id' => $options['customer_id'] ?? null,
            'appointment_id' => $options['appointment_id'] ?? null,
            'recipient_type' => $options['recipient_type'] ?? 'general',
            'message_type' => $options['message_type'] ?? 'notification',
            'status' => 'pending',
            'metadata' => $options['metadata'] ?? [],
        ]);

        try {
            // Send via Twilio
            $twilioMessage = $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                    'statusCallback' => $options['status_callback'] ?? null,
                ]
            );

            // Update log with success
            $smsLog->update([
                'status' => 'sent',
                'provider_message_id' => $twilioMessage->sid,
                'sent_at' => now(),
                'provider_response' => [
                    'sid' => $twilioMessage->sid,
                    'status' => $twilioMessage->status,
                    'price' => $twilioMessage->price,
                    'price_unit' => $twilioMessage->priceUnit,
                ]
            ]);

            Log::info('SMS sent successfully', [
                'to' => $to,
                'sid' => $twilioMessage->sid
            ]);

            return $smsLog;

        } catch (Exception $e) {
            // Update log with failure
            $smsLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            Log::error('Failed to send SMS', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulk(array $recipients, string $message, array $options = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            try {
                if (is_string($recipient)) {
                    $results[] = $this->send($recipient, $message, $options);
                } elseif ($recipient instanceof Customer) {
                    $results[] = $this->sendToCustomer($recipient, $message, $options);
                }
            } catch (Exception $e) {
                $results[] = [
                    'error' => true,
                    'recipient' => $recipient,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder($appointment): ?SmsMessageLog
    {
        if (!$appointment->customer || !$appointment->customer->phone) {
            return null;
        }

        $message = sprintf(
            "Reminder: You have an appointment on %s at %s. Location: %s",
            $appointment->starts_at->format('M j'),
            $appointment->starts_at->format('g:i A'),
            $appointment->branch->name ?? 'Main Office'
        );

        return $this->sendToCustomer($appointment->customer, $message, [
            'appointment_id' => $appointment->id,
            'message_type' => 'reminder'
        ]);
    }

    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation($appointment): ?SmsMessageLog
    {
        if (!$appointment->customer || !$appointment->customer->phone) {
            return null;
        }

        $message = sprintf(
            "Your appointment has been confirmed for %s at %s. We look forward to seeing you!",
            $appointment->starts_at->format('M j, Y'),
            $appointment->starts_at->format('g:i A')
        );

        return $this->sendToCustomer($appointment->customer, $message, [
            'appointment_id' => $appointment->id,
            'message_type' => 'confirmation'
        ]);
    }

    /**
     * Send appointment cancellation
     */
    public function sendAppointmentCancellation($appointment): ?SmsMessageLog
    {
        if (!$appointment->customer || !$appointment->customer->phone) {
            return null;
        }

        $message = sprintf(
            "Your appointment scheduled for %s has been cancelled. Please contact us if you'd like to reschedule.",
            $appointment->starts_at->format('M j, Y \a\t g:i A')
        );

        return $this->sendToCustomer($appointment->customer, $message, [
            'appointment_id' => $appointment->id,
            'message_type' => 'cancellation'
        ]);
    }

    /**
     * Normalize phone number to E.164 format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add country code if not present (assuming German numbers)
        if (strlen($phone) === 10) {
            $phone = '49' . $phone;
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = '49' . substr($phone, 1);
        }

        // Ensure it starts with +
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Get SMS status from Twilio
     */
    public function getMessageStatus(string $messageSid): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $message = $this->twilioClient->messages($messageSid)->fetch();

            return [
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'price' => $message->price,
                'price_unit' => $message->priceUnit,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch message status', [
                'sid' => $messageSid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle Twilio webhook callback
     */
    public function handleWebhook(array $data): void
    {
        if (isset($data['MessageSid'])) {
            $smsLog = SmsMessageLog::where('provider_message_id', $data['MessageSid'])->first();

            if ($smsLog) {
                $smsLog->update([
                    'status' => $this->mapTwilioStatus($data['MessageStatus'] ?? 'unknown'),
                    'delivered_at' => $data['MessageStatus'] === 'delivered' ? now() : null,
                    'provider_response' => array_merge(
                        $smsLog->provider_response ?? [],
                        $data
                    )
                ]);
            }
        }
    }

    /**
     * Map Twilio status to internal status
     */
    protected function mapTwilioStatus(string $twilioStatus): string
    {
        return match($twilioStatus) {
            'queued', 'sending' => 'pending',
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed', 'undelivered' => 'failed',
            default => 'unknown'
        };
    }

    /**
     * Check if SMS service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}