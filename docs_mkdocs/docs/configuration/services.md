# Services Configuration

## Overview

This guide covers the configuration of all external services integrated with AskProAI. Each service has its own configuration file and environment variables.

## Service Configuration Files

### Directory Structure
```
config/
├── services.php      # Main services configuration
├── retell.php        # Retell.ai specific config
├── calcom.php        # Cal.com specific config
├── stripe.php        # Stripe/payment config
├── sms.php          # SMS providers config
├── mail.php         # Email services config
└── monitoring.php   # Monitoring services config
```

## Retell.ai Configuration

### Configuration File
```php
// config/retell.php
return [
    'api_key' => env('DEFAULT_RETELL_API_KEY'),
    'agent_id' => env('DEFAULT_RETELL_AGENT_ID'),
    'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    'base_url' => env('RETELL_BASE_URL', 'https://api.retellai.com'),
    'version' => env('RETELL_API_VERSION', 'v2'),
    
    'timeout' => env('RETELL_TIMEOUT', 30),
    'retry_times' => env('RETELL_RETRY_TIMES', 3),
    'retry_delay' => env('RETELL_RETRY_DELAY', 1000),
    
    'sandbox' => [
        'enabled' => env('RETELL_SANDBOX_MODE', false),
        'api_key' => env('RETELL_SANDBOX_API_KEY'),
        'agent_id' => env('RETELL_SANDBOX_AGENT_ID'),
    ],
    
    'features' => [
        'voice_biometrics' => env('RETELL_VOICE_BIOMETRICS', false),
        'sentiment_analysis' => env('RETELL_SENTIMENT_ANALYSIS', true),
        'language_detection' => env('RETELL_LANGUAGE_DETECTION', true),
    ],
    
    'webhooks' => [
        'events' => ['call_started', 'call_ended', 'call_analyzed'],
        'signature_header' => 'x-retell-signature',
        'tolerance' => 300, // 5 minutes
    ],
];
```

### Service Registration
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(RetellService::class, function ($app) {
        $config = config('retell');
        
        if ($config['sandbox']['enabled']) {
            return new RetellSandboxService($config['sandbox']);
        }
        
        return new RetellService($config);
    });
}
```

### Multi-Tenant Configuration
```php
// Company-specific Retell configuration
class CompanyRetellService
{
    public function getConfig(Company $company): array
    {
        return [
            'api_key' => $company->retell_api_key ?? config('retell.api_key'),
            'agent_id' => $company->retell_agent_id ?? config('retell.agent_id'),
            'voice_settings' => $company->retell_voice_settings ?? [
                'voice_id' => 'eleven_multilingual_v2',
                'language' => 'de-DE',
                'speed' => 1.0,
            ],
        ];
    }
}
```

## Cal.com Configuration

### Configuration File
```php
// config/calcom.php
return [
    'api_key' => env('DEFAULT_CALCOM_API_KEY'),
    'team_slug' => env('DEFAULT_CALCOM_TEAM_SLUG'),
    'organization_id' => env('CALCOM_ORGANIZATION_ID'),
    'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
    
    'api' => [
        'base_url' => env('CALCOM_API_BASE_URL', 'https://api.cal.com'),
        'version' => env('CALCOM_API_VERSION', 'v2'),
        'timeout' => env('CALCOM_TIMEOUT', 30),
    ],
    
    'cache' => [
        'ttl' => env('CALCOM_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'calcom',
    ],
    
    'booking' => [
        'default_timezone' => env('CALCOM_DEFAULT_TIMEZONE', 'Europe/Berlin'),
        'buffer_time' => env('CALCOM_BUFFER_TIME', 0),
        'future_days_limit' => env('CALCOM_FUTURE_DAYS', 60),
        'min_notice_hours' => env('CALCOM_MIN_NOTICE', 24),
    ],
    
    'webhooks' => [
        'events' => [
            'BOOKING_CREATED',
            'BOOKING_CANCELLED',
            'BOOKING_RESCHEDULED',
            'BOOKING_REQUESTED',
        ],
        'signature_header' => 'X-Cal-Signature-256',
    ],
];
```

### Service Factory
```php
// app/Services/Calendar/CalendarServiceFactory.php
class CalendarServiceFactory
{
    public function make(string $provider, array $config = []): CalendarServiceInterface
    {
        return match($provider) {
            'calcom' => new CalcomV2Service($config),
            'google' => new GoogleCalendarService($config),
            'outlook' => new OutlookCalendarService($config),
            default => throw new UnsupportedCalendarException($provider),
        };
    }
}
```

## SMS Configuration

### Multi-Provider Setup
```php
// config/sms.php
return [
    'default' => env('SMS_PROVIDER', 'twilio'),
    'fallback' => env('SMS_FALLBACK_PROVIDER', 'messagebird'),
    
    'providers' => [
        'twilio' => [
            'driver' => 'twilio',
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
            'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
            'status_callback' => env('APP_URL') . '/webhooks/twilio/status',
        ],
        
        'messagebird' => [
            'driver' => 'messagebird',
            'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),
            'originator' => env('MESSAGEBIRD_ORIGINATOR', 'AskProAI'),
            'datacoding' => 'auto',
        ],
        
        'vonage' => [
            'driver' => 'vonage',
            'key' => env('VONAGE_KEY'),
            'secret' => env('VONAGE_SECRET'),
            'from' => env('VONAGE_SMS_FROM', 'AskProAI'),
        ],
    ],
    
    'rate_limits' => [
        'per_minute' => env('SMS_RATE_LIMIT_MINUTE', 60),
        'per_hour' => env('SMS_RATE_LIMIT_HOUR', 1000),
        'per_day' => env('SMS_RATE_LIMIT_DAY', 10000),
    ],
    
    'templates' => [
        'path' => resource_path('sms-templates'),
        'cache' => env('SMS_TEMPLATE_CACHE', true),
        'locales' => ['de', 'en', 'es', 'fr'],
    ],
];
```

### Provider Manager
```php
// app/Services/SMS/SmsManager.php
class SmsManager
{
    private array $providers = [];
    private CircuitBreaker $circuitBreaker;
    
    public function send(string $to, string $message): bool
    {
        $provider = $this->getAvailableProvider();
        
        try {
            return $this->circuitBreaker->call(
                $provider->getName(),
                fn() => $provider->send($to, $message)
            );
        } catch (CircuitOpenException $e) {
            // Try fallback provider
            return $this->sendWithFallback($to, $message);
        }
    }
    
    private function getAvailableProvider(): SmsProviderInterface
    {
        $default = config('sms.default');
        
        if ($this->circuitBreaker->isAvailable($default)) {
            return $this->providers[$default];
        }
        
        // Find first available provider
        foreach ($this->providers as $name => $provider) {
            if ($this->circuitBreaker->isAvailable($name)) {
                return $provider;
            }
        }
        
        throw new NoAvailableSmsProviderException();
    }
}
```

## Email Configuration

### Multi-Mailer Setup
```php
// config/mail.php
return [
    'default' => env('MAIL_MAILER', 'smtp'),
    
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],
        
        'mailgun' => [
            'transport' => 'mailgun',
            'domain' => env('MAILGUN_DOMAIN'),
            'secret' => env('MAILGUN_SECRET'),
            'endpoint' => env('MAILGUN_ENDPOINT', 'api.eu.mailgun.net'),
        ],
        
        'postmark' => [
            'transport' => 'postmark',
            'token' => env('POSTMARK_TOKEN'),
            'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        ],
        
        'ses' => [
            'transport' => 'ses',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
        ],
    ],
    
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@askproai.de'),
        'name' => env('MAIL_FROM_NAME', 'AskProAI'),
    ],
    
    'markdown' => [
        'theme' => 'askproai',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];
```

### Email Service Manager
```php
// app/Services/Email/EmailServiceManager.php
class EmailServiceManager
{
    public function sendTransactional(Mailable $mailable, string $to): void
    {
        // Use high-priority mailer for transactional emails
        Mail::mailer('postmark')
            ->to($to)
            ->queue($mailable->onQueue('emails-priority'));
    }
    
    public function sendMarketing(Mailable $mailable, Collection $recipients): void
    {
        // Use bulk mailer for marketing
        Mail::mailer('mailgun')
            ->bcc($recipients->pluck('email'))
            ->queue($mailable->onQueue('emails-bulk'));
    }
    
    public function sendSystemAlert(string $message, string $to): void
    {
        // Use most reliable mailer for system alerts
        Mail::mailer('ses')
            ->to($to)
            ->send(new SystemAlert($message));
    }
}
```

## Payment Configuration

### Stripe Setup
```php
// config/stripe.php
return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
    
    'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),
    
    'plans' => [
        'starter' => [
            'price_id' => env('STRIPE_PRICE_STARTER'),
            'features' => [
                'calls_per_month' => 100,
                'branches' => 1,
                'users' => 3,
            ],
        ],
        'professional' => [
            'price_id' => env('STRIPE_PRICE_PROFESSIONAL'),
            'features' => [
                'calls_per_month' => 500,
                'branches' => 3,
                'users' => 10,
            ],
        ],
        'enterprise' => [
            'price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'features' => [
                'calls_per_month' => 'unlimited',
                'branches' => 'unlimited',
                'users' => 'unlimited',
            ],
        ],
    ],
    
    'metered_billing' => [
        'call_overage' => env('STRIPE_PRICE_PER_CALL'),
        'sms_overage' => env('STRIPE_PRICE_PER_SMS'),
    ],
    
    'currency' => env('CASHIER_CURRENCY', 'eur'),
    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'de_DE'),
];
```

## Monitoring Services

### Configuration
```php
// config/monitoring.php
return [
    'sentry' => [
        'enabled' => env('SENTRY_ENABLED', true),
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
    ],
    
    'newrelic' => [
        'enabled' => env('NEW_RELIC_ENABLED', false),
        'app_name' => env('NEW_RELIC_APP_NAME'),
        'license_key' => env('NEW_RELIC_LICENSE_KEY'),
    ],
    
    'cloudwatch' => [
        'enabled' => env('CLOUDWATCH_ENABLED', false),
        'region' => env('AWS_DEFAULT_REGION'),
        'log_group' => env('CLOUDWATCH_LOG_GROUP'),
        'log_stream' => env('CLOUDWATCH_LOG_STREAM'),
    ],
    
    'health_checks' => [
        'database' => true,
        'redis' => true,
        'retell' => true,
        'calcom' => true,
        'stripe' => true,
        'mail' => true,
        'sms' => true,
    ],
];
```

## Service Health Checks

### Health Check Configuration
```php
// app/Services/HealthCheck/HealthCheckManager.php
class HealthCheckManager
{
    private array $checks = [];
    
    public function register(string $name, HealthCheckInterface $check): void
    {
        $this->checks[$name] = $check;
    }
    
    public function runAll(): HealthReport
    {
        $results = [];
        
        foreach ($this->checks as $name => $check) {
            try {
                $results[$name] = $check->check();
            } catch (\Exception $e) {
                $results[$name] = new HealthCheckResult(
                    status: 'failed',
                    message: $e->getMessage()
                );
            }
        }
        
        return new HealthReport($results);
    }
}
```

### Service-Specific Health Checks
```php
// app/Services/HealthCheck/Checks/RetellHealthCheck.php
class RetellHealthCheck implements HealthCheckInterface
{
    public function check(): HealthCheckResult
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . config('retell.api_key')])
                ->get(config('retell.base_url') . '/health');
            
            if ($response->successful()) {
                return new HealthCheckResult('healthy', 'Retell API is operational');
            }
            
            return new HealthCheckResult('degraded', 'Retell API returned ' . $response->status());
        } catch (\Exception $e) {
            return new HealthCheckResult('unhealthy', 'Retell API is unreachable: ' . $e->getMessage());
        }
    }
}
```

## Circuit Breaker Configuration

### Global Circuit Breaker Settings
```php
// config/circuit-breaker.php
return [
    'defaults' => [
        'failure_threshold' => 5,
        'success_threshold' => 2,
        'timeout' => 60,
        'time_window' => 120,
    ],
    
    'services' => [
        'retell' => [
            'failure_threshold' => 3,
            'timeout' => 30,
        ],
        'calcom' => [
            'failure_threshold' => 5,
            'timeout' => 60,
        ],
        'stripe' => [
            'failure_threshold' => 2,
            'timeout' => 120,
        ],
        'sms' => [
            'failure_threshold' => 10,
            'timeout' => 300,
        ],
    ],
];
```

## Rate Limiting Configuration

### API Rate Limits
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// config/rate-limiting.php
return [
    'api' => [
        'default' => env('API_RATE_LIMIT', 60),
        'premium' => env('API_RATE_LIMIT_PREMIUM', 300),
        'enterprise' => env('API_RATE_LIMIT_ENTERPRISE', 1000),
    ],
    
    'webhooks' => [
        'retell' => 100,
        'calcom' => 100,
        'stripe' => 50,
    ],
    
    'internal' => [
        'sms' => 60,
        'email' => 100,
        'calls' => 30,
    ],
];
```

## Feature Flag Configuration

### Feature Management
```php
// config/features.php
return [
    'customer_portal' => env('FEATURE_CUSTOMER_PORTAL', false),
    'sms_notifications' => env('FEATURE_SMS_NOTIFICATIONS', true),
    'whatsapp_integration' => env('FEATURE_WHATSAPP_INTEGRATION', false),
    'multi_language' => env('FEATURE_MULTI_LANGUAGE', true),
    'advanced_analytics' => env('FEATURE_ADVANCED_ANALYTICS', true),
    'unified_services' => env('FEATURE_UNIFIED_SERVICES', false),
    
    'experimental' => [
        'ai_insights' => env('FEATURE_AI_INSIGHTS', false),
        'voice_biometrics' => env('FEATURE_VOICE_BIOMETRICS', false),
        'predictive_scheduling' => env('FEATURE_PREDICTIVE_SCHEDULING', false),
    ],
];

// Usage
if (feature('customer_portal')) {
    // Enable customer portal routes
}
```

## Related Documentation
- [Environment Configuration](environment.md)
- [Security Configuration](security.md)
- [Cache Configuration](cache.md)
- [Queue Configuration](queues.md)