# AskProAI - Umfassender Testplan für MCP-basierte Telefonie & Terminbuchung

## Übersicht

Dieser Testplan deckt alle kritischen Aspekte des End-to-End Prozesses ab und nutzt maximale MCP-Funktionen für einen fehlerfreien Betrieb.

## Phase 1: Setup & Konfiguration

### 1.1 Datenbank-Setup Verification
```bash
# Prüfe Company Setup
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT 
    c.id,
    c.name,
    c.retell_api_key IS NOT NULL as has_retell,
    c.calcom_api_key IS NOT NULL as has_calcom,
    COUNT(b.id) as branch_count,
    COUNT(cet.id) as event_type_count
FROM companies c
LEFT JOIN branches b ON b.company_id = c.id
LEFT JOIN calcom_event_types cet ON cet.company_id = c.id
WHERE c.id = 1
GROUP BY c.id;
"
```

### 1.2 Cal.com Event Type Import
```bash
# Option 1: Via Admin UI
# Navigiere zu: /admin/calcom-event-types
# Klicke auf "Import Event Types"

# Option 2: Via Command Line
php artisan calcom:sync-event-types --company=1
```

### 1.3 Branch Configuration Update
```sql
-- Setze Cal.com Event Type (ersetze mit echter ID)
UPDATE branches 
SET calcom_event_type_id = 2026361
WHERE id = '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793';

-- Setze Retell Agent ID (ersetze mit echter ID)
UPDATE branches 
SET retell_agent_id = 'agent_xxxxxxxxxxxxx'
WHERE id = '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793';
```

## Phase 2: Component Testing

### 2.1 Test Phone Resolution (Isolated)
```php
<?php
// test-phone-resolution.php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

$webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);

$testNumbers = [
    '+493083793369',    // Configured number
    '+491234567890',    // Unknown number
    '03083793369',      // Without country code
];

foreach ($testNumbers as $number) {
    echo "\nTesting: $number\n";
    $result = $webhookMCP->resolvePhoneNumber($number);
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
```

### 2.2 Test Cal.com Connection
```php
<?php
// test-calcom-connection.php
$calcomMCP = app(\App\Services\MCP\CalcomMCPServer::class);

// Test connection
$result = $calcomMCP->testConnection(['company_id' => 1]);
echo "Connection Test: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Get event types
$result = $calcomMCP->getEventTypes(['company_id' => 1]);
echo "Event Types: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Check specific date availability
$result = $calcomMCP->checkAvailability([
    'company_id' => 1,
    'event_type_id' => 2026361,
    'date_from' => '2025-06-25',
    'date_to' => '2025-06-25',
    'timezone' => 'Europe/Berlin'
]);
echo "Availability: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
```

### 2.3 Test Retell Connection
```php
<?php
// test-retell-connection.php
$retellMCP = app(\App\Services\MCP\RetellMCPServer::class);

// Test connection
$result = $retellMCP->testConnection(['company_id' => 1]);
echo "Retell Connection: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Get call stats
$result = $retellMCP->getCallStats([
    'company_id' => 1,
    'days' => 7
]);
echo "Call Stats: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
```

## Phase 3: Integration Testing

### 3.1 Complete Webhook Flow Test
```bash
# Create test webhook script
cat > test-complete-webhook-flow.php << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

$webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);

// Test scenarios
$scenarios = [
    // Scenario 1: Successful booking
    [
        'name' => 'Successful Booking',
        'payload' => [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_success_' . uniqid(),
                'from_number' => '+491234567890',
                'to_number' => '+493083793369',
                'call_type' => 'inbound',
                'call_status' => 'ended',
                'duration_ms' => 180000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'name' => 'Max Mustermann',
                    'datum' => '2025-06-25',
                    'uhrzeit' => '14:00',
                    'dienstleistung' => 'Beratung'
                ],
                'call_analysis' => [
                    'call_summary' => 'Kunde möchte Beratungstermin',
                    'sentiment' => 'positive'
                ]
            ]
        ]
    ],
    
    // Scenario 2: No booking requested
    [
        'name' => 'Information Only Call',
        'payload' => [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_info_' . uniqid(),
                'from_number' => '+499876543210',
                'to_number' => '+493083793369',
                'call_type' => 'inbound',
                'call_status' => 'ended',
                'duration_ms' => 60000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => false,
                    'name' => 'Info Kunde'
                ],
                'call_analysis' => [
                    'call_summary' => 'Kunde wollte nur Informationen',
                    'sentiment' => 'neutral'
                ]
            ]
        ]
    ],
    
    // Scenario 3: Incomplete booking data
    [
        'name' => 'Incomplete Booking Data',
        'payload' => [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_incomplete_' . uniqid(),
                'from_number' => '+491111111111',
                'to_number' => '+493083793369',
                'call_type' => 'inbound',
                'call_status' => 'ended',
                'duration_ms' => 120000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'name' => 'Unvollständig Kunde',
                    'datum' => '2025-06-25'
                    // Missing uhrzeit
                ],
                'call_analysis' => [
                    'call_summary' => 'Kunde unsicher über Uhrzeit',
                    'sentiment' => 'neutral'
                ]
            ]
        ]
    ]
];

foreach ($scenarios as $scenario) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Testing: {$scenario['name']}\n";
    echo str_repeat('=', 60) . "\n";
    
    try {
        $result = $webhookMCP->processRetellWebhook($scenario['payload']);
        
        echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        echo "Call ID: " . ($result['call_id'] ?? 'N/A') . "\n";
        echo "Customer ID: " . ($result['customer_id'] ?? 'N/A') . "\n";
        echo "Appointment Created: " . ($result['appointment_created'] ? 'YES' : 'NO') . "\n";
        
        if ($result['appointment_created']) {
            echo "Appointment Data: " . json_encode($result['appointment_data']) . "\n";
        }
        
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}
EOF

php test-complete-webhook-flow.php
```

### 3.2 Load Testing
```bash
# Create load test script
cat > test-mcp-load.php << 'EOF'
<?php
// Simulate multiple concurrent webhook calls
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

$webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);

$concurrentCalls = 10;
$startTime = microtime(true);

for ($i = 0; $i < $concurrentCalls; $i++) {
    $payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'load_test_' . uniqid(),
            'from_number' => '+49' . rand(1000000000, 9999999999),
            'to_number' => '+493083793369',
            'call_type' => 'inbound',
            'call_status' => 'ended',
            'duration_ms' => rand(30000, 300000),
            'retell_llm_dynamic_variables' => [
                'booking_confirmed' => false,
                'name' => 'Load Test User ' . $i
            ]
        ]
    ];
    
    $result = $webhookMCP->processRetellWebhook($payload);
    echo "Call $i: " . ($result['success'] ? 'OK' : 'FAIL') . "\n";
}

$duration = microtime(true) - $startTime;
echo "\nProcessed $concurrentCalls calls in " . round($duration, 2) . " seconds\n";
echo "Average: " . round($duration / $concurrentCalls, 3) . " seconds per call\n";
EOF

php test-mcp-load.php
```

## Phase 4: Monitoring & Verification

### 4.1 Real-time Monitoring Dashboard
```bash
# Terminal 1: Laravel Logs
tail -f storage/logs/laravel.log | grep -E "MCP|Webhook|Call|Appointment"

# Terminal 2: Database Activity
watch -n 2 'mysql -u askproai_user -plkZ57Dju9EDjrMxn askproai_db -e "
SELECT COUNT(*) as calls FROM calls WHERE created_at > NOW() - INTERVAL 1 HOUR;
SELECT COUNT(*) as appointments FROM appointments WHERE created_at > NOW() - INTERVAL 1 HOUR;
"'

# Terminal 3: System Metrics
watch -n 1 'echo "=== MCP Service Status ===" && \
curl -s http://localhost/api/mcp/health | jq . && \
echo && \
echo "=== Recent Activity ===" && \
curl -s http://localhost/api/mcp/retell/recent-calls?company_id=1&limit=5 | jq .calls[].created_at'
```

### 4.2 Verification Queries
```sql
-- Check successful bookings
SELECT 
    c.retell_call_id,
    c.from_number,
    c.extracted_name,
    c.extracted_date,
    c.extracted_time,
    a.id as appointment_id,
    a.calcom_booking_id,
    a.status as appointment_status
FROM calls c
LEFT JOIN appointments a ON a.call_id = c.id
WHERE c.created_at > NOW() - INTERVAL 1 HOUR
ORDER BY c.created_at DESC;

-- Check webhook processing performance
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
    COUNT(*) as total_calls,
    SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as with_appointments,
    AVG(duration_sec) as avg_duration_seconds
FROM calls
WHERE created_at > NOW() - INTERVAL 24 HOUR
GROUP BY hour
ORDER BY hour DESC;
```

## Phase 5: Error Scenarios

### 5.1 Circuit Breaker Test
```bash
# Simulate Cal.com API failure
# Temporarily block Cal.com API in firewall
sudo iptables -A OUTPUT -d api.cal.com -j DROP

# Run booking test - should trigger circuit breaker
php test-complete-webhook-flow.php

# Monitor circuit breaker status
tail -f storage/logs/laravel.log | grep "circuit breaker"

# Restore connection
sudo iptables -D OUTPUT -d api.cal.com -j DROP
```

### 5.2 Rate Limiting Test
```bash
# Flood the webhook endpoint
for i in {1..100}; do
    curl -X POST http://localhost/api/mcp/retell/webhook \
        -H "Content-Type: application/json" \
        -d '{"event":"call_ended","call":{"call_id":"rate_test_'$i'"}}' &
done

# Check rate limit responses
# Should see 429 responses after threshold
```

## Success Criteria

### ✅ Phase 1 Complete When:
- [ ] Company has both API keys configured
- [ ] Branch has phone number assigned
- [ ] Branch has Cal.com event type assigned
- [ ] Branch has Retell agent ID assigned

### ✅ Phase 2 Complete When:
- [ ] Phone resolution returns correct branch
- [ ] Cal.com connection test passes
- [ ] Event types load successfully
- [ ] Availability check returns slots

### ✅ Phase 3 Complete When:
- [ ] Successful booking creates appointment
- [ ] Info-only calls don't create appointments
- [ ] Incomplete data handled gracefully
- [ ] Load test processes all calls

### ✅ Phase 4 Complete When:
- [ ] Monitoring shows real-time activity
- [ ] Database queries confirm data integrity
- [ ] Performance metrics are acceptable

### ✅ Phase 5 Complete When:
- [ ] Circuit breaker activates on API failure
- [ ] Rate limiting prevents overload
- [ ] System recovers gracefully

## Troubleshooting Guide

### Problem: "Event type not found"
```bash
# Re-sync event types
php artisan calcom:sync-event-types --company=1

# Check event types in DB
mysql -e "SELECT * FROM calcom_event_types WHERE company_id = 1;"
```

### Problem: "Phone number not resolved"
```bash
# Check phone number mapping
mysql -e "SELECT * FROM phone_numbers WHERE number LIKE '%3083793369%';"

# Clear phone resolution cache
php artisan cache:clear
```

### Problem: "Booking fails"
```bash
# Check Cal.com API status
curl -H "Authorization: Bearer YOUR_API_KEY" https://api.cal.com/v2/event-types

# Check webhook logs
tail -f storage/logs/laravel.log | grep -A 10 "Cal.com"
```

---
Erstellt: 2025-06-21
Version: 1.0
Status: PRODUCTION READY TEST PLAN