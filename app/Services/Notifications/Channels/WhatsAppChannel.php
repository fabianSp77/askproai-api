<?php

namespace App\Services\Notifications\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\NotificationProvider;
use App\Models\NotificationQueue;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhatsAppChannel implements NotificationChannelInterface
{
    protected $providers = [];
    protected $templates = [];

    public function __construct()
    {
        $this->loadProviders();
        $this->loadTemplates();
    }

    /**
     * Send WhatsApp notification
     */
    public function send(NotificationQueue $notification): array
    {
        try {
            $recipient = $notification->recipient;
            $data = $notification->data;

            // Validate recipient
            if (empty($recipient['phone'])) {
                return $this->failureResponse('Phone number is required for WhatsApp');
            }

            // Format phone number
            $phone = $this->formatPhoneNumber(
                $recipient['phone'],
                $recipient['country_code'] ?? '+49'
            );

            // Get available provider
            $provider = $this->getAvailableProvider();

            if (!$provider) {
                return $this->failureResponse('No WhatsApp provider available');
            }

            // Build content from notification data
            $content = [
                'text' => $data['content'] ?? $data['subject'] ?? '',
                'template' => $data['template'] ?? null,
                'media' => $data['media'] ?? null,
                'data' => $data
            ];

            // Send based on provider type
            $result = match($provider->type) {
                'twilio' => $this->sendViaTwilioWhatsApp($provider, $phone, $content, []),
                'whatsapp_business' => $this->sendViaWhatsAppBusiness($provider, $phone, $content, []),
                'vonage' => $this->sendViaVonageWhatsApp($provider, $phone, $content, []),
                default => throw new \RuntimeException("Unknown WhatsApp provider: {$provider->type}")
            };

            // Track usage
            $this->trackUsage($provider, $result);

            return $this->successResponse($result);

        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            return $this->failureResponse($e->getMessage());
        }
    }

    /**
     * Send via Twilio WhatsApp
     */
    protected function sendViaTwilioWhatsApp(NotificationProvider $provider, string $phone, array $content, array $options): array
    {
        try {
            $credentials = $provider->credentials;
            $config = $provider->config ?? [];

            $client = new TwilioClient(
                $credentials['account_sid'],
                decrypt($credentials['auth_token'])
            );

            // Format WhatsApp number
            $toNumber = 'whatsapp:' . $phone;
            $fromNumber = 'whatsapp:' . ($config['whatsapp_number'] ?? $credentials['whatsapp_number']);

            // Build message
            $messageOptions = [
                'from' => $fromNumber,
                'body' => $this->buildWhatsAppContent($content)
            ];

            // Add media if present
            if (!empty($content['media'])) {
                $messageOptions['mediaUrl'] = $content['media'];
            }

            // Add template if using approved template
            if (!empty($content['template_sid'])) {
                $messageOptions['messagingServiceSid'] = $content['template_sid'];
            }

            $message = $client->messages->create($toNumber, $messageOptions);

            return [
                'success' => true,
                'message_id' => $message->sid,
                'provider' => 'twilio_whatsapp',
                'cost' => $message->price ? abs((float)$message->price) : null
            ];

        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            throw $e;
        }
    }

    /**
     * Send via WhatsApp Business API
     */
    protected function sendViaWhatsAppBusiness(NotificationProvider $provider, string $phone, array $content, array $options): array
    {
        try {
            $credentials = $provider->credentials;
            $config = $provider->config ?? [];

            $apiUrl = $config['api_url'] ?? 'https://graph.facebook.com/v17.0';
            $phoneNumberId = $credentials['phone_number_id'];
            $accessToken = decrypt($credentials['access_token']);

            // Prepare message payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone
            ];

            // Check if using template or direct message
            if (!empty($content['template'])) {
                // Template message
                $payload['type'] = 'template';
                $payload['template'] = [
                    'name' => $content['template'],
                    'language' => [
                        'code' => $content['language'] ?? 'de'
                    ]
                ];

                // Add template parameters if present
                if (!empty($content['parameters'])) {
                    $payload['template']['components'] = $this->buildTemplateComponents($content['parameters']);
                }
            } else {
                // Regular text message
                $payload['type'] = 'text';
                $payload['text'] = [
                    'preview_url' => $content['preview_url'] ?? false,
                    'body' => $this->buildWhatsAppContent($content)
                ];
            }

            // Add media if present
            if (!empty($content['media'])) {
                $payload = $this->addMediaToPayload($payload, $content['media']);
            }

            // Send request
            $response = Http::withToken($accessToken)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", $payload);

            if (!$response->successful()) {
                throw new \RuntimeException('WhatsApp Business API error: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'message_id' => $result['messages'][0]['id'] ?? null,
                'provider' => 'whatsapp_business',
                'cost' => $this->estimateWhatsAppCost($content)
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Business API failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            throw $e;
        }
    }

    /**
     * Send via Vonage WhatsApp
     */
    protected function sendViaVonageWhatsApp(NotificationProvider $provider, string $phone, array $content, array $options): array
    {
        try {
            $credentials = $provider->credentials;

            $basic = new \Vonage\Client\Credentials\Basic(
                $credentials['api_key'],
                decrypt($credentials['api_secret'])
            );

            $client = new \Vonage\Client($basic);

            // Build WhatsApp message
            $message = new \Vonage\Messages\Channel\WhatsApp\WhatsAppText(
                $phone,
                $credentials['whatsapp_number'],
                $this->buildWhatsAppContent($content)
            );

            $response = $client->messages()->send($message);

            return [
                'success' => true,
                'message_id' => $response->getMessageUuid(),
                'provider' => 'vonage_whatsapp',
                'cost' => 0.005 // Approximate cost
            ];

        } catch (\Exception $e) {
            Log::error('Vonage WhatsApp failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            throw $e;
        }
    }

    /**
     * Build WhatsApp message content
     */
    protected function buildWhatsAppContent(array $content): string
    {
        // Format with emojis and structure for WhatsApp
        $message = '';

        // Add header if present
        if (!empty($content['header'])) {
            $message .= "📋 *{$content['header']}*\n\n";
        }

        // Main content
        if (!empty($content['text'])) {
            $message .= $content['text'];
        } elseif (!empty($content['content'])) {
            $message .= $content['content'];
        }

        // Add footer if present
        if (!empty($content['footer'])) {
            $message .= "\n\n_{$content['footer']}_";
        }

        // Add call-to-action if present
        if (!empty($content['cta'])) {
            $message .= "\n\n" . $content['cta'];
        }

        return $message;
    }

    /**
     * Build template components for WhatsApp Business API
     */
    protected function buildTemplateComponents(array $parameters): array
    {
        $components = [];

        // Header parameters
        if (!empty($parameters['header'])) {
            $components[] = [
                'type' => 'header',
                'parameters' => array_map(function ($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters['header'])
            ];
        }

        // Body parameters
        if (!empty($parameters['body'])) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function ($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters['body'])
            ];
        }

        // Button parameters
        if (!empty($parameters['buttons'])) {
            foreach ($parameters['buttons'] as $index => $button) {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => $button['type'] ?? 'quick_reply',
                    'index' => $index,
                    'parameters' => [
                        [
                            'type' => $button['type'] === 'url' ? 'text' : 'payload',
                            $button['type'] === 'url' ? 'text' : 'payload' => $button['value']
                        ]
                    ]
                ];
            }
        }

        return $components;
    }

    /**
     * Add media to WhatsApp Business API payload
     */
    protected function addMediaToPayload(array $payload, $media): array
    {
        if (is_string($media)) {
            // Single media URL
            $mediaType = $this->getMediaType($media);
            $payload['type'] = $mediaType;
            $payload[$mediaType] = [
                'link' => $media,
                'caption' => $payload['text']['body'] ?? ''
            ];
            unset($payload['text']);
        } elseif (is_array($media)) {
            // Multiple media or media with details
            $mediaType = $media['type'] ?? $this->getMediaType($media['url']);
            $payload['type'] = $mediaType;
            $payload[$mediaType] = [
                'link' => $media['url'],
                'caption' => $media['caption'] ?? $payload['text']['body'] ?? ''
            ];
            unset($payload['text']);
        }

        return $payload;
    }

    /**
     * Get media type from URL
     */
    protected function getMediaType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match($extension) {
            'jpg', 'jpeg', 'png', 'gif' => 'image',
            'mp4', 'avi', 'mov' => 'video',
            'pdf' => 'document',
            'mp3', 'wav', 'ogg' => 'audio',
            default => 'document'
        };
    }

    /**
     * Format phone number for WhatsApp
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

        // Remove + for WhatsApp Business API
        return ltrim($phone, '+');
    }

    /**
     * Load WhatsApp providers
     */
    protected function loadProviders(): void
    {
        $this->providers = Cache::remember('whatsapp_providers', 600, function () {
            return NotificationProvider::where('channel', 'whatsapp')
                ->where('is_active', true)
                ->orderBy('priority')
                ->get();
        });
    }

    /**
     * Load approved templates
     */
    protected function loadTemplates(): void
    {
        $this->templates = Cache::remember('whatsapp_templates', 3600, function () {
            // Load from database or config
            return [
                'appointment_confirmation' => [
                    'name' => 'appointment_confirmation',
                    'languages' => ['de', 'en'],
                    'parameters' => ['name', 'service', 'date', 'time', 'location']
                ],
                'appointment_reminder' => [
                    'name' => 'appointment_reminder',
                    'languages' => ['de', 'en'],
                    'parameters' => ['name', 'service', 'time_until', 'location']
                ]
            ];
        });
    }

    /**
     * Get available provider
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

        $key = "whatsapp_rate_limit:{$provider->id}";
        $current = Cache::get($key, 0);

        return $current < $provider->rate_limit;
    }

    /**
     * Check provider balance
     */
    protected function checkProviderBalance(NotificationProvider $provider): bool
    {
        if (!$provider->balance) {
            return true;
        }

        $minBalance = $provider->config['min_balance'] ?? 10;
        return $provider->balance >= $minBalance;
    }

    /**
     * Track usage
     */
    protected function trackUsage(NotificationProvider $provider, array $result): void
    {
        // Update rate limit counter
        if ($provider->rate_limit) {
            $key = "whatsapp_rate_limit:{$provider->id}";
            Cache::increment($key);
            Cache::expire($key, 60);
        }

        // Update provider balance
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
     * Estimate WhatsApp cost
     */
    protected function estimateWhatsAppCost(array $content): float
    {
        // Basic cost estimation
        $baseCost = 0.005; // Base cost per message

        // Add media cost
        if (!empty($content['media'])) {
            $baseCost += 0.002;
        }

        // Add template cost
        if (!empty($content['template'])) {
            $baseCost += 0.001;
        }

        return $baseCost;
    }

    /**
     * Verify WhatsApp number is registered
     */
    public function verifyNumber(string $phone): bool
    {
        try {
            // This would call WhatsApp Business API to verify if number has WhatsApp
            // For now, return true as placeholder
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp number verification failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

        // Check if we have active providers
        if (empty($this->providers) || count($this->providers) === 0) {
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
        return 'whatsapp';
    }

    /**
     * Validate channel configuration
     */
    public function validateConfig(): bool
    {
        // Check if providers are loaded
        if (empty($this->providers) || count($this->providers) === 0) {
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
            'whatsapp_business' => isset($credentials['access_token']) && isset($credentials['phone_number_id']),
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
            'channel' => 'whatsapp',
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
            'channel' => 'whatsapp',
            'error' => $error,
            'data' => $data
        ];
    }
}