# 🔌 Retell.ai Integration

> 📞 **AI-Powered Phone System for AskProAI**  
> Version: 2.0 | Last Updated: January 10, 2025  
> Status: 🟢 Production Ready

---

## 📖 Overview & Architecture

### What is Retell.ai?

Retell.ai is our AI-powered phone system that enables:
- **24/7 Automated Call Handling**: Never miss a customer call
- **Natural Conversations**: AI agents speak fluent German and 30+ languages
- **Appointment Booking**: Direct calendar integration
- **Smart Call Routing**: VIP handling and intelligent transfers

### System Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Phone Call    │────▶│   Retell.ai      │────▶│   AskProAI      │
│   (Customer)    │     │   Cloud Service   │     │   API Gateway   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                │                          │
                                │ Webhooks                 │
                                ▼                          ▼
                        ┌──────────────────┐     ┌─────────────────┐
                        │  Custom Functions │     │   Database      │
                        │   (Appointment)   │     │   (MySQL)       │
                        └──────────────────┘     └─────────────────┘
```

### Key Components

| Component | Purpose | Location |
|-----------|---------|----------|
| RetellV2Service | Main API client | `app/Services/RetellV2Service.php` |
| Webhook Handler | Process events | `app/Services/Webhooks/RetellWebhookHandler.php` |
| Control Center | Admin UI | `/admin/retell-control-center` |
| Custom Functions | Business logic | `app/Services/Retell/CustomFunctions/` |

---

## 🚀 Quick Start Guide

### Prerequisites Checklist

- [ ] Retell.ai account activated
- [ ] API credentials obtained
- [ ] Phone number purchased in Retell
- [ ] Webhook URL publicly accessible
- [ ] SSL certificate valid

### Step 1: Environment Configuration

```bash
# Add to .env file
RETELL_API_KEY=key_your_api_key_here
RETELL_WEBHOOK_SECRET=your_webhook_secret_here
RETELL_BASE=https://api.retellai.com
DEFAULT_RETELL_AGENT_ID=agent_default_id
```

### Step 2: Verify Installation

```bash
# Test API connection
php artisan retell:test-connection

# Check webhook endpoint
curl -X POST https://your-domain.com/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

### Step 3: Create Your First Agent

```bash
# Via Artisan command
php artisan retell:create-agent \
  --name="Customer Service" \
  --language="de" \
  --voice="sarah"

# Or use the Control Center UI
# Navigate to: /admin/retell-control-center
```

### Step 4: Make a Test Call

1. Find your Retell phone number in the dashboard
2. Call the number
3. Monitor real-time logs:
   ```bash
   tail -f storage/logs/retell-webhooks.log
   ```

---

## ⚙️ Configuration Reference

### Environment Variables

| Variable | Description | Required | Default |
|----------|-------------|----------|---------|
| `RETELL_API_KEY` | Primary API key | ✅ Yes | - |
| `RETELL_WEBHOOK_SECRET` | Webhook signature | ✅ Yes | - |
| `RETELL_BASE` | API base URL | ❌ No | https://api.retellai.com |
| `DEFAULT_RETELL_AGENT_ID` | Fallback agent | ❌ No | - |
| `RETELL_DEBUG` | Enable debug logs | ❌ No | false |

### Webhook Configuration

**Webhook URL Format:**
```
https://your-domain.com/api/retell/webhook
```

**Required Events in Retell Dashboard:**
- ✅ `call_started` - Track active calls
- ✅ `call_ended` - Process completed calls
- ✅ `call_analyzed` - Get AI analysis

**Security Headers:**
```
x-retell-signature: {signature}
Content-Type: application/json
```

### Multi-Tenant Configuration

Each company can have independent Retell settings:

```php
// Company-specific configuration
$company = Company::find($id);
$company->retell_settings = [
    'api_key' => 'company_specific_key',
    'agent_id' => 'agent_123',
    'voice' => 'custom_voice_id',
    'language' => 'de'
];
$company->save();
```

---

## 🤖 Agent Management

### Agent Creation Workflow

1. **Define Agent Personality**
   ```yaml
   name: "Friendly Assistant"
   personality: "Professional yet warm"
   language: "de"
   voice: "sarah"
   ```

2. **Configure System Prompt**
   ```
   Du bist ein freundlicher Assistent für [Company Name].
   Deine Aufgabe ist es, Anrufer zu begrüßen und Termine zu vereinbaren.
   Sei höflich, professionell und hilfsbereit.
   ```

3. **Set Up Custom Functions**
   - `check_availability` - Check calendar slots
   - `book_appointment` - Create booking
   - `get_customer_info` - Retrieve customer data

### Voice Configuration Options

| Voice ID | Language | Gender | Style |
|----------|----------|--------|-------|
| sarah | German | Female | Professional |
| max | German | Male | Friendly |
| emma | English | Female | Neutral |
| custom_* | Any | Any | Cloned |

### Agent Versioning

```bash
# Export current agent configuration
php artisan retell:export-agent agent_123 > agent_backup.json

# Import agent configuration
php artisan retell:import-agent < agent_backup.json

# Track changes in git
git add agents/
git commit -m "Update: Customer service agent prompt"
```

---

## 🔧 Custom Functions

### Available Functions

#### 1. **check_availability**
Check available appointment slots

```json
{
  "name": "check_availability",
  "description": "Check available time slots",
  "parameters": {
    "date": "2024-01-15",
    "service_id": "haircut",
    "duration": 30
  }
}
```

#### 2. **book_appointment**
Create new appointment

```json
{
  "name": "book_appointment",
  "description": "Book appointment slot",
  "parameters": {
    "customer_phone": "+49123456789",
    "date": "2024-01-15",
    "time": "14:00",
    "service_id": "haircut"
  }
}
```

#### 3. **get_customer_info**
Retrieve customer details

```json
{
  "name": "get_customer_info",
  "description": "Get customer information",
  "parameters": {
    "phone": "+49123456789"
  }
}
```

### Creating Custom Functions

```php
// app/Services/Retell/CustomFunctions/MyCustomFunction.php
namespace App\Services\Retell\CustomFunctions;

class MyCustomFunction extends BaseCustomFunction
{
    public function execute(array $params): array
    {
        // Your business logic here
        return [
            'success' => true,
            'data' => $result
        ];
    }
}
```

### Function Registration

```php
// config/retell.php
'custom_functions' => [
    'my_function' => \App\Services\Retell\CustomFunctions\MyCustomFunction::class,
]
```

---

## 📡 Webhook Integration

### Webhook Event Flow

```
1. Customer calls → Retell answers
2. Retell sends webhook → AskProAI receives
3. Signature verified → Event processed
4. Job queued → Background processing
5. Database updated → Response sent
```

### Event Types & Handlers

| Event | Description | Job Handler |
|-------|-------------|-------------|
| `call_started` | Call initiated | `ProcessRetellCallStartedJob` |
| `call_ended` | Call completed | `ProcessRetellCallEndedJob` |
| `call_analyzed` | AI analysis ready | `AnalyzeCallSentimentJob` |

### Webhook Security

```php
// Signature verification automatically handled
// app/Http/Middleware/VerifyRetellSignature.php

$signature = $request->header('x-retell-signature');
$payload = $request->getContent();
$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $expected)) {
    abort(401, 'Invalid signature');
}
```

### Error Handling

```php
// Automatic retry configuration
'retry_after' => 60,  // seconds
'max_attempts' => 3,
'backoff_multiplier' => 2,
```

---

## 📊 Control Center Dashboard

### Accessing the Control Center

**URL**: `/admin/retell-control-center`

**Features**:
- Real-time call monitoring
- Agent configuration UI
- Analytics dashboard
- Test call interface
- Webhook log viewer

### Dashboard Sections

#### 1. **Overview**
- Active calls counter
- Today's statistics
- Agent performance
- System health

#### 2. **Agent Management**
- Create/edit agents
- Voice testing
- Prompt editor
- Function assignment

#### 3. **Call Analytics**
- Call volume trends
- Duration statistics
- Success rates
- Customer satisfaction

#### 4. **System Logs**
- Real-time webhook logs
- Error tracking
- API usage stats
- Debug information

---

## 🧪 Testing & Debugging

### Test Call Procedures

#### 1. **Basic Connection Test**
```bash
# Test API connection
php artisan retell:test

# Output:
# ✅ API Connection: OK
# ✅ Webhook URL: Accessible
# ✅ Agent Status: Active
```

#### 2. **Simulated Call Test**
```bash
# Simulate webhook events
php artisan retell:simulate-call \
  --type=appointment \
  --customer="+49123456789"
```

#### 3. **Load Testing**
```bash
# Generate test load
php artisan retell:load-test \
  --calls=100 \
  --duration=60
```

### Debug Tools

#### Enable Debug Mode
```bash
# .env
RETELL_DEBUG=true
LOG_LEVEL=debug
```

#### Log Locations
```
storage/logs/retell-webhooks.log    # Webhook events
storage/logs/retell-api.log         # API calls
storage/logs/retell-functions.log   # Custom functions
storage/logs/laravel.log           # General logs
```

#### Real-time Monitoring
```bash
# Watch all Retell logs
tail -f storage/logs/retell-*.log

# Filter for errors
tail -f storage/logs/retell-*.log | grep ERROR

# Monitor specific call
tail -f storage/logs/retell-*.log | grep "call_abc123"
```

---

## 🚨 Troubleshooting Guide

### Common Issues & Solutions

#### Issue: "No calls are being received"

**Diagnosis Steps:**
1. Check Horizon is running: `php artisan horizon:status`
2. Verify webhook URL in Retell dashboard
3. Test webhook endpoint manually
4. Check firewall/SSL settings

**Quick Fix:**
```bash
# Restart all services
php artisan horizon:terminate
php artisan horizon
php artisan queue:restart

# Force webhook re-registration
php artisan retell:register-webhooks
```

#### Issue: "Agent responds incorrectly"

**Diagnosis Steps:**
1. Review agent prompt in Control Center
2. Check custom function logs
3. Verify language settings
4. Test with simple prompts

**Quick Fix:**
```bash
# Reset agent to defaults
php artisan retell:reset-agent {agent_id}

# Reload custom functions
php artisan retell:reload-functions
```

#### Issue: "Appointments not being created"

**Diagnosis Steps:**
1. Check cal.com integration status
2. Verify customer data extraction
3. Review booking function logs
4. Test availability checker

**Quick Fix:**
```bash
# Test booking flow
php artisan retell:test-booking \
  --date="2024-01-15" \
  --time="14:00"

# Clear booking cache
php artisan cache:forget "booking_locks_*"
```

### Emergency Recovery Scripts

#### 1. **Complete System Reset**
```bash
#!/bin/bash
# emergency-retell-reset.sh

# Stop all processes
php artisan down
php artisan horizon:terminate

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart services
php artisan horizon
php artisan up

echo "✅ Retell system reset complete"
```

#### 2. **Manual Call Import**
```bash
#!/bin/bash
# import-missed-calls.sh

# Import calls from last 24 hours
php artisan retell:import-calls \
  --from="24 hours ago" \
  --process-immediately

echo "✅ Call import complete"
```

---

## 📈 Operations Guide

### Monitoring Setup

#### Key Metrics to Track

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| API Response Time | < 200ms | > 500ms |
| Webhook Success Rate | > 99% | < 95% |
| Call Completion Rate | > 90% | < 80% |
| Agent Response Time | < 2s | > 5s |

#### Monitoring Commands
```bash
# Check system health
php artisan retell:health

# View performance metrics
php artisan retell:metrics --period=1h

# Export analytics
php artisan retell:export-analytics --format=csv
```

### Performance Optimization

#### 1. **Database Indexes**
```sql
-- Add indexes for common queries
CREATE INDEX idx_calls_phone ON calls(customer_phone);
CREATE INDEX idx_calls_date ON calls(created_at);
CREATE INDEX idx_calls_status ON calls(status);
```

#### 2. **Queue Optimization**
```php
// config/horizon.php
'environments' => [
    'production' => [
        'retell-high' => [
            'connection' => 'redis',
            'queue' => ['retell-critical'],
            'processes' => 3,
            'tries' => 1,
            'timeout' => 30,
        ],
    ],
],
```

#### 3. **Caching Strategy**
```php
// Cache frequently accessed data
Cache::remember('agent_config_'.$agentId, 300, function () {
    return RetellAgent::find($agentId);
});
```

### Backup Procedures

#### Daily Backups
```bash
# Backup agent configurations
php artisan retell:backup-agents

# Backup call recordings (if enabled)
php artisan retell:backup-recordings --date=yesterday

# Backup conversation logs
mysqldump -u root -p askproai_db calls call_transcripts > retell_backup_$(date +%Y%m%d).sql
```

### Scaling Guidelines

#### Horizontal Scaling
- Add more Horizon workers for high call volume
- Use Redis clustering for queue distribution
- Implement read replicas for analytics

#### Vertical Scaling
- Increase PHP memory limit for long conversations
- Optimize MySQL for larger datasets
- Use CDN for static webhook responses

---

## 📚 Reference Documentation

### API Endpoints

#### Internal API Endpoints
```
POST   /api/retell/webhook          # Webhook receiver
GET    /api/retell/status           # System status
POST   /api/retell/test-call        # Initiate test call
GET    /api/retell/agents           # List agents
POST   /api/retell/agents           # Create agent
PUT    /api/retell/agents/{id}      # Update agent
DELETE /api/retell/agents/{id}      # Delete agent
```

#### Retell.ai API Reference
```
Base URL: https://api.retellai.com

GET    /v2/list-calls               # List calls
GET    /v2/get-call/{call_id}       # Get call details
POST   /v2/create-agent             # Create agent
PUT    /v2/update-agent/{agent_id}  # Update agent
POST   /v2/create-phone-number      # Register number
```

### Webhook Payload Examples

#### call_started Event
```json
{
  "event": "call_started",
  "call": {
    "call_id": "call_abc123",
    "agent_id": "agent_123",
    "customer_number": "+49123456789",
    "start_timestamp": 1704974400000,
    "direction": "inbound"
  }
}
```

#### call_ended Event
```json
{
  "event": "call_ended",
  "call": {
    "call_id": "call_abc123",
    "agent_id": "agent_123",
    "customer_number": "+49123456789",
    "start_timestamp": 1704974400000,
    "end_timestamp": 1704974700000,
    "duration_seconds": 300,
    "transcript": "...",
    "summary": "Customer booked appointment for haircut",
    "custom_data": {
      "appointment_date": "2024-01-15",
      "appointment_time": "14:00"
    }
  }
}
```

### Code Snippets

#### Process Webhook in Controller
```php
public function handleWebhook(Request $request)
{
    $event = $request->input('event');
    $call = $request->input('call');
    
    switch ($event) {
        case 'call_started':
            ProcessRetellCallStartedJob::dispatch($call);
            break;
        case 'call_ended':
            ProcessRetellCallEndedJob::dispatch($call);
            break;
    }
    
    return response()->json(['status' => 'accepted']);
}
```

#### Custom Function Example
```php
class CheckAvailabilityFunction extends BaseCustomFunction
{
    public function execute(array $params): array
    {
        $date = Carbon::parse($params['date']);
        $duration = $params['duration'] ?? 30;
        
        $slots = $this->calcomService->getAvailableSlots(
            $date,
            $duration
        );
        
        return [
            'available' => !empty($slots),
            'slots' => array_map(function ($slot) {
                return $slot->format('H:i');
            }, $slots)
        ];
    }
}
```

### Best Practices

#### 1. **Prompt Engineering**
- Keep prompts concise and clear
- Use native language for better understanding
- Include company-specific information
- Test with various accents and speaking styles

#### 2. **Error Handling**
- Always provide fallback responses
- Log all errors with context
- Implement circuit breakers for external services
- Use graceful degradation

#### 3. **Security**
- Rotate API keys regularly
- Use environment-specific credentials
- Implement rate limiting
- Monitor for unusual patterns

#### 4. **Performance**
- Cache agent configurations
- Use async processing for webhooks
- Implement database query optimization
- Monitor resource usage

---

## 🔗 Additional Resources

### Internal Resources
- **Control Center**: `/admin/retell-control-center`
- **API Documentation**: `/docs/api/retell`
- **System Logs**: `/admin/logs?filter=retell`

### External Resources
- [Retell.ai Documentation](https://docs.retellai.com)
- [Retell.ai Dashboard](https://app.retellai.com)
- [API Status Page](https://status.retellai.com)

### Support Contacts
- **Technical Issues**: tech-support@askproai.de
- **Retell.ai Support**: support@retellai.com
- **Emergency Hotline**: +49 XXX XXXX (24/7)

---

**Document Version**: 2.0  
**Last Updated**: January 10, 2025  
**Maintained By**: AskProAI Technical Team