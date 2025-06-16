<?php
/**
 * Cal.com Webhook Test - Simplified
 * Verify webhook handling without Laravel bootstrap
 */

echo "Cal.com Webhook Test\n";
echo "====================\n\n";

// Test webhook configuration
$webhookSecret = '6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7';
$webhookUrl = 'https://api.askproai.de/api/calcom/webhook';

// Check webhook controller
echo "Test 1: Webhook Controller Analysis\n";
echo "-----------------------------------\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/CalcomWebhookController.php';
if (file_exists($controllerFile)) {
    echo "✓ CalcomWebhookController exists\n\n";
    
    $content = file_get_contents($controllerFile);
    
    // Extract handled events
    preg_match_all('/case\s+[\'"]([^\'"]*)[\'"]:/', $content, $matches);
    if (!empty($matches[1])) {
        echo "Handled webhook events:\n";
        foreach ($matches[1] as $event) {
            echo "  • $event\n";
        }
    }
    
    // Check key features
    echo "\nController features:\n";
    echo "  " . (strpos($content, 'X-Cal-Signature-256') !== false ? "✓" : "✗") . " Signature verification\n";
    echo "  " . (strpos($content, 'CalcomBooking::create') !== false ? "✓" : "✗") . " Booking creation\n";
    echo "  " . (strpos($content, 'Appointment::') !== false ? "✓" : "✗") . " Appointment management\n";
    echo "  " . (strpos($content, 'Log::') !== false ? "✓" : "✗") . " Logging\n";
} else {
    echo "✗ CalcomWebhookController not found\n";
}

// Check webhook routes
echo "\n\nTest 2: Webhook Route Configuration\n";
echo "-----------------------------------\n";

$routeFiles = [
    'routes/api.php',
    'routes/web.php'
];

foreach ($routeFiles as $routeFile) {
    if (file_exists($routeFile)) {
        $content = file_get_contents($routeFile);
        if (strpos($content, 'calcom/webhook') !== false) {
            echo "✓ Webhook route found in $routeFile\n";
            
            // Extract route details
            preg_match('/Route::(post|any)\([\'"]([^\'"]*)calcom\/webhook[\'"].*?\)/', $content, $match);
            if ($match) {
                echo "  Method: " . strtoupper($match[1]) . "\n";
                echo "  Path: " . $match[2] . "calcom/webhook\n";
            }
        }
    }
}

// Test webhook payload processing
echo "\n\nTest 3: Webhook Payload Examples\n";
echo "---------------------------------\n";

$samplePayloads = [
    'BOOKING_CREATED' => [
        'triggerEvent' => 'BOOKING_CREATED',
        'createdAt' => '2025-06-12T18:00:00.000Z',
        'payload' => [
            'id' => 789012,
            'uid' => 'abc-def-ghi',
            'idempotencyKey' => 'unique-key-123',
            'eventTypeId' => 2563193,
            'title' => 'Demo Meeting',
            'description' => 'Test booking',
            'startTime' => '2025-06-13T14:00:00.000Z',
            'endTime' => '2025-06-13T15:00:00.000Z',
            'attendees' => [
                [
                    'id' => 456,
                    'email' => 'customer@example.com',
                    'name' => 'Test Customer',
                    'timeZone' => 'Europe/Berlin',
                    'language' => 'de'
                ]
            ],
            'user' => [
                'id' => 1414768,
                'email' => 'staff@askproai.de',
                'name' => 'Staff Member',
                'username' => 'staff'
            ],
            'location' => [
                'type' => 'attendeeInPerson',
                'value' => 'phone'
            ],
            'destinationCalendar' => null,
            'cancellationReason' => null,
            'rejectionReason' => null,
            'metadata' => [
                'source' => 'askproai',
                'via' => 'phone_ai'
            ],
            'status' => 'ACCEPTED'
        ]
    ]
];

foreach ($samplePayloads as $eventType => $payload) {
    $payloadJson = json_encode($payload, JSON_PRETTY_PRINT);
    $signature = hash_hmac('sha256', json_encode($payload), $webhookSecret);
    
    echo "\nEvent: $eventType\n";
    echo "Signature: $signature\n";
    echo "Payload structure:\n";
    echo "  - triggerEvent: {$payload['triggerEvent']}\n";
    echo "  - bookingId: " . ($payload['payload']['id'] ?? 'N/A') . "\n";
    echo "  - eventTypeId: " . ($payload['payload']['eventTypeId'] ?? 'N/A') . "\n";
    echo "  - startTime: " . ($payload['payload']['startTime'] ?? 'N/A') . "\n";
    echo "  - attendee: " . ($payload['payload']['attendees'][0]['name'] ?? 'N/A') . "\n";
}

// Check database schema
echo "\n\nTest 4: Database Schema Check\n";
echo "-----------------------------\n";

$migrations = glob(__DIR__ . '/database/migrations/*calcom*.php');
echo "Found " . count($migrations) . " Cal.com related migrations:\n";
foreach ($migrations as $migration) {
    echo "  • " . basename($migration) . "\n";
}

// Summary
echo "\n\n";
echo "========================================\n";
echo "WEBHOOK IMPLEMENTATION CHECKLIST\n";
echo "========================================\n\n";

$checklist = [
    'Webhook URL registered with Cal.com' => 'https://api.askproai.de/api/calcom/webhook',
    'Signature verification implemented' => 'Using X-Cal-Signature-256 header',
    'Event handlers for BOOKING_CREATED' => 'Create/update appointment records',
    'Event handlers for BOOKING_CANCELLED' => 'Mark appointments as cancelled',
    'Event handlers for BOOKING_RESCHEDULED' => 'Update appointment times',
    'Idempotency handling' => 'Use idempotencyKey to prevent duplicates',
    'Error logging' => 'Log failed webhook processing',
    'Retry mechanism' => 'Return 2xx for success, 4xx/5xx for retry'
];

foreach ($checklist as $item => $description) {
    echo "□ $item\n";
    echo "  $description\n\n";
}

echo "V2 API Webhook Differences:\n";
echo "---------------------------\n";
echo "• Same webhook events as V1\n";
echo "• Same signature verification method\n";
echo "• Payload structure remains consistent\n";
echo "• No changes needed for V2 migration\n";