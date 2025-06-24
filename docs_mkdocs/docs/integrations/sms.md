# SMS Integration

## Overview

SMS integration enables AskProAI to send appointment confirmations, reminders, and notifications via text message. The system supports multiple SMS providers with automatic failover.

## Supported Providers

### Primary Providers
- **Twilio** - Global coverage, reliable delivery
- **Vonage (Nexmo)** - European focus, good pricing
- **MessageBird** - EU-based, GDPR compliant
- **Plivo** - Cost-effective, good API

### Configuration
```bash
# SMS Provider Configuration
SMS_PROVIDER=twilio
SMS_FALLBACK_PROVIDER=messagebird

***REMOVED***
TWILIO_SID=ACxxxxxxxxxxxxxx
TWILIO_TOKEN=xxxxxxxxxxxxxx
TWILIO_FROM=+4930xxxxxxx

# MessageBird (Fallback)
MESSAGEBIRD_ACCESS_KEY=xxxxxxxxxxxxxx
MESSAGEBIRD_ORIGINATOR=AskProAI
```

## SMS Service Implementation

### Service Interface
```php
namespace App\Services\SMS;

interface SMSProviderInterface
{
    public function send(string $to, string $message): SMSResponse;
    public function sendBulk(array $recipients, string $message): array;
    public function getBalance(): float;
    public function checkStatus(string $messageId): string;
}
```

### Multi-Provider Service
```php
namespace App\Services;

class SMSService
{
    private array $providers;
    private SMSProviderInterface $activeProvider;
    
    public function __construct()
    {
        $this->providers = [
            'twilio' => app(TwilioProvider::class),
            'messagebird' => app(MessageBirdProvider::class),
            'vonage' => app(VonageProvider::class),
        ];
        
        $this->activeProvider = $this->providers[config('sms.provider')];
    }
    
    public function send(string $to, string $message): bool
    {
        try {
            // Format phone number
            $to = $this->formatPhoneNumber($to);
            
            // Try primary provider
            $response = $this->activeProvider->send($to, $message);
            
            // Log success
            $this->logMessage($to, $message, $response);
            
            return true;
            
        } catch (\Exception $e) {
            // Try fallback provider
            return $this->sendWithFallback($to, $message);
        }
    }
    
    private function sendWithFallback(string $to, string $message): bool
    {
        $fallbackProvider = $this->providers[config('sms.fallback_provider')];
        
        try {
            $response = $fallbackProvider->send($to, $message);
            $this->logMessage($to, $message, $response, 'fallback');
            
            // Alert about primary provider failure
            $this->alertProviderFailure($this->activeProvider);
            
            return true;
        } catch (\Exception $e) {
            Log::error('SMS failed on all providers', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
```

## Provider Implementations

##***REMOVED*** Provider
```php
namespace App\Services\SMS\Providers;

use Twilio\Rest\Client;

class TwilioProvider implements SMSProviderInterface
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }
    
    public function send(string $to, string $message): SMSResponse
    {
        $response = $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message
        ]);
        
        return new SMSResponse([
            'id' => $response->sid,
            'status' => $response->status,
            'price' => $response->price,
            'provider' => 'twilio'
        ]);
    }
    
    public function getBalance(): float
    {
        $account = $this->client->api->v2010->accounts(config('services.twilio.sid'))->fetch();
        return (float) $account->balance;
    }
}
```

### MessageBird Provider
```php
namespace App\Services\SMS\Providers;

use MessageBird\Client;

class MessageBirdProvider implements SMSProviderInterface
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client(config('services.messagebird.access_key'));
    }
    
    public function send(string $to, string $message): SMSResponse
    {
        $sms = new \MessageBird\Objects\Message();
        $sms->originator = config('services.messagebird.originator');
        $sms->recipients = [$to];
        $sms->body = $message;
        
        $response = $this->client->messages->create($sms);
        
        return new SMSResponse([
            'id' => $response->id,
            'status' => $response->recipients->items[0]->status,
            'provider' => 'messagebird'
        ]);
    }
}
```

## Message Templates

### Template Management
```php
namespace App\Services\SMS;

class SMSTemplateService
{
    private array $templates = [
        'appointment_confirmation' => [
            'de' => 'Termin bestätigt: {date} um {time} bei {company}. Adresse: {address}',
            'en' => 'Appointment confirmed: {date} at {time} with {company}. Address: {address}'
        ],
        'appointment_reminder' => [
            'de' => 'Erinnerung: Ihr Termin bei {company} ist morgen um {time}',
            'en' => 'Reminder: Your appointment with {company} is tomorrow at {time}'
        ],
        'appointment_cancelled' => [
            'de' => 'Ihr Termin bei {company} am {date} wurde storniert',
            'en' => 'Your appointment with {company} on {date} has been cancelled'
        ]
    ];
    
    public function render(string $template, array $data, string $locale = 'de'): string
    {
        $text = $this->templates[$template][$locale] ?? $this->templates[$template]['de'];
        
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        
        return $text;
    }
}
```

### Using Templates
```php
// Send appointment confirmation
$template = app(SMSTemplateService::class);
$sms = app(SMSService::class);

$message = $template->render('appointment_confirmation', [
    'date' => $appointment->date->format('d.m.Y'),
    'time' => $appointment->time->format('H:i'),
    'company' => $appointment->branch->company->name,
    'address' => $appointment->branch->address
]);

$sms->send($appointment->customer->phone, $message);
```

## Notification Jobs

### Appointment Confirmation Job
```php
namespace App\Jobs;

class SendSMSConfirmation extends Job
{
    use Dispatchable, InteractsWithQueue, Queueable;
    
    private Appointment $appointment;
    
    public function handle()
    {
        // Check if customer wants SMS
        if (!$this->appointment->customer->sms_notifications) {
            return;
        }
        
        $sms = app(SMSService::class);
        $template = app(SMSTemplateService::class);
        
        $message = $template->render('appointment_confirmation', [
            'date' => $this->appointment->date->format('d.m.Y'),
            'time' => $this->appointment->time->format('H:i'),
            'company' => $this->appointment->branch->company->name,
            'address' => $this->appointment->branch->full_address
        ]);
        
        $sent = $sms->send($this->appointment->customer->phone, $message);
        
        // Update appointment
        $this->appointment->update([
            'sms_confirmation_sent' => $sent,
            'sms_confirmation_sent_at' => now()
        ]);
    }
}
```

### Reminder Scheduler
```php
namespace App\Console\Commands;

class ScheduleSMSReminders extends Command
{
    protected $signature = 'sms:schedule-reminders';
    
    public function handle()
    {
        // 24-hour reminders
        $tomorrow = now()->addDay();
        $appointments = Appointment::whereDate('date', $tomorrow->toDateString())
            ->where('sms_reminder_sent', false)
            ->get();
        
        foreach ($appointments as $appointment) {
            SendSMSReminder::dispatch($appointment, '24h');
        }
        
        // 1-hour reminders
        $inOneHour = now()->addHour();
        $appointments = Appointment::where('datetime', '>=', $inOneHour)
            ->where('datetime', '<=', $inOneHour->copy()->addMinutes(15))
            ->where('sms_1h_reminder_sent', false)
            ->get();
        
        foreach ($appointments as $appointment) {
            SendSMSReminder::dispatch($appointment, '1h');
        }
    }
}
```

## Phone Number Management

### Number Formatting
```php
namespace App\Services\SMS;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneNumberFormatter
{
    private PhoneNumberUtil $phoneUtil;
    
    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }
    
    public function format(string $number, string $country = 'DE'): string
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($number, $country);
            
            if (!$this->phoneUtil->isValidNumber($phoneNumber)) {
                throw new \Exception('Invalid phone number');
            }
            
            return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (\Exception $e) {
            Log::warning('Phone number formatting failed', [
                'number' => $number,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to basic formatting
            return $this->basicFormat($number);
        }
    }
}
```

## Cost Tracking

### SMS Cost Calculator
```php
namespace App\Services\SMS;

class SMSCostTracker
{
    private array $rates = [
        'DE' => 0.075,  // EUR per SMS
        'AT' => 0.080,
        'CH' => 0.085,
        'default' => 0.10
    ];
    
    public function trackMessage(string $to, string $provider, ?float $actualCost = null)
    {
        $country = $this->getCountryCode($to);
        $estimatedCost = $this->rates[$country] ?? $this->rates['default'];
        
        SMSLog::create([
            'to' => $to,
            'provider' => $provider,
            'estimated_cost' => $estimatedCost,
            'actual_cost' => $actualCost,
            'sent_at' => now()
        ]);
    }
    
    public function getMonthlyCost(Company $company): array
    {
        $logs = SMSLog::where('company_id', $company->id)
            ->whereMonth('sent_at', now()->month)
            ->get();
        
        return [
            'total_messages' => $logs->count(),
            'estimated_cost' => $logs->sum('estimated_cost'),
            'actual_cost' => $logs->sum('actual_cost'),
            'by_provider' => $logs->groupBy('provider')->map->count()
        ];
    }
}
```

## Opt-Out Management

### Unsubscribe Handling
```php
namespace App\Services\SMS;

class SMSOptOutService
{
    private array $optOutKeywords = [
        'STOP', 'STOPP', 'ABMELDEN', 'UNSUBSCRIBE', 'CANCEL'
    ];
    
    public function processIncomingSMS(string $from, string $message)
    {
        $message = strtoupper(trim($message));
        
        if (in_array($message, $this->optOutKeywords)) {
            $this->handleOptOut($from);
        }
    }
    
    private function handleOptOut(string $phoneNumber)
    {
        // Find customer
        $customer = Customer::where('phone', $phoneNumber)->first();
        
        if ($customer) {
            $customer->update(['sms_notifications' => false]);
            
            // Send confirmation
            app(SMSService::class)->send(
                $phoneNumber,
                'Sie wurden erfolgreich von SMS-Benachrichtigungen abgemeldet.'
            );
            
            // Log opt-out
            OptOutLog::create([
                'customer_id' => $customer->id,
                'channel' => 'sms',
                'opted_out_at' => now()
            ]);
        }
    }
}
```

## Testing

### SMS Testing Service
```php
namespace App\Services\SMS;

class SMSTestService
{
    public function sendTestSMS(string $to, string $provider = null)
    {
        $providers = $provider ? [$provider] : ['twilio', 'messagebird', 'vonage'];
        $results = [];
        
        foreach ($providers as $providerName) {
            try {
                $provider = app("sms.provider.{$providerName}");
                $response = $provider->send($to, "Test SMS from AskProAI via {$providerName}");
                
                $results[$providerName] = [
                    'success' => true,
                    'message_id' => $response->id,
                    'cost' => $response->price
                ];
            } catch (\Exception $e) {
                $results[$providerName] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
```

### Test Commands
```bash
# Test SMS sending
php artisan sms:test +49xxxxxxxxx --provider=twilio

# Check provider balance
php artisan sms:balance

# Verify phone number
php artisan sms:verify +49xxxxxxxxx
```

## Monitoring & Alerts

### Delivery Monitoring
```php
class SMSMonitor
{
    public function checkDeliveryRates()
    {
        $stats = SMSLog::where('created_at', '>=', now()->subHour())
            ->selectRaw('
                provider,
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('provider')
            ->get();
        
        foreach ($stats as $stat) {
            $deliveryRate = $stat->delivered / $stat->total * 100;
            
            if ($deliveryRate < 95) {
                $this->alertLowDeliveryRate($stat->provider, $deliveryRate);
            }
        }
    }
}
```

## Related Documentation
- [WhatsApp Integration](whatsapp.md)
- [Email Integration](email.md)
- [Notification System](../features/notifications.md)