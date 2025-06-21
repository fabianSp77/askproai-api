<?php

namespace App\Services\Monitoring;

use Sentry\Event;
use Sentry\EventHint;
use Illuminate\Support\Str;

class SentryBeforeSend
{
    /**
     * Handle Sentry event before sending
     * Used to filter sensitive data and add custom context
     */
    public function handle(Event $event, EventHint $hint): ?Event
    {
        // Add tenant context if available
        if ($tenant = auth()->user()?->company) {
            $event->setContext('tenant', [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->subscription_plan ?? 'free',
            ]);
        }

        // Add Stripe context for payment-related errors
        if ($this->isStripeRelated($event)) {
            $event->setContext('stripe', [
                'mode' => config('services.stripe.mode', 'test'),
                'webhook_endpoint' => request()->path() === 'api/stripe/webhook',
            ]);
        }

        // Add customer portal context
        if ($this->isPortalRelated($event)) {
            $event->setContext('customer_portal', [
                'user_id' => session('customer_id'),
                'page' => request()->path(),
                'authenticated' => session()->has('customer_id'),
            ]);
        }

        // Filter sensitive data from the event
        $event = $this->filterSensitiveData($event);

        // Don't send events for known issues in development
        if ($this->shouldIgnoreEvent($event)) {
            return null;
        }

        return $event;
    }

    /**
     * Check if the event is Stripe-related
     */
    private function isStripeRelated(Event $event): bool
    {
        $message = $event->getMessage() ?? '';
        $exceptions = $event->getExceptions();
        
        if (Str::contains($message, ['stripe', 'payment', 'subscription'])) {
            return true;
        }

        foreach ($exceptions as $exception) {
            if (Str::contains($exception->getType(), 'Stripe')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the event is customer portal related
     */
    private function isPortalRelated(Event $event): bool
    {
        $request = request();
        
        return Str::startsWith($request->path(), ['portal/', 'customer/']) ||
               $request->route()?->getPrefix() === 'portal';
    }

    /**
     * Filter sensitive data from the event
     */
    private function filterSensitiveData(Event $event): Event
    {
        $sensitiveKeys = [
            'password',
            'stripe_secret',
            'api_key',
            'token',
            'secret',
            'card_number',
            'cvv',
            'ssn',
            'tax_id',
            'bank_account',
        ];

        // Filter request data
        $request = $event->getRequest();
        if ($request) {
            foreach (['data', 'query_string', 'cookies', 'headers'] as $key) {
                if (isset($request[$key])) {
                    $request[$key] = $this->recursiveFilter($request[$key], $sensitiveKeys);
                }
            }
            $event->setRequest($request);
        }

        // Filter extra context
        $extra = $event->getExtra();
        if ($extra) {
            $event->setExtra($this->recursiveFilter($extra, $sensitiveKeys));
        }

        return $event;
    }

    /**
     * Recursively filter sensitive data
     */
    private function recursiveFilter($data, array $sensitiveKeys): array
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveFilter($value, $sensitiveKeys);
            } elseif ($this->isSensitiveKey($key, $sensitiveKeys)) {
                $data[$key] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * Check if a key is sensitive
     */
    private function isSensitiveKey(string $key, array $sensitiveKeys): bool
    {
        $lowerKey = strtolower($key);
        
        foreach ($sensitiveKeys as $sensitive) {
            if (Str::contains($lowerKey, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if an event should be ignored
     */
    private function shouldIgnoreEvent(Event $event): bool
    {
        // Ignore certain errors in non-production environments
        if (app()->environment(['local', 'testing'])) {
            $ignoredMessages = [
                'Failed to authenticate on SMTP server',
                'Connection refused',
                'Failed to connect to',
            ];

            $message = $event->getMessage() ?? '';
            foreach ($ignoredMessages as $ignored) {
                if (Str::contains($message, $ignored)) {
                    return true;
                }
            }
        }

        return false;
    }
}