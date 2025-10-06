<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\NotificationQueue;
use App\Models\NotificationTemplate;
use App\Models\NotificationProvider;
use App\Models\NotificationConfiguration;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\WhatsAppChannel;
use App\Services\Notifications\Channels\PushChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationManager
{
    protected array $channels = [];
    protected array $providers = [];
    protected TemplateEngine $templateEngine;
    protected DeliveryOptimizer $optimizer;
    protected AnalyticsTracker $analytics;

    public function __construct(
        TemplateEngine $templateEngine,
        DeliveryOptimizer $optimizer,
        AnalyticsTracker $analytics
    ) {
        $this->templateEngine = $templateEngine;
        $this->optimizer = $optimizer;
        $this->analytics = $analytics;
        $this->initializeChannels();
        $this->loadProviders();
    }

    /**
     * Send notification through appropriate channels
     */
    public function send(
        $notifiable,
        string $type,
        array $data = [],
        array $channels = null,
        array $options = []
    ): array {
        try {
            // Check if notifiable wants notifications
            if (!$this->shouldSendNotification($notifiable, $type)) {
                return ['status' => 'skipped', 'reason' => 'User preferences'];
            }

            // Resolve hierarchical config (if not manually specified)
            $config = null;
            if ($channels === null) {
                $config = $this->resolveHierarchicalConfig($notifiable, $type);
            }

            // Determine channels to use
            if ($config) {
                // Use config-defined channel and fallback
                $channels = [$config->channel];
                $options['fallback_channel'] = $config->fallback_channel;
                $options['config_id'] = $config->id;

                Log::info('📋 Using hierarchical notification config', [
                    'notifiable' => get_class($notifiable),
                    'type' => $type,
                    'channel' => $config->channel,
                    'fallback' => $config->fallback_channel,
                ]);
            } else {
                // Fallback to preference-based or explicit channels
                $channels = $channels ?? $this->getPreferredChannels($notifiable, $type);
            }

            // Get language preference
            $language = $this->getLanguage($notifiable);

            $results = [];

            foreach ($channels as $channel) {
                // Check if channel is enabled for user
                if (!$this->isChannelEnabled($notifiable, $channel, $type)) {
                    continue;
                }

                // Check for unsubscribes
                if ($this->isUnsubscribed($notifiable, $channel, $type)) {
                    Log::info("User unsubscribed from {$channel}", [
                        'notifiable_id' => $notifiable->id,
                        'type' => $type
                    ]);
                    continue;
                }

                // Get optimal send time
                $scheduledAt = $this->optimizer->getOptimalSendTime(
                    $notifiable,
                    $channel,
                    $type,
                    $options['immediate'] ?? false
                );

                // Queue the notification
                $queuedNotification = $this->queueNotification(
                    $notifiable,
                    $channel,
                    $type,
                    $data,
                    $language,
                    $scheduledAt,
                    $options
                );

                $results[$channel] = $queuedNotification;

                // Send immediately if specified
                if ($options['immediate'] ?? false) {
                    $this->processNotification($queuedNotification);
                }
            }

            return [
                'status' => 'success',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Notification send failed', [
                'error' => $e->getMessage(),
                'notifiable' => get_class($notifiable),
                'type' => $type
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Resolve hierarchical notification configuration
     *
     * Resolution order: Staff → Service → Branch → Company → null (use defaults)
     *
     * @param mixed $notifiable The entity being notified
     * @param string $eventType The notification event type
     * @return NotificationConfiguration|null
     */
    protected function resolveHierarchicalConfig($notifiable, string $eventType): ?NotificationConfiguration
    {
        $context = $this->extractContext($notifiable);

        // Try Staff level (highest priority for personal preferences only)
        if ($context['staff_id']) {
            $config = NotificationConfiguration::forEntity(Staff::find($context['staff_id']))
                ->byEvent($eventType)
                ->enabled()
                ->first();
            if ($config) {
                Log::debug('✅ Config resolved at Staff level', [
                    'staff_id' => $context['staff_id'],
                    'event_type' => $eventType,
                    'channel' => $config->channel,
                ]);
                return $config;
            }
        }

        // Try Service level
        if ($context['service_id']) {
            $config = NotificationConfiguration::forEntity(Service::find($context['service_id']))
                ->byEvent($eventType)
                ->enabled()
                ->first();
            if ($config) {
                Log::debug('✅ Config resolved at Service level', [
                    'service_id' => $context['service_id'],
                    'event_type' => $eventType,
                    'channel' => $config->channel,
                ]);
                return $config;
            }
        }

        // Try Branch level
        if ($context['branch_id']) {
            $config = NotificationConfiguration::forEntity(Branch::find($context['branch_id']))
                ->byEvent($eventType)
                ->enabled()
                ->first();
            if ($config) {
                Log::debug('✅ Config resolved at Branch level', [
                    'branch_id' => $context['branch_id'],
                    'event_type' => $eventType,
                    'channel' => $config->channel,
                ]);
                return $config;
            }
        }

        // Try Company level (fallback)
        if ($context['company_id']) {
            $config = NotificationConfiguration::forEntity(Company::find($context['company_id']))
                ->byEvent($eventType)
                ->enabled()
                ->first();
            if ($config) {
                Log::debug('✅ Config resolved at Company level', [
                    'company_id' => $context['company_id'],
                    'event_type' => $eventType,
                    'channel' => $config->channel,
                ]);
                return $config;
            }
        }

        Log::debug('⚙️ No hierarchical config found, using system defaults', [
            'event_type' => $eventType,
            'context' => $context,
        ]);

        return null; // Use system defaults
    }

    /**
     * Extract hierarchical context from notifiable entity
     *
     * Determines Staff, Service, Branch, and Company IDs from the notifiable
     *
     * @param mixed $notifiable
     * @return array<string, string|null>
     */
    protected function extractContext($notifiable): array
    {
        $context = [
            'staff_id' => null,
            'service_id' => null,
            'branch_id' => null,
            'company_id' => null,
        ];

        // Direct entity detection
        if ($notifiable instanceof Staff) {
            $context['staff_id'] = $notifiable->id;
            $context['branch_id'] = $notifiable->branch_id ?? null;
            $context['company_id'] = $notifiable->branch?->company_id ?? null;
        } elseif ($notifiable instanceof Customer) {
            $context['company_id'] = $notifiable->company_id ?? null;
        } elseif ($notifiable instanceof Service) {
            $context['service_id'] = $notifiable->id;
            $context['company_id'] = $notifiable->company_id ?? null;
        } elseif ($notifiable instanceof Branch) {
            $context['branch_id'] = $notifiable->id;
            $context['company_id'] = $notifiable->company_id ?? null;
        } elseif ($notifiable instanceof Company) {
            $context['company_id'] = $notifiable->id;
        }

        // Check for relationships on Customer
        if ($notifiable instanceof Customer) {
            // If customer has appointments, get context from latest appointment
            if (method_exists($notifiable, 'appointments')) {
                $latestAppointment = $notifiable->appointments()
                    ->with(['service', 'branch', 'staff'])
                    ->latest()
                    ->first();

                if ($latestAppointment) {
                    $context['service_id'] = $latestAppointment->service_id ?? null;
                    $context['branch_id'] = $latestAppointment->branch_id ?? null;
                    $context['staff_id'] = $latestAppointment->staff_id ?? null;
                }
            }
        }

        return $context;
    }

    /**
     * Queue notification for later processing
     */
    protected function queueNotification(
        $notifiable,
        string $channel,
        string $type,
        array $data,
        string $language,
        ?Carbon $scheduledAt,
        array $options
    ): NotificationQueue {
        // Get template if exists
        $template = $this->getTemplate($channel, $type, $language);

        // Prepare recipient info
        $recipient = $this->getRecipientInfo($notifiable, $channel);

        // Merge config context into metadata
        $metadata = $options['metadata'] ?? [];
        if (isset($options['config_id'])) {
            $metadata['notification_config_id'] = $options['config_id'];
        }
        if (isset($options['fallback_channel'])) {
            $metadata['fallback_channel'] = $options['fallback_channel'];
        }

        return NotificationQueue::create([
            'uuid' => Str::uuid(),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'channel' => $channel,
            'template_key' => $template?->key,
            'type' => $type,
            'data' => $data,
            'recipient' => $recipient,
            'language' => $language,
            'priority' => $options['priority'] ?? 5,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'metadata' => !empty($metadata) ? $metadata : null
        ]);
    }

    /**
     * Process queued notification
     */
    public function processNotification(NotificationQueue $notification): bool
    {
        try {
            // Check rate limits
            if (!$this->checkRateLimit($notification)) {
                $notification->update([
                    'scheduled_at' => now()->addMinutes(5)
                ]);
                return false;
            }

            // Mark as processing
            $notification->update([
                'status' => 'processing',
                'attempts' => $notification->attempts + 1
            ]);

            // Get channel handler
            $channel = $this->getChannel($notification->channel);

            // Render content and update notification data
            $content = $this->renderNotification($notification);
            $notification->data = array_merge($notification->data, $content);

            // Send through channel (pass NotificationQueue object)
            $result = $channel->send($notification);

            // Update notification status
            $notification->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'sent_at' => $result['success'] ? now() : null,
                'provider_message_id' => $result['message_id'] ?? null,
                'cost' => $result['cost'] ?? null,
                'error_message' => $result['error'] ?? null
            ]);

            // Track delivery
            if ($result['success']) {
                $this->trackDelivery($notification, 'sent', $result);
                $this->analytics->trackSent($notification);
            } else {
                $this->handleFailure($notification, $result['error'] ?? 'Unknown error');
            }

            return $result['success'];

        } catch (\Exception $e) {
            Log::error('Notification processing failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            $this->handleFailure($notification, $e->getMessage());

            return false;
        }
    }

    /**
     * Get notification configuration from metadata
     *
     * @param NotificationQueue $notification
     * @return NotificationConfiguration|null
     */
    protected function getNotificationConfig(NotificationQueue $notification): ?NotificationConfiguration
    {
        $configId = $notification->metadata['notification_config_id'] ?? null;

        if ($configId) {
            return NotificationConfiguration::find($configId);
        }

        return null;
    }

    /**
     * Calculate retry delay based on configured strategy
     *
     * Supported strategies:
     * - exponential: pow(2, attempts) * baseDelay (default)
     * - linear: baseDelay * (attempts + 1)
     * - fibonacci: fibonacci(attempts) * baseDelay
     * - constant: baseDelay (no increase)
     *
     * @param NotificationConfiguration|null $config
     * @param int $attempts Current attempt count
     * @return int Delay in minutes
     */
    protected function calculateRetryDelay(?NotificationConfiguration $config, int $attempts): int
    {
        $baseDelay = $config?->retry_delay_minutes ?? config('notifications.retry_delay_minutes', 5);
        $strategy = $config?->metadata['retry_strategy'] ?? 'exponential';
        $maxDelay = $config?->metadata['max_retry_delay_minutes'] ?? config('notifications.max_retry_delay_minutes', 1440); // 24h default

        $delayMinutes = match($strategy) {
            'linear' => $baseDelay * ($attempts + 1),
            'fibonacci' => $this->fibonacciBackoff($baseDelay, $attempts),
            'constant' => $baseDelay,
            'exponential' => pow(2, $attempts) * $baseDelay,
            default => pow(2, $attempts) * $baseDelay
        };

        // Apply max delay cap
        return min($delayMinutes, $maxDelay);
    }

    /**
     * Calculate Fibonacci backoff delay
     *
     * Uses Fibonacci sequence for progressive backoff: 1, 1, 2, 3, 5, 8, 13...
     *
     * @param int $baseDelay Base delay in minutes
     * @param int $attempts Current attempt count
     * @return int Delay in minutes
     */
    protected function fibonacciBackoff(int $baseDelay, int $attempts): int
    {
        if ($attempts <= 0) return $baseDelay;
        if ($attempts === 1) return $baseDelay;

        $fib = [1, 1];
        for ($i = 2; $i <= $attempts; $i++) {
            $fib[$i] = $fib[$i - 1] + $fib[$i - 2];
        }

        return $fib[$attempts] * $baseDelay;
    }

    /**
     * Send notification via fallback channel
     *
     * Creates a new notification queue entry with the fallback channel
     *
     * @param NotificationQueue $notification
     * @param string $fallbackChannel
     * @return bool Success status
     */
    protected function sendViaFallbackChannel(NotificationQueue $notification, string $fallbackChannel): bool
    {
        Log::info('🔄 Attempting fallback channel', [
            'original_channel' => $notification->channel,
            'fallback_channel' => $fallbackChannel,
            'notification_id' => $notification->id,
        ]);

        try {
            // Get original notifiable
            $notifiableClass = $notification->notifiable_type;
            $notifiable = $notifiableClass::find($notification->notifiable_id);

            if (!$notifiable) {
                Log::error('❌ Notifiable not found for fallback', [
                    'notifiable_type' => $notification->notifiable_type,
                    'notifiable_id' => $notification->notifiable_id,
                ]);
                return false;
            }

            // Check if fallback channel is enabled
            if (!$this->isChannelEnabled($notifiable, $fallbackChannel, $notification->type)) {
                Log::warning('⚠️ Fallback channel not enabled', [
                    'channel' => $fallbackChannel,
                    'notifiable_id' => $notification->notifiable_id,
                ]);
                return false;
            }

            // Create new notification with fallback channel
            $fallbackNotification = NotificationQueue::create([
                'uuid' => Str::uuid(),
                'notifiable_type' => $notification->notifiable_type,
                'notifiable_id' => $notification->notifiable_id,
                'channel' => $fallbackChannel,
                'template_key' => $notification->template_key,
                'type' => $notification->type,
                'data' => $notification->data,
                'recipient' => $this->getRecipientInfo($notifiable, $fallbackChannel),
                'language' => $notification->language,
                'priority' => $notification->priority + 1, // Increase priority for fallback
                'status' => 'pending',
                'scheduled_at' => now(),
                'metadata' => array_merge($notification->metadata ?? [], [
                    'fallback_from_notification_id' => $notification->id,
                    'fallback_from_channel' => $notification->channel,
                ]),
            ]);

            // Track fallback
            $this->trackDelivery($notification, 'fallback_created', [
                'fallback_channel' => $fallbackChannel,
                'fallback_notification_id' => $fallbackNotification->id,
            ]);

            // Process immediately
            $result = $this->processNotification($fallbackNotification);

            if ($result) {
                Log::info('✅ Fallback channel successful', [
                    'original_channel' => $notification->channel,
                    'fallback_channel' => $fallbackChannel,
                    'fallback_notification_id' => $fallbackNotification->id,
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Fallback channel failed', [
                'fallback_channel' => $fallbackChannel,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Render notification content from template
     */
    protected function renderNotification(NotificationQueue $notification): array
    {
        // Get template
        $template = null;
        if ($notification->template_key) {
            $template = NotificationTemplate::where('key', $notification->template_key)
                ->where('channel', $notification->channel)
                ->first();
        }

        // Use template engine to render
        if ($template) {
            return $this->templateEngine->render(
                $template,
                $notification->data,
                $notification->language
            );
        }

        // Fallback to basic rendering
        return $this->renderBasicNotification(
            $notification->type,
            $notification->data,
            $notification->language,
            $notification->channel
        );
    }

    /**
     * Handle notification failure
     */
    protected function handleFailure(NotificationQueue $notification, string $error): void
    {
        // Log failure
        $this->trackDelivery($notification, 'failed', ['error' => $error]);

        // Try fallback channel first (if configured and not already a fallback)
        $fallbackChannel = $notification->metadata['fallback_channel'] ?? null;
        $isAlreadyFallback = isset($notification->metadata['fallback_from_notification_id']);

        if ($fallbackChannel && $fallbackChannel !== 'none' && !$isAlreadyFallback) {
            Log::info('🔄 Attempting cross-channel fallback', [
                'notification_id' => $notification->id,
                'original_channel' => $notification->channel,
                'fallback_channel' => $fallbackChannel,
            ]);

            $fallbackSuccess = $this->sendViaFallbackChannel($notification, $fallbackChannel);

            if ($fallbackSuccess) {
                // Fallback succeeded, mark original as failed but handled
                $notification->update([
                    'status' => 'failed_with_fallback',
                    'error_message' => "Failed on {$notification->channel}, succeeded on {$fallbackChannel}",
                ]);

                Log::info('✅ Fallback successful, original notification marked', [
                    'notification_id' => $notification->id,
                ]);

                return; // No need to retry original channel
            }
        }

        // Check retry policy
        $config = $this->getNotificationConfig($notification);
        $maxAttempts = $config?->retry_count ?? config('notifications.max_attempts', 3);

        if ($notification->attempts < $maxAttempts) {
            // Calculate retry delay using configured strategy
            $delayMinutes = $this->calculateRetryDelay($config, $notification->attempts);

            $notification->update([
                'status' => 'pending',
                'scheduled_at' => now()->addMinutes($delayMinutes)
            ]);

            Log::info('📅 Notification scheduled for retry', [
                'notification_id' => $notification->id,
                'attempt' => $notification->attempts,
                'retry_at' => $notification->scheduled_at,
                'delay_minutes' => $delayMinutes,
                'strategy' => $config?->metadata['retry_strategy'] ?? 'exponential',
            ]);
        } else {
            // Max attempts reached
            $notification->update(['status' => 'failed']);

            Log::error('❌ Notification permanently failed', [
                'notification_id' => $notification->id,
                'channel' => $notification->channel,
                'attempts' => $notification->attempts,
            ]);

            // Notify admins of permanent failure
            $this->notifyAdminOfFailure($notification, $error);
        }

        // Track in analytics
        $this->analytics->trackFailed($notification);
    }

    /**
     * Check if notification should be sent
     */
    protected function shouldSendNotification($notifiable, string $type): bool
    {
        // Check global kill switch
        if (config('notifications.disabled')) {
            return false;
        }

        // Check if notifiable can receive notifications
        if (method_exists($notifiable, 'canReceiveNotifications')) {
            return $notifiable->canReceiveNotifications($type);
        }

        // Check quiet hours
        $preferences = $this->getPreferences($notifiable);
        if ($preferences && $this->isInQuietHours($preferences)) {
            return false;
        }

        // Check frequency limits
        if ($this->exceededFrequencyLimit($notifiable, $type)) {
            return false;
        }

        return true;
    }

    /**
     * Get preferred channels for notifiable
     */
    protected function getPreferredChannels($notifiable, string $type): array
    {
        // If NotificationPreference model doesn't exist, use email as default
        if (!class_exists('App\\Models\\NotificationPreference')) {
            return $notifiable->email ? ['email'] : [];
        }

        $preferences = \App\Models\NotificationPreference::where('customer_id', $notifiable->id)
            ->where('enabled', true)
            ->get();

        $channels = [];

        foreach ($preferences as $pref) {
            // Check if type is allowed
            if ($pref->types && !in_array($type, $pref->types)) {
                continue;
            }

            // Check marketing consent for marketing messages
            if ($type === 'marketing' && !$pref->marketing_consent) {
                continue;
            }

            $channels[] = $pref->channel;
        }

        // Fallback to email if no preferences
        if (empty($channels) && $notifiable->email) {
            $channels = ['email'];
        }

        return $channels;
    }

    /**
     * Check if channel is enabled for user
     */
    protected function isChannelEnabled($notifiable, string $channel, string $type): bool
    {
        if (!class_exists('App\\Models\\NotificationPreference')) {
            return $this->getDefaultChannelSetting($channel, $type);
        }

        $preference = \App\Models\NotificationPreference::where('customer_id', $notifiable->id)
            ->where('channel', $channel)
            ->first();

        if (!$preference) {
            // No preference set, use defaults
            return $this->getDefaultChannelSetting($channel, $type);
        }

        if (!$preference->enabled) {
            return false;
        }

        // Check type-specific settings
        if ($preference->types && !in_array($type, $preference->types)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user is unsubscribed
     */
    protected function isUnsubscribed($notifiable, string $channel, string $type): bool
    {
        if (!class_exists('App\\Models\\NotificationUnsubscribe')) {
            return false; // No unsubscribe tracking, allow all notifications
        }

        return \App\Models\NotificationUnsubscribe::where(function ($query) use ($notifiable) {
                $query->where('customer_id', $notifiable->id)
                    ->orWhere('email', $notifiable->email)
                    ->orWhere('phone', $notifiable->phone);
            })
            ->where('channel', $channel)
            ->where(function ($query) use ($type) {
                $query->whereNull('type')
                    ->orWhere('type', $type);
            })
            ->exists();
    }

    /**
     * Check rate limits
     */
    protected function checkRateLimit(NotificationQueue $notification): bool
    {
        $key = "notification_rate:{$notification->channel}:{$notification->notifiable_id}";
        $limit = config("notifications.rate_limits.{$notification->channel}", 10);
        $window = 60; // 1 minute

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            Log::warning('Rate limit exceeded', [
                'channel' => $notification->channel,
                'notifiable_id' => $notification->notifiable_id,
                'current' => $current,
                'limit' => $limit
            ]);
            return false;
        }

        // Use put with TTL instead of increment + expire for compatibility with ArrayStore
        Cache::put($key, $current + 1, $window);

        return true;
    }

    /**
     * Track notification delivery
     */
    protected function trackDelivery(NotificationQueue $notification, string $event, array $data = []): void
    {
        DB::table('notification_deliveries')->insert([
            'notification_queue_id' => $notification->id,
            'event' => $event,
            'data' => json_encode($data),
            'provider' => $data['provider'] ?? null,
            'provider_status' => $data['status'] ?? null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Get language preference
     */
    protected function getLanguage($notifiable): string
    {
        if (method_exists($notifiable, 'getNotificationLanguage')) {
            return $notifiable->getNotificationLanguage();
        }

        if (property_exists($notifiable, 'notification_language')) {
            return $notifiable->notification_language;
        }

        return config('app.locale', 'de');
    }

    /**
     * Get recipient info for channel
     */
    protected function getRecipientInfo($notifiable, string $channel): array
    {
        switch ($channel) {
            case 'email':
                return [
                    'email' => $notifiable->email,
                    'name' => $notifiable->name ?? null
                ];

            case 'sms':
                return [
                    'phone' => $notifiable->phone,
                    'country_code' => $notifiable->country_code ?? '+49'
                ];

            case 'whatsapp':
                return [
                    'phone' => $notifiable->whatsapp_number ?? $notifiable->phone,
                    'name' => $notifiable->name
                ];

            case 'push':
                return [
                    'token' => $notifiable->push_token,
                    'platform' => $notifiable->push_platform,
                    'user_id' => $notifiable->id
                ];

            default:
                return [];
        }
    }

    /**
     * Initialize notification channels
     */
    protected function initializeChannels(): void
    {
        $channelClasses = [
            'email' => EmailChannel::class,
            'sms' => SmsChannel::class,
            'whatsapp' => WhatsAppChannel::class,
            'push' => PushChannel::class,
        ];

        foreach ($channelClasses as $name => $class) {
            try {
                $this->channels[$name] = app($class);
            } catch (\Exception $e) {
                // Log channel initialization failure but don't break the whole system
                Log::warning("Failed to initialize {$name} channel", [
                    'class' => $class,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Load notification providers
     */
    protected function loadProviders(): void
    {
        $this->providers = Cache::remember('notification_providers', 3600, function () {
            return NotificationProvider::where('is_active', true)
                ->orderBy('priority')
                ->get()
                ->groupBy('channel')
                ->toArray();
        });
    }

    /**
     * Get channel handler
     */
    protected function getChannel(string $channel)
    {
        if (!isset($this->channels[$channel])) {
            throw new \InvalidArgumentException("Unknown notification channel: {$channel}");
        }

        return $this->channels[$channel];
    }

    /**
     * Get template for channel and type
     */
    protected function getTemplate(string $channel, string $type, string $language): ?NotificationTemplate
    {
        return Cache::remember(
            "notification_template:{$channel}:{$type}:{$language}",
            3600,
            function () use ($channel, $type, $language) {
                return NotificationTemplate::where('channel', $channel)
                    ->where('type', $type)
                    ->where('is_active', true)
                    ->first();
            }
        );
    }

    /**
     * Get user preferences
     */
    protected function getPreferences($notifiable)
    {
        if (!class_exists('App\\Models\\NotificationPreference')) {
            return null;
        }

        return \App\Models\NotificationPreference::where('customer_id', $notifiable->id)->first();
    }

    /**
     * Check if currently in quiet hours
     */
    protected function isInQuietHours($preferences): bool
    {
        if (!$preferences || !$preferences->quiet_hours) {
            return false;
        }

        $now = now();
        $quietHours = $preferences->quiet_hours;

        if (isset($quietHours['start']) && isset($quietHours['end'])) {
            $start = Carbon::parse($quietHours['start']);
            $end = Carbon::parse($quietHours['end']);

            // Handle overnight quiet hours
            if ($end < $start) {
                return $now >= $start || $now <= $end;
            }

            return $now >= $start && $now <= $end;
        }

        return false;
    }

    /**
     * Check frequency limits
     */
    protected function exceededFrequencyLimit($notifiable, string $type): bool
    {
        $preferences = $this->getPreferences($notifiable);

        if (!$preferences || !$preferences->frequency_limits) {
            return false;
        }

        $limits = $preferences->frequency_limits;

        // Check daily limit
        if (isset($limits['daily'])) {
            $todayCount = NotificationQueue::where('notifiable_id', $notifiable->id)
                ->where('notifiable_type', get_class($notifiable))
                ->whereDate('created_at', today())
                ->where('status', 'sent')
                ->count();

            if ($todayCount >= $limits['daily']) {
                return true;
            }
        }

        // Check weekly limit
        if (isset($limits['weekly'])) {
            $weekCount = NotificationQueue::where('notifiable_id', $notifiable->id)
                ->where('notifiable_type', get_class($notifiable))
                ->where('created_at', '>=', now()->startOfWeek())
                ->where('status', 'sent')
                ->count();

            if ($weekCount >= $limits['weekly']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default channel setting
     */
    protected function getDefaultChannelSetting(string $channel, string $type): bool
    {
        $defaults = config('notifications.defaults', []);

        return $defaults[$channel][$type] ?? true;
    }

    /**
     * Render basic notification without template
     */
    protected function renderBasicNotification(
        string $type,
        array $data,
        string $language,
        string $channel
    ): array {
        // This would be customized based on your needs
        return [
            'subject' => $this->getDefaultSubject($type, $language),
            'content' => $this->getDefaultContent($type, $data, $language),
            'data' => $data
        ];
    }

    /**
     * Get default subject for notification type
     */
    protected function getDefaultSubject(string $type, string $language): string
    {
        $subjects = [
            'de' => [
                'confirmation' => 'Terminbestätigung',
                'reminder' => 'Terminerinnerung',
                'cancellation' => 'Termin storniert',
                'rescheduled' => 'Termin verschoben'
            ],
            'en' => [
                'confirmation' => 'Appointment Confirmation',
                'reminder' => 'Appointment Reminder',
                'cancellation' => 'Appointment Cancelled',
                'rescheduled' => 'Appointment Rescheduled'
            ]
        ];

        return $subjects[$language][$type] ?? ucfirst($type);
    }

    /**
     * Get default content for notification type
     */
    protected function getDefaultContent(string $type, array $data, string $language): string
    {
        // Basic content generation - would be more sophisticated in production
        return view("notifications.{$language}.{$type}", $data)->render();
    }

    /**
     * Notify admin of permanent failure
     */
    protected function notifyAdminOfFailure(NotificationQueue $notification, string $error): void
    {
        // Send alert to admin about failed notification
        Log::critical('Notification permanently failed', [
            'notification_id' => $notification->id,
            'channel' => $notification->channel,
            'type' => $notification->type,
            'error' => $error,
            'attempts' => $notification->attempts
        ]);
    }

    /**
     * Send appointment cancelled notification to customer
     */
    public function sendAppointmentCancelled($customer, array $appointmentData, string $channel = 'email'): array
    {
        return $this->send(
            notifiable: $customer,
            type: 'appointment_cancelled',
            data: $appointmentData,
            channels: [$channel],
            options: ['immediate' => true]
        );
    }

    /**
     * Notify staff member of appointment cancellation
     */
    public function notifyStaffOfCancellation($staff, array $appointmentData): array
    {
        return $this->send(
            notifiable: $staff,
            type: 'staff_appointment_cancelled',
            data: $appointmentData,
            channels: ['email'], // Staff typically get email notifications
            options: ['immediate' => true]
        );
    }

    /**
     * Notify manager of appointment cancellation
     */
    public function notifyManagerOfCancellation($manager, array $appointmentData): array
    {
        return $this->send(
            notifiable: $manager,
            type: 'manager_appointment_cancelled',
            data: $appointmentData,
            channels: ['email'], // Managers typically get email notifications
            options: ['immediate' => true]
        );
    }
}