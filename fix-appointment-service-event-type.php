<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "================================\n";
echo "Fixing AppointmentBookingService\n";
echo "================================\n\n";

// Read the current service file
$filePath = __DIR__ . '/app/Services/AppointmentBookingService.php';
$content = file_get_contents($filePath);

// Fix 1: Check if eventType and calcom_numeric_event_type_id exist before using them
$search1 = <<<'PHP'
                // Add event type information if available from matching
                if (isset($eventType)) {
                    $appointmentData['calcom_event_type_id'] = $eventType->calcom_numeric_event_type_id;
PHP;

$replace1 = <<<'PHP'
                // Add event type information if available from matching
                if (isset($eventType) && !empty($eventType->calcom_numeric_event_type_id)) {
                    $appointmentData['calcom_event_type_id'] = $eventType->calcom_numeric_event_type_id;
                } elseif ($branch->calcom_event_type_id) {
                    // Fall back to branch's default event type if valid
                    $eventTypeExists = \App\Models\CalcomEventType::where('id', $branch->calcom_event_type_id)->exists();
                    if ($eventTypeExists) {
                        $appointmentData['calcom_event_type_id'] = $branch->calcom_event_type_id;
                    }
PHP;

if (strpos($content, $search1) !== false) {
    $content = str_replace($search1, $replace1, $content);
    echo "✅ Fixed event type assignment logic\n";
} else {
    echo "⚠️  Could not find event type assignment code to fix\n";
}

// Fix 2: Make sure eventType->id is checked before accessing
$search2 = <<<'PHP'
                        Log::info('Event type match found', [
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'event_type_id' => $eventType->id,
                            'duration' => $serviceDuration
                        ]);
PHP;

$replace2 = <<<'PHP'
                        Log::info('Event type match found', [
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'event_type_id' => $eventType ? ($eventType->id ?? null) : null,
                            'duration' => $serviceDuration
                        ]);
PHP;

if (strpos($content, $search2) !== false) {
    $content = str_replace($search2, $replace2, $content);
    echo "✅ Fixed event type logging\n";
} else {
    echo "⚠️  Could not find event type logging code to fix\n";
}

// Write the fixed content back
file_put_contents($filePath, $content);

echo "\nFix completed!\n";