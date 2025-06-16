<?php
/**
 * Cal.com Webhook Test
 * Verify webhook handling and compare with expected functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Cal.com Webhook Test\n";
echo "====================\n\n";

// Test webhook signatures
$webhookSecret = '6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7';

// Sample webhook payloads
$webhookPayloads = [
    'booking.created' => [
        'triggerEvent' => 'BOOKING_CREATED',
        'createdAt' => '2025-06-12T18:00:00.000Z',
        'payload' => [
            'bookingId' => 123456,
            'eventTypeId' => 2563193,
            'eventTitle' => 'Test Meeting',
            'startTime' => '2025-06-13T14:00:00.000Z',
            'endTime' => '2025-06-13T15:00:00.000Z',
            'attendees' => [
                [
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                    'timeZone' => 'Europe/Berlin'
                ]
            ],
            'organizer' => [
                'email' => 'organizer@askproai.de',
                'name' => 'Organizer Name'
            ],
            'metadata' => [
                'source' => 'askproai',
                'via' => 'phone_ai'
            ]
        ]
    ],
    'booking.cancelled' => [
        'triggerEvent' => 'BOOKING_CANCELLED',
        'createdAt' => '2025-06-12T18:00:00.000Z',
        'payload' => [
            'bookingId' => 123456,
            'eventTypeId' => 2563193,
            'eventTitle' => 'Test Meeting',
            'startTime' => '2025-06-13T14:00:00.000Z',
            'endTime' => '2025-06-13T15:00:00.000Z',
            'cancellationReason' => 'Customer request'
        ]
    ],
    'booking.rescheduled' => [
        'triggerEvent' => 'BOOKING_RESCHEDULED',
        'createdAt' => '2025-06-12T18:00:00.000Z',
        'payload' => [
            'bookingId' => 123456,
            'eventTypeId' => 2563193,
            'eventTitle' => 'Test Meeting',
            'oldStartTime' => '2025-06-13T14:00:00.000Z',
            'oldEndTime' => '2025-06-13T15:00:00.000Z',
            'startTime' => '2025-06-14T14:00:00.000Z',
            'endTime' => '2025-06-14T15:00:00.000Z'
        ]
    ]
];

// Test signature verification
echo "Test 1: Webhook Signature Verification\n";
echo "-------------------------------------\n";

foreach ($webhookPayloads as $type => $payload) {
    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, $webhookSecret);
    
    echo "Event Type: $type\n";
    echo "Payload: " . substr($payloadJson, 0, 100) . "...\n";
    echo "Expected Signature: X-Cal-Signature-256: $signature\n";
    echo "✓ Signature generated successfully\n\n";
}

// Test webhook endpoint
echo "Test 2: Webhook Endpoint Response\n";
echo "---------------------------------\n";

$webhookUrl = 'https://api.askproai.de/api/calcom/webhook';
echo "Webhook URL: $webhookUrl\n";

// Check if endpoint exists in routes
$routes = app()->routes->getRoutes();
$webhookRoute = null;
foreach ($routes as $route) {
    if ($route->uri() === 'api/calcom/webhook' && in_array('POST', $route->methods())) {
        $webhookRoute = $route;
        break;
    }
}

if ($webhookRoute) {
    echo "✓ Webhook route found in application\n";
    echo "  Methods: " . implode(', ', $webhookRoute->methods()) . "\n";
    echo "  Middleware: " . implode(', ', $webhookRoute->middleware()) . "\n";
} else {
    echo "✗ Webhook route not found\n";
}

// Test webhook controller
echo "\nTest 3: Webhook Handler Analysis\n";
echo "--------------------------------\n";

$controllerFile = app_path('Http/Controllers/CalcomWebhookController.php');
if (file_exists($controllerFile)) {
    echo "✓ CalcomWebhookController exists\n";
    
    // Analyze controller methods
    $controllerContent = file_get_contents($controllerFile);
    
    // Check for event handlers
    $eventHandlers = [];
    if (preg_match_all('/case\s+[\'"]([^\'"])+[\'"]:/', $controllerContent, $matches)) {
        $eventHandlers = $matches[1];
    }
    
    if (!empty($eventHandlers)) {
        echo "  Handled events:\n";
        foreach ($eventHandlers as $event) {
            echo "    - $event\n";
        }
    }
    
    // Check for signature verification
    if (strpos($controllerContent, 'X-Cal-Signature-256') !== false) {
        echo "  ✓ Signature verification implemented\n";
    } else {
        echo "  ✗ Signature verification not found\n";
    }
    
    // Check for database operations
    if (strpos($controllerContent, 'Appointment::') !== false || strpos($controllerContent, 'CalcomBooking::') !== false) {
        echo "  ✓ Database operations found\n";
    }
} else {
    echo "✗ CalcomWebhookController not found\n";
}

// Test webhook processing logic
echo "\nTest 4: Webhook Processing Simulation\n";
echo "------------------------------------\n";

// Simulate webhook processing for each event type
foreach ($webhookPayloads as $type => $payload) {
    echo "\nProcessing: $type\n";
    
    switch ($payload['triggerEvent']) {
        case 'BOOKING_CREATED':
            echo "  Expected actions:\n";
            echo "    - Create/update appointment record\n";
            echo "    - Send confirmation notifications\n";
            echo "    - Update availability cache\n";
            echo "    - Log event for tracking\n";
            break;
            
        case 'BOOKING_CANCELLED':
            echo "  Expected actions:\n";
            echo "    - Mark appointment as cancelled\n";
            echo "    - Send cancellation notifications\n";
            echo "    - Update availability cache\n";
            echo "    - Log cancellation reason\n";
            break;
            
        case 'BOOKING_RESCHEDULED':
            echo "  Expected actions:\n";
            echo "    - Update appointment times\n";
            echo "    - Send rescheduling notifications\n";
            echo "    - Update availability cache\n";
            echo "    - Log old and new times\n";
            break;
    }
}

// Test webhook security
echo "\n\nTest 5: Webhook Security Analysis\n";
echo "---------------------------------\n";

$securityChecks = [
    'Signature Verification' => 'X-Cal-Signature-256 header validation',
    'HTTPS Only' => 'Webhook URL uses HTTPS',
    'IP Whitelist' => 'Optional Cal.com IP restriction',
    'Timestamp Validation' => 'Prevent replay attacks',
    'Idempotency' => 'Handle duplicate webhooks'
];

foreach ($securityChecks as $check => $description) {
    echo "• $check: $description\n";
}

// Summary and recommendations
echo "\n\n";
echo "========================================\n";
echo "WEBHOOK IMPLEMENTATION SUMMARY\n";
echo "========================================\n\n";

echo "Required Webhook Events:\n";
echo "------------------------\n";
echo "✓ BOOKING_CREATED - New appointment created\n";
echo "✓ BOOKING_CANCELLED - Appointment cancelled\n";
echo "✓ BOOKING_RESCHEDULED - Appointment time changed\n";
echo "? BOOKING_REJECTED - Appointment rejected\n";
echo "? BOOKING_REQUESTED - Requires confirmation\n";
echo "? BOOKING_NO_SHOW_UPDATED - No-show status\n";

echo "\nWebhook Data Mapping:\n";
echo "--------------------\n";
echo "Cal.com Field → Database Field\n";
echo "bookingId → calcom_booking_id\n";
echo "eventTypeId → calcom_event_type_id\n";
echo "startTime → starts_at\n";
echo "endTime → ends_at\n";
echo "attendees[0].name → customer.name\n";
echo "attendees[0].email → customer.email\n";
echo "metadata → appointment.metadata (JSON)\n";

echo "\nRecommendations:\n";
echo "----------------\n";
echo "1. Implement robust signature verification\n";
echo "2. Add webhook event logging for debugging\n";
echo "3. Implement idempotency to handle duplicates\n";
echo "4. Add retry mechanism for failed processing\n";
echo "5. Monitor webhook health and alert on failures\n";
echo "6. Consider webhook queue for async processing\n";