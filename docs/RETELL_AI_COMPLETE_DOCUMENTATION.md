# Retell.ai Integration Documentation for AskProAI

> **Version**: 2.0  
> **Last Updated**: July 10, 2025  
> **Status**: Production-Ready with Critical Fixes Applied

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Integration Flow](#integration-flow)
4. [Webhook Handling](#webhook-handling)
5. [Agent Configuration](#agent-configuration)
6. [Custom Functions](#custom-functions)
7. [API Endpoints](#api-endpoints)
8. [Configuration](#configuration)
9. [Testing Procedures](#testing-procedures)
10. [Troubleshooting](#troubleshooting)
11. [Performance & Monitoring](#performance--monitoring)
12. [Security Considerations](#security-considerations)
13. [Recent Critical Fixes](#recent-critical-fixes)
14. [Best Practices](#best-practices)
15. [Developer Guide](#developer-guide)
16. [Operations Manual](#operations-manual)

---

## Overview

Retell.ai is the AI-powered phone system that powers AskProAI's automatic call handling and appointment booking capabilities. It provides:

- **24/7 AI Phone Agent**: Answers calls in natural German/English
- **Intelligent Conversation**: Context-aware dialogue with customers
- **Appointment Booking**: Direct integration with calendar systems
- **Multi-tenant Support**: Isolated data for each company
- **Custom Functions**: Business logic execution during calls
- **Real-time Analytics**: Call metrics and transcripts

### Key Features
- Natural language understanding in 30+ languages (focus on German)
- Custom voice cloning for brand consistency
- Real-time appointment availability checking
- Customer recognition and VIP handling
- Call transfer and callback scheduling
- GDPR-compliant data handling

---

## Architecture

### System Components

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
                                                          │
                                                          ▼
                                                 ┌─────────────────┐
                                                 │   Cal.com       │
                                                 │   Integration   │
                                                 └─────────────────┘
```

### Service Layer Architecture

1. **RetellV2Service** (`app/Services/RetellV2Service.php`)
   - Main API client for Retell.ai
   - Handles agent management, call operations
   - Circuit breaker pattern for resilience
   - Automatic retry with exponential backoff

2. **RetellWebhookHandler** (`app/Services/Webhooks/RetellWebhookHandler.php`)
   - Processes incoming webhooks
   - Signature verification
   - Event routing to appropriate jobs

3. **RetellDataExtractor** (`app/Helpers/RetellDataExtractor.php`)
   - Extracts and normalizes webhook data
   - Handles timestamp conversions
   - Language detection fallback

4. **Processing Jobs**
   - `ProcessRetellCallStartedJob`: Live call tracking
   - `ProcessRetellCallEndedJob`: Call completion & analysis
   - `AnalyzeCallSentimentJob`: Post-call analysis

---

## Integration Flow

### End-to-End Call Flow

1. **Call Initiation**
   ```
   Customer dials → Phone provider → Retell.ai answers with agent
   ```

2. **Webhook: call_started**
   ```
   Retell → POST /api/retell/webhook-simple
   → Creates Call record with status: in_progress
   → Appears in live dashboard
   ```

3. **During Call**
   - AI conducts conversation
   - Custom functions called for:
     - Customer recognition
     - Appointment availability
     - Data collection

4. **Webhook: call_ended**
   ```
   Retell → POST /api/retell/webhook-simple
   → Updates Call record with:
     - Transcript
     - Duration
     - Cost
     - Analysis data
   → Triggers appointment booking if needed
   ```

5. **Post-Processing**
   - Sentiment analysis
   - Email summaries (if enabled)
   - Metrics calculation
   - Cal.com sync (if appointment made)

---

## Webhook Handling

### Critical Configuration

**Webhook URL**: `https://api.askproai.de/api/retell/webhook-simple`

> ⚠️ **IMPORTANT**: Use the `-simple` endpoint to bypass signature verification issues

### Webhook Events

1. **call_started**
   - Fired when call begins
   - Creates initial call record
   - Sets up live monitoring

2. **call_ended**
   - Fired when call completes
   - Contains full call data
   - Triggers post-processing

3. **call_analyzed**
   - Optional enriched data
   - Additional AI analysis

### Data Structure (As of July 2025)

```json
{
  "event": "call_ended",
  "call": {
    "call_id": "abc123",
    "agent_id": "agent_123",
    "from_number": "+491234567890",
    "to_number": "+493012345678",
    "direction": "inbound",
    "call_type": "phone_call",
    "call_status": "ended",
    "start_timestamp": "2025-07-10T10:30:00.000Z",
    "end_timestamp": "2025-07-10T10:35:00.000Z",
    "duration_ms": 300000,
    "transcript": "Full conversation text...",
    "call_analysis": {
      "call_summary": "Customer called about...",
      "user_sentiment": "positive",
      "call_successful": true,
      "custom_analysis_data": {
        "appointment_made": true,
        "datum_termin": "2025-07-15",
        "uhrzeit_termin": "14:00"
      }
    },
    "recording_url": "https://...",
    "public_log_url": "https://...",
    "latency": { "e2e": { "p50": 150 } },
    "call_cost": {
      "total_cost": 245,
      "transcript_cost": 120,
      "synthesis_cost": 125
    }
  }
}
```

### Critical Implementation Details

```php
// RetellWebhookWorkingController.php - CRITICAL FIX
if (isset($data['call']) && is_array($data['call'])) {
    // Flatten nested structure for compatibility
    $callData = $data['call'];
    $data = array_merge($callData, [
        'event' => $data['event'] ?? $data['event_type'] ?? null,
        'event_type' => $data['event'] ?? $data['event_type'] ?? null
    ]);
}
```

---

## Agent Configuration

### Agent Structure

```json
{
  "agent_name": "AskProAI Assistant",
  "voice_id": "elevenlabs_voice_id",
  "language": "de",
  "llm_websocket_url": "wss://api.retellai.com/llm",
  "agent_prompt": "System prompt here...",
  "response_engine": {
    "type": "retell_llm",
    "llm_id": "llm_abc123"
  }
}
```

### Key Configuration Elements

1. **Voice Settings**
   - Provider: ElevenLabs
   - Language: German (de)
   - Speed: 1.0
   - Stability: 0.5
   - Style: Professional

2. **LLM Configuration**
   - Model: GPT-4 Turbo
   - Temperature: 0.7
   - Max tokens: Dynamic
   - Custom functions enabled

3. **Behavioral Settings**
   - Interruption sensitivity: Medium
   - End call on goodbye: Yes
   - Ambient sound: Office
   - Webhook events: All

### Agent Prompt Template

```
Du bist der intelligente Assistent von [COMPANY_NAME].

DEINE AUFGABEN:
1. Begrüße Anrufer professionell
2. Erkenne bestehende Kunden
3. Vereinbare Termine
4. Beantworte Fragen

WICHTIGE INFORMATIONEN:
- Öffnungszeiten: [HOURS]
- Services: [SERVICES]
- Adresse: [ADDRESS]

GESPRÄCHSFÜHRUNG:
- Sei freundlich und hilfsbereit
- Frage nach allen notwendigen Informationen
- Bestätige immer die Details
```

---

## Custom Functions

### Available Functions

1. **check_availability**
   - Check appointment slots
   - Input: date (string)
   - Output: available time slots

2. **collect_appointment_data**
   - Collect and validate appointment details
   - Input: datum, uhrzeit, name, service, etc.
   - Output: confirmation message

3. **check_customer**
   - Identify returning customers
   - Input: call_id
   - Output: customer info if exists

4. **current_time_berlin**
   - Get current time in Berlin
   - Input: none
   - Output: current date/time

5. **transfer_to_fabian**
   - Transfer urgent calls
   - Input: reason
   - Output: transfer status

### Function Implementation

```php
// RetellCustomFunctionsController.php
public function collectAppointment(Request $request)
{
    $data = $request->all();
    $callId = $data['call']['call_id'] ?? null;
    
    // Security check
    $call = Call::where('call_id', $callId)->first();
    if ($call && !$call->company->needsAppointmentBooking()) {
        return response()->json([
            'success' => false,
            'message' => 'Function not available'
        ]);
    }
    
    // Process appointment
    $appointmentData = [
        'date' => $this->parseRelativeDate($data['args']['datum']),
        'time' => $this->parseTime($data['args']['uhrzeit']),
        'customer_name' => $data['args']['name'],
        'service' => $data['args']['dienstleistung']
    ];
    
    $result = $this->appointmentMCP->create($appointmentData);
    
    return response()->json($result);
}
```

---

## API Endpoints

### Webhook Endpoints

| Endpoint | Method | Purpose | Middleware |
|----------|--------|---------|------------|
| `/api/retell/webhook-simple` | POST | Main webhook (no signature) | None |
| `/api/retell/webhook` | POST | Signature-verified webhook | verify.retell.signature |
| `/api/webhooks/retell` | POST | Unified webhook handler | Multiple security layers |

### Custom Function Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/retell/check-availability` | POST | Check appointment slots |
| `/api/retell/collect-appointment` | POST | Collect appointment data |
| `/api/retell/identify-customer` | POST | Customer recognition |
| `/api/retell/current-time-berlin` | GET | Get current time |

### Management Endpoints

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/mcp/retell/agents/{companyId}` | GET | List agents | auth:sanctum |
| `/api/mcp/retell/agent/{agentId}` | GET | Get agent details | auth:sanctum |
| `/api/mcp/retell/update-agent/{agentId}` | PATCH | Update agent | auth:sanctum |
| `/api/mcp/retell/test-call` | POST | Initiate test call | auth:sanctum |

### Monitoring Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/retell/monitor/stats` | GET | Call statistics |
| `/api/retell/monitor/activity` | GET | Recent activity |
| `/api/health/service/retell` | GET | Service health |

---

## Configuration

### Environment Variables

```bash
# Required
RETELL_TOKEN=key_abc123...              # API Key
RETELL_WEBHOOK_SECRET=key_abc123...     # Same as API Key
RETELL_BASE=https://api.retellai.com    # API Base URL

# Optional
DEFAULT_RETELL_API_KEY=key_abc123...    # Fallback API Key
DEFAULT_RETELL_AGENT_ID=agent_123...    # Default Agent ID
RETELL_VOICE_ID=elevenlabs_voice_123   # Voice ID
RETELL_VOICE_SPEED=1.0                 # Voice speed
RETELL_TEMPERATURE=0.7                 # LLM temperature
```

### Database Configuration

Key tables:
- `calls`: Call records with full webhook data
- `retell_webhooks`: Raw webhook storage
- `webhook_events`: Event processing log
- `phone_numbers`: Phone-to-company mapping

### Cron Jobs

```bash
# Import calls every 15 minutes
*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php

# Clean stale in-progress calls
*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php
```

---

## Testing Procedures

### 1. Webhook Testing

```bash
# Test webhook endpoint
curl -X POST https://api.askproai.de/api/retell/webhook-simple \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_started",
    "call": {
      "call_id": "test_123",
      "from_number": "+491234567890",
      "to_number": "+493012345678",
      "start_timestamp": "2025-07-10T10:00:00.000Z"
    }
  }'
```

### 2. Function Testing

```bash
# Test availability check
curl -X POST https://api.askproai.de/api/retell/check-availability \
  -H "Content-Type: application/json" \
  -d '{"date": "morgen"}'
```

### 3. Health Check

```bash
# Check Retell integration health
php retell-health-check.php

# Manual import test
php import-retell-calls-manual.php
```

### 4. Test Call Flow

1. Make test call to configured number
2. Monitor logs: `tail -f storage/logs/laravel.log | grep -i retell`
3. Check database: `SELECT * FROM calls ORDER BY created_at DESC LIMIT 1`
4. Verify dashboard updates

---

## Troubleshooting

### Common Issues

#### 1. No Calls Appearing

**Symptoms**: Calls made but not showing in dashboard

**Solutions**:
```bash
# Check Horizon is running
php artisan horizon:status

# If not, start it
php artisan horizon

# Run manual import
php manual-retell-import.php

# Check webhook logs
tail -f storage/logs/laravel.log | grep "Retell Webhook"
```

#### 2. Webhook 500 Errors

**Symptoms**: Webhooks failing with 500 status

**Causes & Solutions**:

1. **Structure mismatch**
   - Check if Retell changed data structure
   - Review RetellWebhookWorkingController fixes

2. **Timestamp format issues**
   - Verify parseTimestamp method handles both numeric and ISO formats

3. **TenantScope blocking**
   - Ensure webhook routes bypass tenant filtering

#### 3. Wrong Timezone

**Symptoms**: Call times off by hours

**Solution**:
- Retell sends UTC timestamps
- System converts to Berlin time (+2 hours)
- Check timezone in `.env`: `APP_TIMEZONE=Europe/Berlin`

#### 4. Agent Not Found

**Symptoms**: 404 errors when accessing agent

**Solutions**:
```bash
# List all agents
php artisan tinker
>>> $retell = new \App\Services\RetellV2Service();
>>> $retell->listAgents();

# Update agent ID in database
>>> $company = Company::first();
>>> $company->retell_agent_id = 'new_agent_id';
>>> $company->save();
```

### Debug Commands

```bash
# Test phone number resolution
php test-phone-resolution.php +493012345678

# Verify webhook status
php verify-webhook-status.php

# Check recent calls
mysql -u askproai_user -p askproai_db \
  -e "SELECT call_id, from_number, call_status, created_at 
      FROM calls ORDER BY created_at DESC LIMIT 10"

# Monitor webhook events
mysql -u askproai_user -p askproai_db \
  -e "SELECT * FROM webhook_events 
      WHERE provider = 'retell' 
      ORDER BY created_at DESC LIMIT 10"
```

---

## Performance & Monitoring

### Key Metrics

1. **Response Times**
   - Webhook processing: < 500ms
   - Custom functions: < 200ms
   - API calls: < 1s

2. **Success Rates**
   - Webhook delivery: > 99%
   - Call completion: > 95%
   - Appointment booking: > 90%

3. **Cost Tracking**
   - Average call cost: €0.50-1.50
   - Cost breakdown: Transcript + Synthesis
   - Monthly usage limits

### Monitoring Tools

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log | grep -E "Retell|Call"

# Horizon dashboard
php artisan horizon

# Database metrics
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_calls,
    AVG(duration_sec) as avg_duration,
    SUM(cost) as total_cost
FROM calls
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

### Performance Optimization

1. **Caching**
   - Agent configurations cached for 1 hour
   - Phone number mappings cached indefinitely
   - Clear with: `php artisan cache:clear`

2. **Queue Configuration**
   ```php
   // config/horizon.php
   'webhooks' => [
       'connection' => 'redis',
       'queue' => 'webhooks',
       'balance' => 'auto',
       'processes' => 3,
       'tries' => 3,
       'timeout' => 60,
   ]
   ```

3. **Database Indexes**
   - calls.call_id (unique)
   - calls.created_at
   - calls.company_id
   - phone_numbers.number

---

## Security Considerations

### Authentication & Authorization

1. **Webhook Security**
   - Signature verification available (currently bypassed)
   - IP whitelisting possible
   - Rate limiting: 60 requests/minute

2. **API Authentication**
   - Sanctum tokens for management APIs
   - Company-scoped access
   - Role-based permissions

3. **Data Protection**
   - GDPR compliance built-in
   - Sensitive data masking in logs
   - Encrypted storage for recordings
   - 30-day retention policy

### Security Headers

```php
// Webhook signature verification
$signature = $request->header('x-retell-signature');
$timestamp = explode(',', $signature)[0];
$hash = explode(',', $signature)[1];

$payload = $timestamp . $request->getContent();
$expectedHash = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($hash, $expectedHash)) {
    abort(401, 'Invalid signature');
}
```

### Access Control

```php
// Company-based filtering
public function needsAppointmentBooking(): bool
{
    return $this->settings['appointment_booking_enabled'] ?? true;
}

// Custom function security
if (!$call->company->needsAppointmentBooking()) {
    return response()->json([
        'success' => false,
        'message' => 'Function not available'
    ]);
}
```

---

## Recent Critical Fixes

### July 2025 - Webhook Structure Change

**Problem**: Retell changed webhook structure from flat to nested

**Fix Applied**:
```php
// Before: { "call_id": "123", "event_type": "call_ended" }
// After: { "event": "call_ended", "call": { "call_id": "123" } }

if (isset($data['call']) && is_array($data['call'])) {
    $callData = $data['call'];
    $data = array_merge($callData, [
        'event' => $data['event'] ?? $data['event_type'] ?? null
    ]);
}
```

### Timestamp Format Flexibility

**Problem**: Timestamps sometimes ISO 8601, sometimes numeric

**Fix Applied**:
```php
private static function parseTimestamp($timestamp): ?string
{
    if (is_numeric($timestamp)) {
        return date('Y-m-d H:i:s', $timestamp / 1000);
    }
    if (is_string($timestamp)) {
        try {
            $dt = new \DateTime($timestamp);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

### TenantScope Webhook Bypass

**Problem**: Webhooks blocked by tenant filtering

**Fix Applied**:
```php
// In TenantScope::apply()
if (request()->is('api/retell/*') || 
    request()->is('api/webhook*')) {
    return; // Skip tenant filtering
}
```

---

## Best Practices

### 1. Agent Configuration

- **Keep prompts concise**: < 1000 tokens
- **Use German examples**: Better local understanding
- **Test edge cases**: Holidays, after-hours, full calendar
- **Version control**: Track prompt changes

### 2. Webhook Handling

- **Idempotency**: Handle duplicate webhooks gracefully
- **Async processing**: Use queues for heavy operations
- **Error handling**: Log failures, retry with backoff
- **Monitoring**: Alert on high failure rates

### 3. Custom Functions

- **Fast responses**: < 200ms target
- **Graceful failures**: Always return valid JSON
- **Security first**: Validate all inputs
- **Logging**: Track all function calls

### 4. Cost Management

- **Monitor usage**: Daily cost tracking
- **Set limits**: Per-company quotas
- **Optimize prompts**: Shorter = cheaper
- **Cache responses**: Reduce API calls

---

## Developer Guide

### Setting Up Development Environment

```bash
# Clone repository
git clone https://github.com/askproai/api-gateway.git

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
# Add Retell credentials to .env

# Run migrations
php artisan migrate

# Start services
php artisan horizon
npm run dev
```

### Creating New Custom Functions

1. Add route in `routes/api.php`:
```php
Route::post('/retell/my-function', 
    [RetellCustomFunctionsController::class, 'myFunction'])
    ->middleware(['verify.retell.signature']);
```

2. Implement in controller:
```php
public function myFunction(Request $request)
{
    $this->logRetellRequest('my_function', $request);
    
    // Your logic here
    
    return response()->json([
        'success' => true,
        'data' => $result
    ]);
}
```

3. Configure in Retell.ai dashboard:
   - Add custom function
   - Set endpoint URL
   - Define parameters
   - Test thoroughly

### Testing Webhooks Locally

```bash
# Use ngrok for local testing
ngrok http 8000

# Update Retell webhook URL to ngrok URL
# Make test calls
# Monitor local logs
```

---

## Operations Manual

### Daily Operations

1. **Morning Checks**
   ```bash
   # Check overnight calls
   php artisan retell:daily-report
   
   # Verify Horizon running
   php artisan horizon:status
   
   # Check failed jobs
   php artisan queue:failed
   ```

2. **Monitoring**
   - Dashboard: `/admin/retell-dashboard`
   - Live calls widget
   - Cost tracking
   - Error rates

3. **Common Tasks**
   - Update agent prompt
   - Adjust voice settings
   - Review transcripts
   - Handle escalations

### Weekly Maintenance

1. **Performance Review**
   ```sql
   -- Weekly call metrics
   SELECT 
       WEEK(created_at) as week,
       COUNT(*) as calls,
       AVG(duration_sec) as avg_duration,
       SUM(cost) as total_cost,
       COUNT(CASE WHEN call_status = 'failed' THEN 1 END) as failed
   FROM calls
   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
   GROUP BY WEEK(created_at);
   ```

2. **Cost Analysis**
   - Review per-company usage
   - Identify anomalies
   - Optimize high-cost agents

3. **System Updates**
   ```bash
   # Update dependencies
   composer update
   
   # Clear old logs
   php artisan log:clear --keep=30
   
   # Optimize performance
   php artisan optimize
   ```

### Emergency Procedures

1. **High Error Rate**
   ```bash
   # Check recent errors
   tail -n 1000 storage/logs/laravel.log | grep ERROR
   
   # Restart services
   php artisan down
   supervisorctl restart horizon
   php artisan up
   ```

2. **Webhook Failures**
   ```bash
   # Switch to simple endpoint
   # Update in Retell.ai: /api/retell/webhook-simple
   
   # Clear webhook cache
   php artisan cache:clear
   
   # Test manually
   php trigger-simple-webhook.php
   ```

3. **No Calls Processing**
   ```bash
   # Force import
   php manual-retell-import.php
   
   # Check phone mappings
   php test-phone-resolution.php
   
   # Verify API key
   php test-retell-api.php
   ```

---

## Appendix

### Useful SQL Queries

```sql
-- Recent calls with details
SELECT 
    c.call_id,
    c.from_number,
    c.to_number,
    c.duration_sec,
    c.cost,
    c.call_status,
    co.name as company,
    c.created_at
FROM calls c
LEFT JOIN companies co ON c.company_id = co.id
ORDER BY c.created_at DESC
LIMIT 20;

-- Failed webhook events
SELECT * FROM webhook_events
WHERE provider = 'retell' 
AND status = 'failed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Cost by company
SELECT 
    co.name,
    COUNT(c.id) as total_calls,
    SUM(c.cost) as total_cost,
    AVG(c.cost) as avg_cost
FROM calls c
JOIN companies co ON c.company_id = co.id
WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY co.id
ORDER BY total_cost DESC;
```

### Monitoring URLs

- Horizon Dashboard: `https://api.askproai.de/horizon`
- Admin Panel: `https://api.askproai.de/admin`
- Retell Dashboard: `https://app.retellai.com`
- System Health: `https://api.askproai.de/api/health/comprehensive`

### Support Contacts

- Technical Issues: Create GitHub issue
- Urgent Problems: Check #askproai-alerts Slack
- Retell.ai Support: support@retellai.com

---

**Document Version**: 2.0  
**Last Updated**: July 10, 2025  
**Next Review**: August 2025

This documentation is maintained in the AskProAI repository. For updates or corrections, please submit a pull request.