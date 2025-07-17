<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $notionMCP = new \App\Services\MCP\NotionMCPServer();
    
    // Parent page ID for the main Retell.ai Integration page we just created
    $parentId = '22caba11-76e2-8114-a6b8-f13896c8fd38';
    
    echo "📝 Creating Retell.ai sub-pages...\n\n";
    
    // 1. Architecture & Data Flow
    $architectureContent = <<<MARKDOWN
# Architecture & Data Flow

## System Overview

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Customer  │────▶│  Retell.ai   │────▶│  AskProAI   │
│   (Phone)   │     │  AI Agent    │     │  Backend    │
└─────────────┘     └──────────────┘     └─────────────┘
                           │                      │
                           ▼                      ▼
                    ┌──────────────┐     ┌─────────────┐
                    │   Webhook    │     │  Database   │
                    │   Events     │     │   (MySQL)   │
                    └──────────────┘     └─────────────┘
```

## Data Flow Details

### 1. Incoming Call
- Customer calls Retell.ai phone number
- Phone number mapped to company/branch via PhoneNumberResolver
- AI agent answers with company-specific greeting

### 2. Conversation & Data Collection
- Agent conducts conversation in German
- Extracts appointment details using custom functions
- Validates availability in real-time

### 3. Webhook Processing
```
POST /api/retell/webhook-simple
{
  "event": "call_ended",
  "call": {
    "call_id": "...",
    "from_number": "...",
    "to_number": "...",
    "start_timestamp": "2025-07-02T20:51:03.000Z"
  }
}
```

### 4. Job Queue Processing
- ProcessRetellCallStartedJob: Creates live call record
- ProcessRetellCallEndedJob: Updates call status, creates appointment

### 5. Data Storage
- Calls table: All call records with transcripts
- Appointments table: Booked appointments
- Webhook_events table: Raw webhook data for debugging

## Key Components

### RetellWebhookWorkingController
- Handles incoming webhooks
- Flattens nested data structure
- Dispatches jobs to queue

### RetellDataExtractor
- Extracts and normalizes data
- Handles timestamp conversion (UTC → Berlin)
- Flexible format parsing

### PhoneNumberResolver
- Maps phone numbers to companies
- Resolves branch assignments
- Handles multi-tenant isolation

### TenantScope
- Global scope for data isolation
- Bypassed for webhook routes
- Ensures data security
MARKDOWN;

    // 2. Webhook Configuration
    $webhookContent = <<<MARKDOWN
# Webhook Configuration Guide

## Retell.ai Dashboard Setup

### 1. Access Webhook Settings
1. Log in to [Retell Dashboard](https://dashboard.retellai.com)
2. Navigate to Settings → Webhooks
3. Click "Add Webhook"

### 2. Configure Webhook URL
```
URL: https://api.askproai.de/api/retell/webhook-simple
Method: POST
Content-Type: application/json
```

### 3. Enable Events
Required events:
- ✅ call_started
- ✅ call_ended
- ✅ call_analyzed
- ✅ call_failed

### 4. Authentication
⚠️ IMPORTANT: Retell uses API key as webhook secret
- No separate webhook secret needed
- Signature uses same API key

## Webhook Data Structure

### Call Started Event
```json
{
  "event": "call_started",
  "call": {
    "call_id": "call_abc123",
    "from_number": "+491234567890",
    "to_number": "+493083793369",
    "direction": "inbound",
    "call_type": "web_call",
    "agent_id": "agent_xyz",
    "start_timestamp": "2025-07-02T10:30:00.000Z"
  }
}
```

### Call Ended Event
```json
{
  "event": "call_ended",
  "call": {
    "call_id": "call_abc123",
    "status": "ended",
    "end_timestamp": "2025-07-02T10:35:00.000Z",
    "duration": 300,
    "recording_url": "https://...",
    "transcript": "...",
    "summary": "...",
    "custom_analysis": {...}
  }
}
```

## Signature Verification

### Format
```
x-retell-signature: v=timestamp,d=signature
```

### Verification Process
1. Extract timestamp and signature
2. Reconstruct payload: timestamp.request_body
3. Calculate HMAC-SHA256 with API key
4. Compare signatures

### PHP Implementation
```php
protected function verifySignature(\$request)
{
    \$signature = \$request->header('x-retell-signature');
    \$parts = explode(',', \$signature);
    
    \$timestamp = str_replace('v=', '', \$parts[0]);
    \$providedSignature = str_replace('d=', '', \$parts[1]);
    
    \$payload = \$timestamp . '.' . \$request->getContent();
    \$calculatedSignature = hash_hmac('sha256', \$payload, \$this->apiKey);
    
    return hash_equals(\$calculatedSignature, \$providedSignature);
}
```

## Testing Webhooks

### Manual Test
```bash
curl -X POST https://api.askproai.de/api/retell/webhook-simple \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_started",
    "call": {
      "call_id": "test_123",
      "from_number": "+491234567890",
      "to_number": "+493083793369"
    }
  }'
```

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i retell
```

### Verify Processing
```sql
SELECT * FROM webhook_events 
WHERE service = 'retell' 
ORDER BY created_at DESC 
LIMIT 10;
```
MARKDOWN;

    // 3. Troubleshooting Deep Dive
    $troubleshootingContent = <<<MARKDOWN
# Advanced Troubleshooting Guide

## Diagnostic Workflow

### Step 1: Verify Webhook Receipt
```bash
# Check nginx access logs
tail -f /var/log/nginx/access.log | grep retell

# Should see:
# POST /api/retell/webhook-simple 200
```

### Step 2: Check Laravel Processing
```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep -E "(Retell|webhook)"

# Check for errors
grep -i error storage/logs/laravel.log | tail -20
```

### Step 3: Queue Status
```bash
# Horizon dashboard
php artisan horizon:status

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}
```

## Common Error Patterns

### 1. "No company context found"
```php
// Issue: TenantScope blocking webhook
// Fix in TenantScope.php:
if (request()->is('api/retell/*')) {
    return; // Skip tenant filtering
}
```

### 2. "A non-numeric value encountered"
```php
// Issue: Timestamp format change
// Fix in RetellDataExtractor.php:
if (is_numeric($timestamp)) {
    return date('Y-m-d H:i:s', $timestamp / 1000);
} elseif (is_string($timestamp)) {
    return (new DateTime($timestamp))->format('Y-m-d H:i:s');
}
```

### 3. "Call to undefined method"
```php
// Issue: API version mismatch
// Check RetellV2Service.php endpoints
// Remove /v2/ prefix if needed
```

## Database Queries

### Find Stuck Calls
```sql
-- Calls in progress > 15 minutes
SELECT * FROM calls 
WHERE call_status = 'in_progress' 
AND created_at < NOW() - INTERVAL 15 MINUTE;

-- Cleanup
UPDATE calls 
SET call_status = 'abandoned' 
WHERE call_status = 'in_progress' 
AND created_at < NOW() - INTERVAL 15 MINUTE;
```

### Webhook Analysis
```sql
-- Success rate by day
SELECT 
    DATE(created_at) as date,
    status,
    COUNT(*) as count
FROM webhook_events
WHERE service = 'retell'
GROUP BY DATE(created_at), status
ORDER BY date DESC;

-- Failed webhook details
SELECT 
    id,
    event_type,
    created_at,
    JSON_EXTRACT(error_details, '$.message') as error
FROM webhook_events
WHERE service = 'retell' 
AND status = 'failed'
ORDER BY created_at DESC
LIMIT 10;
```

### Phone Number Mapping
```sql
-- Check phone number configuration
SELECT 
    pn.*,
    c.name as company_name,
    b.name as branch_name
FROM phone_numbers pn
LEFT JOIN companies c ON pn.company_id = c.id
LEFT JOIN branches b ON pn.branch_id = b.id
WHERE pn.number LIKE '%YOUR_NUMBER%';
```

## Performance Monitoring

### Response Times
```sql
-- Average webhook processing time
SELECT 
    AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_seconds,
    MAX(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as max_seconds,
    COUNT(*) as total
FROM webhook_events
WHERE service = 'retell'
AND processed_at IS NOT NULL
AND created_at >= NOW() - INTERVAL 24 HOUR;
```

### Call Volume
```sql
-- Calls per hour
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
    COUNT(*) as calls
FROM calls
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY hour
ORDER BY hour DESC;
```

## Emergency Procedures

### 1. Webhook Overload
```bash
# Temporarily disable webhook processing
php artisan down --allow=127.0.0.1

# Process backlog manually
php artisan queue:work --queue=webhooks --stop-when-empty

# Re-enable
php artisan up
```

### 2. Database Lock
```sql
-- Find blocking queries
SHOW PROCESSLIST;

-- Kill stuck query
KILL {process_id};
```

### 3. Memory Issues
```bash
# Check memory usage
free -h

# Restart services
supervisorctl restart horizon
systemctl restart php8.3-fpm
```

## Monitoring Scripts

### Real-time Dashboard
```bash
#!/bin/bash
# save as: retell-monitor.sh
watch -n 5 '
echo "=== RETELL MONITOR ==="
echo "Active Calls:"
mysql -u askproai_user -p"password" askproai_db -e "
SELECT COUNT(*) as active FROM calls 
WHERE call_status = \"in_progress\";"

echo -e "\nLast 5 Calls:"
mysql -u askproai_user -p"password" askproai_db -e "
SELECT id, from_number, call_status, created_at 
FROM calls ORDER BY created_at DESC LIMIT 5;"

echo -e "\nQueue Status:"
php artisan horizon:status | grep -A5 "Queues"
'
```
MARKDOWN;

    // 4. API Reference
    $apiContent = <<<MARKDOWN
# API Reference

## Internal API Endpoints

### MCP Server Endpoints

#### Get Call Statistics
```
GET /api/mcp/retell/stats/{company_id}
```

Response:
```json
{
  "success": true,
  "data": {
    "total_calls": 150,
    "calls_today": 12,
    "average_duration": 180,
    "success_rate": 0.85
  }
}
```

#### Import Recent Calls
```
POST /api/mcp/retell/import-calls
{
  "company_id": 1,
  "days": 7
}
```

#### Get Agent Details
```
GET /api/mcp/retell/agent/{company_id}
```

## Retell.ai API Integration

### List Calls
```php
\$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . \$apiKey
])->get('https://api.retellai.com/list-calls', [
    'limit' => 100,
    'sort_order' => 'descending'
]);
```

### Get Call Details
```php
\$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . \$apiKey
])->get("https://api.retellai.com/get-call/{\$callId}");
```

### Update Agent
```php
\$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . \$apiKey
])->patch("https://api.retellai.com/update-agent/{\$agentId}", [
    'general_prompt' => \$prompt,
    'voice_id' => \$voiceId
]);
```

## Custom Functions

### collect_appointment_data
Purpose: Collect appointment information during call

Parameters:
```json
{
  "datum": "string (heute, morgen, DD.MM.YYYY)",
  "uhrzeit": "string (HH:MM)",
  "name": "string",
  "telefonnummer": "string",
  "dienstleistung": "string",
  "email": "string (optional)",
  "notizen": "string (optional)"
}
```

Usage in Agent Prompt:
```
Wenn der Kunde einen Termin buchen möchte, sammle alle notwendigen 
Informationen und verwende die Funktion 'collect_appointment_data' 
mit den gesammelten Daten.
```

### check_availability
Purpose: Check available time slots

Parameters:
```json
{
  "branch_id": "integer",
  "service_id": "integer",
  "date": "string (YYYY-MM-DD)",
  "duration": "integer (minutes)"
}
```

Response:
```json
{
  "available": true,
  "slots": [
    "09:00", "09:30", "10:00", "14:00", "14:30"
  ]
}
```

## Data Models

### Call Model
```php
class Call extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'retell_call_id',
        'from_number',
        'to_number',
        'direction',
        'call_type',
        'call_status',
        'start_timestamp',
        'end_timestamp',
        'duration_ms',
        'recording_url',
        'transcript',
        'summary',
        'appointment_id'
    ];
}
```

### Webhook Event Model
```php
class WebhookEvent extends Model
{
    protected $fillable = [
        'service',
        'event_type',
        'payload',
        'status',
        'processed_at',
        'error_details'
    ];
    
    protected $casts = [
        'payload' => 'json',
        'error_details' => 'json'
    ];
}
```

## Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| 401 | Invalid API key | Check RETELL_TOKEN in .env |
| 403 | Signature verification failed | Ensure webhook secret = API key |
| 404 | Resource not found | Check endpoint URL and parameters |
| 429 | Rate limit exceeded | Implement exponential backoff |
| 500 | Internal server error | Check logs, contact support |
MARKDOWN;

    // Create sub-pages
    $subPages = [
        ['title' => '🏗️ Architecture & Data Flow', 'content' => $architectureContent],
        ['title' => '🔧 Webhook Configuration', 'content' => $webhookContent],
        ['title' => '🐛 Advanced Troubleshooting', 'content' => $troubleshootingContent],
        ['title' => '📚 API Reference', 'content' => $apiContent]
    ];
    
    foreach ($subPages as $page) {
        echo "📝 Creating: {$page['title']}...\n";
        
        $result = $notionMCP->executeTool('create_page', [
            'parent_id' => $parentId,
            'title' => $page['title'],
            'content' => $page['content']
        ]);
        
        if ($result['success']) {
            echo "✅ Created successfully!\n";
            echo "   URL: " . $result['data']['url'] . "\n\n";
        } else {
            echo "❌ Failed: " . $result['error'] . "\n\n";
        }
    }
    
    echo "\n🎉 All sub-pages created!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}