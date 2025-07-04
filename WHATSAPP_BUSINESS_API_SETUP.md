# 📱 WhatsApp Business API Setup Guide für AskProAI

## 🚀 Überblick

Wir verwenden die **WhatsApp Business API** (nicht die persönliche WhatsApp Web API) für production-ready, skalierbare und compliant Messaging.

## ✅ Warum NICHT der GitHub WhatsApp MCP Server?

Der analysierte [whatsapp-mcp](https://github.com/lharries/whatsapp-mcp) Server:
- ❌ Nutzt persönliche WhatsApp Accounts (ToS Violation)
- ❌ Manuelle QR-Code Authentication
- ❌ Keine Multi-Tenancy
- ❌ Keine Rate Limits
- ❌ Nicht production-ready

## 🎯 Unsere Production-Ready Lösung

### Features
- ✅ WhatsApp Business API (offiziell)
- ✅ Multi-tenant Support
- ✅ Rate Limiting & Compliance
- ✅ Template Management
- ✅ Delivery Tracking
- ✅ Cost Tracking
- ✅ Alternative: Twilio Support

## 📋 Setup Schritte

### 1. **Meta Business Account erstellen**
1. Gehe zu [business.facebook.com](https://business.facebook.com)
2. Erstelle Business Account
3. Verifiziere dein Business

### 2. **WhatsApp Business App registrieren**
1. Gehe zu [developers.facebook.com](https://developers.facebook.com)
2. Erstelle neue App → Type: "Business"
3. Add Product → WhatsApp
4. Setup WhatsApp Business API

### 3. **Phone Number registrieren**
```bash
# Über unseren MCP Server
php artisan mcp:execute whatsapp register_phone_number \
  --phone_number="+4930123456789" \
  --display_name="AskProAI Support"
```

### 4. **Environment Variables setzen**
```env
# Meta/Facebook WhatsApp Business API
WHATSAPP_BUSINESS_ID=123456789012345
WHATSAPP_ACCESS_TOKEN=EAABsbCS...
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_secure_random_token
WHATSAPP_API_VERSION=v18.0

# Templates (müssen in Meta Business Manager approved sein)
WHATSAPP_TEMPLATE_REMINDER_24H=appointment_reminder_24h_de
WHATSAPP_TEMPLATE_REMINDER_2H=appointment_reminder_2h_de
WHATSAPP_TEMPLATE_REMINDER_30MIN=appointment_reminder_30min_de
```

### 5. **Message Templates erstellen**

Im Meta Business Manager:

#### Template: appointment_reminder_24h_de
```
Hallo {{1}},

dies ist eine Erinnerung an Ihren Termin:

📅 Datum: {{2}}
🕐 Uhrzeit: {{3}}
📍 Ort: {{4}}
👤 Bei: {{5}}
🔧 Service: {{6}}

Falls Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage.

Ihr {{4}} Team
```

#### Template: appointment_confirmation_de
```
Hallo {{1}},

Ihr Termin wurde erfolgreich gebucht:

✅ Service: {{2}}
📅 Datum: {{3}}
🕐 Uhrzeit: {{4}}
📍 Ort: {{5}}

Sie erhalten 24 Stunden vorher eine Erinnerung.

Vielen Dank für Ihr Vertrauen!
```

### 6. **Webhook Configuration**

Add to `routes/api.php`:
```php
// WhatsApp Webhook
Route::match(['get', 'post'], '/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.whatsapp');
```

Create Controller:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessWhatsAppWebhook;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // GET request = verification
        if ($request->isMethod('get')) {
            $token = config('services.whatsapp.webhook_verify_token');
            $challenge = $request->input('hub.challenge');
            $verifyToken = $request->input('hub.verify_token');
            
            if ($verifyToken === $token) {
                return response($challenge, 200);
            }
            
            return response('Invalid token', 403);
        }
        
        // POST request = webhook event
        ProcessWhatsAppWebhook::dispatch($request->all());
        
        return response('OK', 200);
    }
}
```

## 🔧 Integration in NotificationService

Update `app/Services/NotificationService.php`:

```php
protected function sendWhatsApp(Appointment $appointment, string $type): bool
{
    if (!$appointment->customer->whatsapp_opt_in) {
        return false;
    }
    
    try {
        $response = app(MCPGateway::class)->send('whatsapp', 'send_appointment_reminder', [
            'appointment_id' => $appointment->id,
            'reminder_type' => $type
        ]);
        
        return $response['success'] ?? false;
    } catch (\Exception $e) {
        Log::error('WhatsApp notification failed', [
            'appointment_id' => $appointment->id,
            'error' => $e->getMessage()
        ]);
        
        return false;
    }
}
```

## 🧪 Testing

### 1. Test Message senden
```bash
php artisan tinker
>>> $mcp = app(App\Services\MCP\MCPGateway::class);
>>> $mcp->send('whatsapp', 'send_message', [
...     'to' => '+491701234567',
...     'message' => 'Test message from AskProAI'
... ]);
```

### 2. Test Template senden
```bash
php artisan mcp:execute whatsapp send_template \
  --to="+491701234567" \
  --template_name="appointment_reminder_24h_de" \
  --language_code="de_DE" \
  --parameters='["Max Mustermann","Haarschnitt","28.06.2025","14:00","Salon Berlin","Maria"]'
```

### 3. Delivery Status prüfen
```bash
php artisan tinker
>>> DB::table('whatsapp_message_logs')->latest()->first();
```

## 💰 Kosten

### WhatsApp Business API Pricing (Stand 2025)
- **Business-initiated**: €0.07 per message (Germany)
- **User-initiated** (24h window): €0.03 per message
- **Template messages**: €0.07 per message

### Kosten-Tracking
```sql
-- Monatliche Kosten
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as messages,
    SUM(cost) as total_cost
FROM whatsapp_message_logs
WHERE status = 'delivered'
GROUP BY month;
```

## 🔄 Alternative: Twilio

Falls WhatsApp Business API zu komplex:

```env
WHATSAPP_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxx
TWILIO_AUTH_TOKEN=xxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

## ⚠️ Wichtige Hinweise

1. **Opt-In erforderlich**: Kunden müssen explizit zustimmen
2. **Template Approval**: Alle Templates müssen von Meta approved werden
3. **24h Window**: Nach Kundeninteraktion 24h für freie Nachrichten
4. **Rate Limits**: Beachte API Rate Limits (80 msgs/sec)
5. **GDPR**: WhatsApp-Kommunikation muss GDPR-compliant sein

## 📊 Monitoring

```sql
-- Success Rate
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM whatsapp_message_logs
WHERE created_at > NOW() - INTERVAL 7 DAY
GROUP BY status;

-- Failed Messages
SELECT * FROM whatsapp_message_logs
WHERE status = 'failed'
AND created_at > NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;
```

## 🚀 Go-Live Checklist

- [ ] Meta Business Account verifiziert
- [ ] WhatsApp Business API access
- [ ] Phone Number registriert und verifiziert
- [ ] Message Templates approved
- [ ] Webhook URL configured
- [ ] Environment variables gesetzt
- [ ] Test messages erfolgreich
- [ ] Customer opt-in UI implementiert
- [ ] Cost tracking aktiviert
- [ ] Monitoring dashboard erstellt

---

**Status**: Implementation Ready
**Nächster Schritt**: Meta Business Account erstellen
**Geschätzte Zeit**: 2-3 Tage (hauptsächlich Approval-Wartezeit)