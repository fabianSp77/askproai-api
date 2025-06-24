# WhatsApp Integration

## Overview

WhatsApp integration allows AskProAI to send appointment confirmations, reminders, and enable two-way communication with customers via WhatsApp Business API.

## Status

⚠️ **Note**: WhatsApp integration is planned but not yet implemented. This documentation outlines the planned implementation.

## Planned Features

### Messaging Capabilities
- Appointment confirmations
- Reminder notifications (24h, 1h before)
- Rescheduling requests
- Two-way chat support
- Rich media messages (location maps, documents)

### WhatsApp Business API Setup

#### Prerequisites
```yaml
requirements:
  - WhatsApp Business Account
  - Facebook Business Manager
  - Verified Business
  - Approved Message Templates
  - Phone Number (not used for WhatsApp before)
```

#### Configuration
```bash
# Environment Variables
WHATSAPP_API_URL=https://graph.facebook.com/v17.0
WHATSAPP_PHONE_NUMBER_ID=1234567890
WHATSAPP_BUSINESS_ACCOUNT_ID=987654321
WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxxxxxxx
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your-verify-token
```

## Message Templates

### Appointment Confirmation Template
```json
{
  "name": "appointment_confirmation_de",
  "language": "de",
  "category": "TRANSACTIONAL",
  "components": [
    {
      "type": "HEADER",
      "format": "TEXT",
      "text": "Terminbestätigung"
    },
    {
      "type": "BODY",
      "text": "Hallo {{1}}, Ihr Termin bei {{2}} wurde bestätigt:\n\n📅 Datum: {{3}}\n⏰ Zeit: {{4}}\n📍 Ort: {{5}}\n\nBitte antworten Sie mit 'JA' zur Bestätigung oder 'NEIN' zum Stornieren."
    },
    {
      "type": "FOOTER",
      "text": "AskProAI - Ihr digitaler Assistent"
    }
  ]
}
```

### Reminder Template
```json
{
  "name": "appointment_reminder_de",
  "language": "de",
  "category": "TRANSACTIONAL",
  "components": [
    {
      "type": "BODY",
      "text": "Erinnerung: Sie haben morgen um {{1}} einen Termin bei {{2}}. Adresse: {{3}}"
    }
  ]
}
```

## Implementation Architecture

### Service Layer
```php
namespace App\Services;

class WhatsAppService
{
    private $client;
    private $phoneNumberId;
    
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('services.whatsapp.api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.whatsapp.access_token'),
                'Content-Type' => 'application/json'
            ]
        ]);
        
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }
    
    public function sendTemplate(string $to, string $template, array $parameters)
    {
        $response = $this->client->post("/{$this->phoneNumberId}/messages", [
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'de'],
                    'components' => $this->buildComponents($parameters)
                ]
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    public function sendMessage(string $to, string $message)
    {
        $response = $this->client->post("/{$this->phoneNumberId}/messages", [
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($to),
                'type' => 'text',
                'text' => ['body' => $message]
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }
}
```

### Webhook Handler
```php
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');
        
        if ($request->get('hub_verify_token') === $verifyToken) {
            return response($request->get('hub_challenge'));
        }
        
        abort(403);
    }
    
    public function handle(Request $request)
    {
        $data = $request->all();
        
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'messages') {
                    $this->processMessage($change['value']);
                }
            }
        }
        
        return response('EVENT_RECEIVED');
    }
    
    private function processMessage($value)
    {
        if (!isset($value['messages'])) {
            return;
        }
        
        foreach ($value['messages'] as $message) {
            ProcessWhatsAppMessage::dispatch($message);
        }
    }
}
```

## Message Processing

### Appointment Confirmation Flow
```php
class SendWhatsAppConfirmation extends Job
{
    public function handle()
    {
        $whatsapp = app(WhatsAppService::class);
        
        $parameters = [
            $this->appointment->customer->name,
            $this->appointment->branch->name,
            $this->appointment->date->format('d.m.Y'),
            $this->appointment->time->format('H:i'),
            $this->appointment->branch->full_address
        ];
        
        $whatsapp->sendTemplate(
            $this->appointment->customer->phone,
            'appointment_confirmation_de',
            $parameters
        );
        
        // Log message
        WhatsAppMessage::create([
            'appointment_id' => $this->appointment->id,
            'customer_id' => $this->appointment->customer_id,
            'type' => 'template',
            'template' => 'appointment_confirmation_de',
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }
}
```

### Interactive Messages
```php
// Send interactive button message
$whatsapp->sendInteractive($to, [
    'type' => 'button',
    'body' => [
        'text' => 'Möchten Sie Ihren Termin bestätigen?'
    ],
    'action' => [
        'buttons' => [
            ['type' => 'reply', 'reply' => ['id' => 'confirm', 'title' => 'Bestätigen']],
            ['type' => 'reply', 'reply' => ['id' => 'cancel', 'title' => 'Stornieren']],
            ['type' => 'reply', 'reply' => ['id' => 'reschedule', 'title' => 'Verschieben']]
        ]
    ]
]);
```

## Opt-in Management

### Customer Preferences
```php
Schema::table('customers', function (Blueprint $table) {
    $table->boolean('whatsapp_opted_in')->default(false);
    $table->timestamp('whatsapp_opted_in_at')->nullable();
    $table->string('whatsapp_number')->nullable();
});
```

### Opt-in Flow
```php
class WhatsAppOptInService
{
    public function requestOptIn(Customer $customer)
    {
        // Send opt-in request via SMS or email
        $message = "Möchten Sie Terminerinnerungen per WhatsApp erhalten? 
                   Antworten Sie mit JA auf diese Nachricht.";
        
        app(SMSService::class)->send($customer->phone, $message);
    }
    
    public function processOptIn(string $phone, bool $accepted)
    {
        $customer = Customer::where('phone', $phone)->first();
        
        if ($customer && $accepted) {
            $customer->update([
                'whatsapp_opted_in' => true,
                'whatsapp_opted_in_at' => now(),
                'whatsapp_number' => $phone
            ]);
            
            // Send welcome message
            app(WhatsAppService::class)->sendTemplate(
                $phone,
                'welcome_whatsapp',
                [$customer->name]
            );
        }
    }
}
```

## Conversation Management

### Two-Way Communication
```php
class WhatsAppConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'appointment_id',
        'status', // open, closed
        'last_message_at',
        'assigned_to' // staff member handling conversation
    ];
    
    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }
}
```

### Auto-Response System
```php
class WhatsAppAutoResponder
{
    private $patterns = [
        '/termin|appointment/i' => 'appointment_query',
        '/stornieren|cancel/i' => 'cancellation_request',
        '/verschieben|reschedule/i' => 'reschedule_request',
        '/öffnungszeiten|hours/i' => 'business_hours',
        '/adresse|location/i' => 'location_info'
    ];
    
    public function processMessage($message)
    {
        foreach ($this->patterns as $pattern => $intent) {
            if (preg_match($pattern, $message['text']['body'])) {
                return $this->handleIntent($intent, $message);
            }
        }
        
        return $this->defaultResponse($message);
    }
}
```

## Media Handling

### Location Sharing
```php
// Send location for appointment
$whatsapp->sendLocation($to, [
    'latitude' => $branch->latitude,
    'longitude' => $branch->longitude,
    'name' => $branch->name,
    'address' => $branch->full_address
]);
```

### Document Sending
```php
// Send appointment confirmation PDF
$whatsapp->sendDocument($to, [
    'link' => $appointment->confirmation_pdf_url,
    'caption' => 'Ihre Terminbestätigung'
]);
```

## Analytics & Monitoring

### Message Tracking
```php
class WhatsAppAnalytics
{
    public function getMetrics($companyId, $period = 'month')
    {
        return [
            'messages_sent' => WhatsAppMessage::sent()->period($period)->count(),
            'templates_used' => WhatsAppMessage::templates()->period($period)->count(),
            'conversations' => WhatsAppConversation::period($period)->count(),
            'response_rate' => $this->calculateResponseRate($companyId, $period),
            'opt_in_rate' => $this->calculateOptInRate($companyId, $period)
        ];
    }
}
```

## Cost Management

### Message Pricing
```php
class WhatsAppCostCalculator
{
    private $pricing = [
        'DE' => [
            'template' => 0.0551, // EUR per message
            'session' => 0.0367   // EUR per conversation session
        ]
    ];
    
    public function calculateMonthlyCost(Company $company)
    {
        $templateMessages = $company->whatsapp_messages()
            ->whereType('template')
            ->whereMonth('created_at', now()->month)
            ->count();
            
        $sessions = $company->whatsapp_conversations()
            ->whereMonth('created_at', now()->month)
            ->count();
            
        return [
            'template_cost' => $templateMessages * $this->pricing['DE']['template'],
            'session_cost' => $sessions * $this->pricing['DE']['session'],
            'total' => $total
        ];
    }
}
```

## Testing

### Sandbox Testing
```bash
# WhatsApp test configuration
WHATSAPP_SANDBOX_NUMBER=+1-415-238-0000
WHATSAPP_TEST_TOKEN=test_token_xxxxx
```

### Test Commands
```bash
# Send test message
php artisan whatsapp:test-message +49xxxxxxxxx

# Test webhook
php artisan whatsapp:test-webhook message_received
```

## Related Documentation
- [SMS Integration](sms.md)
- [Notification System](../features/notifications.md)
- [Customer Portal](../features/customer-portal.md)