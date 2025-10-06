<?php

namespace App\Services\Notifications\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\NotificationProvider;
use App\Models\NotificationQueue;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsChannel implements NotificationChannelInterface
{
    protected $providers = [];
    protected $currentProvider = null;

    public function __construct()
    {
        $this->loadProviders();
    }

    /**
     * Send SMS notification
     */
    public function send(NotificationQueue $notification): array
    {
        try {
            $recipient = $notification->recipient;
            $data = $notification->data;

            // Validate recipient
            if (empty($recipient['phone'])) {
                return $this->failureResponse('Phone number is required for SMS');
            }

            // Format phone number
            $phone = $this->formatPhoneNumber(
                $recipient['phone'],
                $recipient['country_code'] ?? '+49'
            );

            // Get available provider
            $provider = $this->getAvailableProvider();

            if (!$provider) {
                return $this->failureResponse('No SMS provider available');
            }

            // Build content from notification data
            $content = [
                'text' => $data['content'] ?? $data['subject'] ?? '',
                'data' => $data
            ];

            // Send based on provider type
            $result = match($provider->type) {
                'twilio' => $this->sendViaTwilio($provider, $phone, $content, []),
                'vonage' => $this->sendViaVonage($provider, $phone, $content, []),
                default => throw new \RuntimeException("Unknown SMS provider: {$provider->type}")
            };

            // Track usage
            $this->trackUsage($provider, $result);

            return $this->successResponse($result);

        } catch (\Exception $e) {
            Log::error('SMS send failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            return $this->failureResponse($e->getMessage());
        }
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendViaTwilio(NotificationProvider $provider, string $phone, array $content, array $options): array
    {
        try {
            $credentials = $provider->credentials;
            $config = $provider->config ?? [];

            $client = new TwilioClient(
                $credentials['account_sid'],
                decrypt($credentials['auth_token'])
            );

            // Build message
            $messageBody = $this->buildSmsContent($content);

            // Check message length and split if needed
            $messages = $this->splitLongMessage($messageBody);

            $messageIds = [];
            $totalCost = 0;

            foreach ($messages as $index => $messagePart) {
                // Add part indicator for multi-part messages
                if (count($messages) > 1) {
                    $partNumber = $index + 1;
                    $totalParts = count($messages);
                    $messagePart = "({$partNumber}/{$totalParts}) " . $messagePart;
                }

                $message = $client->messages->create(
                    $phone,
                    [
                        'from' => $config['from_number'] ?? $credentials['phone_number'],
                        'body' => $messagePart,
                        'statusCallback' => config('app.url') . '/webhooks/twilio/status'
                    ]
                );

                $messageIds[] = $message->sid;

                // Estimate cost (Twilio provides this)
                if ($message->price) {
                    $totalCost += abs((float)$message->price);
                }
            }

            return [
                'success' => true,
                'message_id' => implode(',', $messageIds),
                'provider' => 'twilio',
                'cost' => $totalCost,
                'parts' => count($messages)
            ];

        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            // Try failover provider if available
            if ($this->hasFailoverProvider($provider)) {
                return $this->sendViaFailover($phone, $content, $options);
            }

            throw $e;
        }
    }

    /**
     * Send SMS via Vonage (Nexmo)
     */
    protected function sendViaVonage(NotificationProvider $provider, string $phone, array $content, array $options): array
    {
        try {
            $credentials = $provider->credentials;
            $config = $provider->config ?? [];

            $basic = new \Vonage\Client\Credentials\Basic(
                $credentials['api_key'],
                decrypt($credentials['api_secret'])
            );

            $client = new \Vonage\Client($basic);

            $messageBody = $this->buildSmsContent($content);
            $messages = $this->splitLongMessage($messageBody);

            $messageIds = [];
            $totalCost = 0;

            foreach ($messages as $index => $messagePart) {
                if (count($messages) > 1) {
                    $partNumber = $index + 1;
                    $totalParts = count($messages);
                    $messagePart = "({$partNumber}/{$totalParts}) " . $messagePart;
                }

                $response = $client->sms()->send(
                    new \Vonage\SMS\Message\SMS(
                        $phone,
                        $config['from'] ?? config('app.name'),
                        $messagePart
                    )
                );

                $message = $response->current();
                $messageIds[] = $message->getMessageId();

                // Calculate cost
                if ($message->getMessagePrice()) {
                    $totalCost += (float)$message->getMessagePrice();
                }
            }

            return [
                'success' => true,
                'message_id' => implode(',', $messageIds),
                'provider' => 'vonage',
                'cost' => $totalCost,
                'parts' => count($messages)
            ];

        } catch (\Exception $e) {
            Log::error('Vonage SMS failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            if ($this->hasFailoverProvider($provider)) {
                return $this->sendViaFailover($phone, $content, $options);
            }

            throw $e;
        }
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhoneNumber(string $phone, string $defaultCountryCode = '+49'): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If doesn't start with +, add default country code
        if (!str_starts_with($phone, '+')) {
            // Remove leading 0 for German numbers
            if (str_starts_with($phone, '0') && $defaultCountryCode === '+49') {
                $phone = substr($phone, 1);
            }
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    /**
     * Build SMS content from template or raw content
     */
    protected function buildSmsContent(array $content): string
    {
        if (isset($content['template'])) {
            // Template-based content
            return $this->renderTemplate($content['template'], $content['data'] ?? []);
        }

        // Direct content
        return $content['text'] ?? $content['content'] ?? '';
    }

    /**
     * Split long messages for SMS
     */
    protected function splitLongMessage(string $message): array
    {
        $maxLength = 160; // Standard SMS length
        $maxLengthMultipart = 153; // Length when split

        if (strlen($message) <= $maxLength) {
            return [$message];
        }

        // Split into multiple parts
        $parts = [];
        $words = explode(' ', $message);
        $currentPart = '';

        foreach ($words as $word) {
            if (strlen($currentPart . ' ' . $word) <= $maxLengthMultipart) {
                $currentPart .= ($currentPart ? ' ' : '') . $word;
            } else {
                if ($currentPart) {
                    $parts[] = $currentPart;
                }
                $currentPart = $word;
            }
        }

        if ($currentPart) {
            $parts[] = $currentPart;
        }

        return $parts;
    }

    /**
     * Load SMS providers
     */
    protected function loadProviders(): void
    {
        $this->providers = Cache::remember('sms_providers', 600, function () {
            return NotificationProvider::where('channel', 'sms')
                ->where('is_active', true)
                ->orderBy('priority')
                ->get();
        });
    }

    /**
     * Get available provider based on rate limits and balance
     */
    protected function getAvailableProvider(): ?NotificationProvider
    {
        foreach ($this->providers as $provider) {
            // Check rate limit
            if (!$this->checkProviderRateLimit($provider)) {
                continue;
            }

            // Check balance if applicable
            if (!$this->checkProviderBalance($provider)) {
                continue;
            }

            $this->currentProvider = $provider;
            return $provider;
        }

        return null;
    }

    /**
     * Check provider rate limit
     */
    protected function checkProviderRateLimit(NotificationProvider $provider): bool
    {
        if (!$provider->rate_limit) {
            return true;
        }

        $key = "sms_rate_limit:{$provider->id}";
        $current = Cache::get($key, 0);

        return $current < $provider->rate_limit;
    }

    /**
     * Check provider balance
     */
    protected function checkProviderBalance(NotificationProvider $provider): bool
    {
        if (!$provider->balance) {
            return true; // No balance tracking
        }

        // Minimum balance threshold
        $minBalance = $provider->config['min_balance'] ?? 10;

        return $provider->balance >= $minBalance;
    }

    /**
     * Track usage for billing and rate limiting
     */
    protected function trackUsage(NotificationProvider $provider, array $result): void
    {
        // Update rate limit counter
        if ($provider->rate_limit) {
            $key = "sms_rate_limit:{$provider->id}";
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, 60); // Reset every minute
        }

        // Update provider balance if cost is known
        if (isset($result['cost']) && $result['cost'] > 0) {
            $provider->decrement('balance', $result['cost']);
        }

        // Update statistics
        $stats = $provider->statistics ?? [];
        $stats['messages_sent'] = ($stats['messages_sent'] ?? 0) + 1;
        $stats['total_cost'] = ($stats['total_cost'] ?? 0) + ($result['cost'] ?? 0);
        $stats['last_used'] = now()->toIso8601String();

        $provider->update(['statistics' => $stats]);
    }

    /**
     * Check if provider has failover
     */
    protected function hasFailoverProvider(NotificationProvider $provider): bool
    {
        return $this->providers->where('id', '!=', $provider->id)->count() > 0;
    }

    /**
     * Send via failover provider
     */
    protected function sendViaFailover(string $phone, array $content, array $options): array
    {
        // Get next available provider
        $failoverProvider = $this->providers
            ->where('id', '!=', $this->currentProvider->id)
            ->first();

        if (!$failoverProvider) {
            throw new \RuntimeException('No failover SMS provider available');
        }

        Log::info('Using failover SMS provider', [
            'primary' => $this->currentProvider->name,
            'failover' => $failoverProvider->name
        ]);

        $this->currentProvider = $failoverProvider;

        return match($failoverProvider->type) {
            'twilio' => $this->sendViaTwilio($failoverProvider, $phone, $content, $options),
            'vonage' => $this->sendViaVonage($failoverProvider, $phone, $content, $options),
            default => throw new \RuntimeException("Unknown SMS provider: {$failoverProvider->type}")
        };
    }

    /**
     * Render template
     */
    protected function renderTemplate(string $template, array $data): string
    {
        // Simple template rendering - replace with your template engine
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * Validate phone number
     */
    public function validatePhoneNumber(string $phone, string $countryCode = null): bool
    {
        try {
            // Basic validation
            $formatted = $this->formatPhoneNumber($phone, $countryCode ?? '+49');

            // Check length (E.164 format: max 15 digits)
            if (strlen($formatted) < 8 || strlen($formatted) > 16) {
                return false;
            }

            // Could add libphonenumber for more sophisticated validation
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get delivery status
     */
    public function getDeliveryStatus(string $messageId, string $provider = null): array
    {
        try {
            $provider = $provider ?? 'twilio'; // Default

            switch ($provider) {
                case 'twilio':
                    return $this->getTwilioStatus($messageId);
                case 'vonage':
                    return $this->getVonageStatus($messageId);
                default:
                    return ['status' => 'unknown'];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get SMS status', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Twilio message status
     */
    protected function getTwilioStatus(string $messageId): array
    {
        $provider = $this->providers->where('type', 'twilio')->first();

        if (!$provider) {
            return ['status' => 'unknown'];
        }

        $client = new TwilioClient(
            $provider->credentials['account_sid'],
            decrypt($provider->credentials['auth_token'])
        );

        $message = $client->messages($messageId)->fetch();

        return [
            'status' => $message->status,
            'delivered_at' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
            'error' => $message->errorMessage
        ];
    }

    /**
     * Get Vonage message status
     */
    protected function getVonageStatus(string $messageId): array
    {
        // Vonage doesn't provide easy status lookup
        // Would need to implement webhook handling
        return ['status' => 'unknown'];
    }

    /**
     * Check if this channel can deliver to the given recipient
     */
    public function canDeliver(array $recipient): bool
    {
        // Check if recipient has phone number
        if (empty($recipient['phone'])) {
            return false;
        }

        // Validate phone number format
        if (!$this->validatePhoneNumber($recipient['phone'], $recipient['country_code'] ?? null)) {
            return false;
        }

        // Check if we have active providers
        if ($this->providers->isEmpty()) {
            return false;
        }

        // Check if at least one provider has balance
        $hasAvailableProvider = $this->getAvailableProvider() !== null;

        return $hasAvailableProvider;
    }

    /**
     * Get the channel identifier
     */
    public function getChannelName(): string
    {
        return 'sms';
    }

    /**
     * Validate channel configuration
     */
    public function validateConfig(): bool
    {
        // Check if providers are loaded
        if ($this->providers->isEmpty()) {
            return false;
        }

        // Check if at least one provider is properly configured
        foreach ($this->providers as $provider) {
            if ($this->isProviderConfigured($provider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if provider is properly configured
     */
    protected function isProviderConfigured(NotificationProvider $provider): bool
    {
        if (!$provider->is_active) {
            return false;
        }

        $credentials = $provider->credentials;

        return match($provider->type) {
            'twilio' => isset($credentials['account_sid']) && isset($credentials['auth_token']),
            'vonage' => isset($credentials['api_key']) && isset($credentials['api_secret']),
            default => false
        };
    }

    /**
     * Create success response
     */
    protected function successResponse(array $data = []): array
    {
        return [
            'success' => true,
            'channel' => 'sms',
            'data' => $data
        ];
    }

    /**
     * Create failure response
     */
    protected function failureResponse(string $error, array $data = []): array
    {
        return [
            'success' => false,
            'channel' => 'sms',
            'error' => $error,
            'data' => $data
        ];
    }
}