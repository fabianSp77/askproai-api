<?php

namespace App\Services\Notifications\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;

class PushChannel implements NotificationChannelInterface
{
    protected ?Messaging $messaging = null;
    protected array $config;
    protected array $platformConfigs = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'credentials_path' => config('services.firebase.credentials_path'),
            'project_id' => config('services.firebase.project_id'),
            'default_sound' => 'default',
            'default_badge' => 1,
            'default_icon' => '/icon-192x192.png',
            'click_action' => config('app.url'),
            'ttl' => 86400, // 24 hours
            'priority' => 'high',
            'collapse_key' => null
        ], $config);

        $this->initializeFirebase();
        $this->setupPlatformConfigs();
    }

    protected function initializeFirebase(): void
    {
        if (!$this->config['credentials_path']) {
            Log::warning('Firebase credentials not configured for push notifications');
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($this->config['credentials_path']);

            if ($this->config['project_id']) {
                $factory = $factory->withProjectId($this->config['project_id']);
            }

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function setupPlatformConfigs(): void
    {
        // Check if Firebase classes are available
        if (!class_exists(\Kreait\Firebase\Messaging\AndroidConfig::class)) {
            Log::warning('Firebase SDK not installed, skipping push notification platform configs');
            return;
        }

        try {
            // Android configuration
            $this->platformConfigs['android'] = AndroidConfig::fromArray([
                'priority' => $this->config['priority'],
                'ttl' => $this->config['ttl'] . 's',
                'notification' => [
                    'sound' => $this->config['default_sound'],
                    'click_action' => $this->config['click_action'],
                    'icon' => $this->config['default_icon'],
                    'color' => '#007bff'
                ]
            ]);

            // iOS configuration
            $this->platformConfigs['ios'] = ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority' => $this->config['priority'] === 'high' ? '10' : '5',
                    'apns-expiration' => (string)(time() + $this->config['ttl'])
                ],
                'payload' => [
                    'aps' => [
                        'sound' => $this->config['default_sound'],
                        'badge' => $this->config['default_badge'],
                        'mutable-content' => 1
                    ]
                ]
            ]);

            // Web Push configuration
            $this->platformConfigs['web'] = WebPushConfig::fromArray([
                'notification' => [
                    'icon' => $this->config['default_icon'],
                    'badge' => '/badge-72x72.png',
                    'vibrate' => [200, 100, 200]
                ],
                'fcm_options' => [
                    'link' => $this->config['click_action']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to setup push notification platform configs', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function send(NotificationQueue $notification): array
    {
        if (!$this->messaging) {
            return $this->failureResponse('Push notifications not configured');
        }

        try {
            $recipient = $notification->recipient;
            $data = $notification->data;

            // Validate recipient has push token
            if (!isset($recipient['push_token']) || empty($recipient['push_token'])) {
                return $this->failureResponse('No push token available');
            }

            // Build push message
            $message = $this->buildMessage($notification);

            // Send to FCM
            $response = $this->messaging->send($message);

            return $this->successResponse([
                'message_id' => $response,
                'token' => $recipient['push_token'],
                'platform' => $recipient['platform'] ?? 'unknown'
            ]);

        } catch (MessagingException $e) {
            Log::error('FCM messaging error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'errors' => $e->errors()
            ]);

            // Handle specific FCM errors
            if (str_contains($e->getMessage(), 'Unregistered') ||
                str_contains($e->getMessage(), 'InvalidRegistration')) {
                // Token is invalid, should be removed
                $this->handleInvalidToken($notification);
                return $this->failureResponse('Invalid or expired push token', [
                    'should_remove_token' => true
                ]);
            }

            return $this->failureResponse('FCM error: ' . $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Push notification error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            return $this->failureResponse('Failed to send push notification: ' . $e->getMessage());
        }
    }

    public function sendBulk(array $notifications): array
    {
        if (!$this->messaging) {
            return array_map(
                fn($n) => $this->failureResponse('Push notifications not configured'),
                $notifications
            );
        }

        $messages = [];
        $notificationMap = [];

        // Build messages for multicast
        foreach ($notifications as $notification) {
            $recipient = $notification->recipient;

            if (!isset($recipient['push_token']) || empty($recipient['push_token'])) {
                continue;
            }

            $message = $this->buildMessage($notification);
            $messages[] = $message;
            $notificationMap[$recipient['push_token']] = $notification;
        }

        if (empty($messages)) {
            return array_map(
                fn($n) => $this->failureResponse('No valid push tokens'),
                $notifications
            );
        }

        try {
            // Send multicast
            $response = $this->messaging->sendAll($messages);

            $results = [];
            foreach ($response->getResponses() as $index => $singleResponse) {
                $notification = $notifications[$index];

                if ($singleResponse->isSuccessful()) {
                    $results[] = $this->successResponse([
                        'message_id' => $singleResponse->target()->value(),
                        'token' => $notification->recipient['push_token']
                    ]);
                } else {
                    $error = $singleResponse->error();
                    $results[] = $this->failureResponse(
                        'Failed to send: ' . $error->getMessage()
                    );

                    // Handle invalid tokens
                    if ($error->code() === 'UNREGISTERED' ||
                        $error->code() === 'INVALID_ARGUMENT') {
                        $this->handleInvalidToken($notification);
                    }
                }
            }

            // Add summary statistics
            $successCount = $response->successes()->count();
            $failureCount = $response->failures()->count();

            Log::info('Bulk push notification sent', [
                'total' => count($messages),
                'success' => $successCount,
                'failed' => $failureCount
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Bulk push notification error', [
                'error' => $e->getMessage()
            ]);

            return array_map(
                fn($n) => $this->failureResponse('Bulk send failed: ' . $e->getMessage()),
                $notifications
            );
        }
    }

    protected function buildMessage(NotificationQueue $notification): CloudMessage
    {
        $data = $notification->data;
        $recipient = $notification->recipient;

        // Create base notification
        $pushNotification = Notification::create(
            $data['title'] ?? 'Benachrichtigung',
            $data['body'] ?? $data['content'] ?? ''
        );

        // Set image if provided
        if (isset($data['image'])) {
            $pushNotification = $pushNotification->withImageUrl($data['image']);
        }

        // Create message
        $message = CloudMessage::withTarget('token', $recipient['push_token'])
            ->withNotification($pushNotification)
            ->withData($this->prepareDataPayload($notification));

        // Add platform-specific config
        $platform = $recipient['platform'] ?? $this->detectPlatform($recipient['push_token']);

        switch ($platform) {
            case 'android':
                $message = $message->withAndroidConfig($this->getAndroidConfig($data));
                break;
            case 'ios':
                $message = $message->withApnsConfig($this->getIosConfig($data));
                break;
            case 'web':
                $message = $message->withWebPushConfig($this->getWebConfig($data));
                break;
        }

        // Set priority
        if ($notification->priority <= 2) {
            $message = $message->withHighestPossiblePriority();
        }

        // Set collapse key for replacing notifications
        if (isset($data['collapse_key']) || $this->config['collapse_key']) {
            $collapseKey = $data['collapse_key'] ?? $this->config['collapse_key'];
            $message = $message->withCollapseKey($collapseKey);
        }

        return $message;
    }

    protected function prepareDataPayload(NotificationQueue $notification): array
    {
        $data = $notification->data;

        return [
            'notification_id' => $notification->uuid,
            'type' => $notification->type,
            'action' => $data['action'] ?? 'open',
            'action_url' => $data['action_url'] ?? $this->config['click_action'],
            'category' => $data['category'] ?? $notification->type,
            'timestamp' => now()->toIso8601String(),
            'custom_data' => json_encode($data['custom_data'] ?? [])
        ];
    }

    protected function getAndroidConfig(array $data): AndroidConfig
    {
        $config = $this->platformConfigs['android'];

        // Override with custom data
        if (isset($data['android'])) {
            $configArray = $config->jsonSerialize();
            $configArray = array_merge($configArray, $data['android']);
            $config = AndroidConfig::fromArray($configArray);
        }

        return $config;
    }

    protected function getIosConfig(array $data): ApnsConfig
    {
        $config = $this->platformConfigs['ios'];

        // Override with custom data
        if (isset($data['ios'])) {
            $configArray = $config->jsonSerialize();
            $configArray = array_merge_recursive($configArray, $data['ios']);
            $config = ApnsConfig::fromArray($configArray);
        }

        // Add custom sound if specified
        if (isset($data['sound']) && $data['sound'] !== 'default') {
            $configArray = $config->jsonSerialize();
            $configArray['payload']['aps']['sound'] = $data['sound'];
            $config = ApnsConfig::fromArray($configArray);
        }

        return $config;
    }

    protected function getWebConfig(array $data): WebPushConfig
    {
        $config = $this->platformConfigs['web'];

        // Override with custom data
        if (isset($data['web'])) {
            $configArray = $config->jsonSerialize();
            $configArray = array_merge_recursive($configArray, $data['web']);
            $config = WebPushConfig::fromArray($configArray);
        }

        // Add actions if provided
        if (isset($data['actions']) && is_array($data['actions'])) {
            $configArray = $config->jsonSerialize();
            $configArray['notification']['actions'] = array_map(function ($action) {
                return [
                    'action' => $action['id'] ?? uniqid(),
                    'title' => $action['title'],
                    'icon' => $action['icon'] ?? null
                ];
            }, $data['actions']);
            $config = WebPushConfig::fromArray($configArray);
        }

        return $config;
    }

    protected function detectPlatform(string $token): string
    {
        // Simple heuristic based on token format
        // This is not 100% accurate but works for most cases

        if (strlen($token) > 150) {
            return 'web';
        } elseif (preg_match('/^[a-zA-Z0-9:_-]+$/', $token) && strlen($token) === 64) {
            return 'ios';
        } else {
            return 'android';
        }
    }

    protected function handleInvalidToken(NotificationQueue $notification): void
    {
        // Mark the token as invalid
        $notifiable = $notification->notifiable;

        if ($notifiable && method_exists($notifiable, 'clearPushToken')) {
            $notifiable->clearPushToken();
        }

        Log::warning('Invalid push token detected', [
            'notification_id' => $notification->id,
            'notifiable_type' => $notification->notifiable_type,
            'notifiable_id' => $notification->notifiable_id
        ]);
    }

    protected function successResponse(array $data = []): array
    {
        return [
            'success' => true,
            'channel' => 'push',
            'data' => $data
        ];
    }

    protected function failureResponse(string $error, array $data = []): array
    {
        return [
            'success' => false,
            'channel' => 'push',
            'error' => $error,
            'data' => $data
        ];
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['credentials_path']) &&
               file_exists($this->config['credentials_path']);
    }

    public function getChannelName(): string
    {
        return 'push';
    }

    public function supportsTemplates(): bool
    {
        return true;
    }

    public function supportsBulkSending(): bool
    {
        return true;
    }

    public function getMaxRecipientsPerRequest(): int
    {
        return 500; // FCM multicast limit
    }

    public function estimateCost(int $messageCount): float
    {
        // FCM is free for basic usage
        return 0.0;
    }

    public function canDeliver(array $recipient): bool
    {
        // Check if recipient has push token
        if (empty($recipient['token'])) {
            return false;
        }

        // Check if platform is supported
        $platform = $recipient['platform'] ?? 'fcm';
        if (!in_array($platform, ['fcm', 'apns', 'web'])) {
            return false;
        }

        // Check if Firebase is configured
        if (!$this->validateConfig()) {
            return false;
        }

        return true;
    }
}