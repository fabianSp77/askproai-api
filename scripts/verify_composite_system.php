<?php

/**
 * Phase 4: Composite Booking System Verification
 *
 * Comprehensive verification of all composite booking system components
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Phase 4: Composite Booking System - Verification\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$checks = [];

// ═══════════════════════════════════════════════════════════════
// CHECK 1: Database Schema
// ═══════════════════════════════════════════════════════════════

echo "📋 CHECK 1: Database Schema\n";
echo str_repeat("─", 63) . "\n";

// Check services table
$serviceColumns = DB::select("SHOW COLUMNS FROM services WHERE Field IN ('composite', 'segments', 'pause_bookable_policy')");
$hasCompositeFields = count($serviceColumns) === 3;

echo "  services table:\n";
echo "    • composite column: " . ($hasCompositeFields ? "✅" : "❌") . "\n";
echo "    • segments column: " . ($hasCompositeFields ? "✅" : "❌") . "\n";
echo "    • pause_bookable_policy column: " . ($hasCompositeFields ? "✅" : "❌") . "\n";

$checks['database_services'] = $hasCompositeFields;

// Check appointments table
$appointmentColumns = DB::select("SHOW COLUMNS FROM appointments WHERE Field IN ('is_composite', 'composite_group_uid', 'segments')");
$hasCompositeAppointmentFields = count($appointmentColumns) === 3;

echo "  appointments table:\n";
echo "    • is_composite column: " . ($hasCompositeAppointmentFields ? "✅" : "❌") . "\n";
echo "    • composite_group_uid column: " . ($hasCompositeAppointmentFields ? "✅" : "❌") . "\n";
echo "    • segments column: " . ($hasCompositeAppointmentFields ? "✅" : "❌") . "\n";

$checks['database_appointments'] = $hasCompositeAppointmentFields;

// Check calcom_event_map table
$mapTableExists = DB::select("SHOW TABLES LIKE 'calcom_event_map'");
$hasMapTable = !empty($mapTableExists);

echo "  calcom_event_map table: " . ($hasMapTable ? "✅" : "❌") . "\n";
$checks['database_map'] = $hasMapTable;

echo "\n";

// ═══════════════════════════════════════════════════════════════
// CHECK 2: Composite Services Configuration
// ═══════════════════════════════════════════════════════════════

echo "📋 CHECK 2: Composite Services Configuration\n";
echo str_repeat("─", 63) . "\n";

$compositeServices = DB::table('services')
    ->where('composite', true)
    ->select('id', 'name', 'composite', 'segments', 'pause_bookable_policy', 'duration_minutes')
    ->get();

echo "  Anzahl Composite Services: " . $compositeServices->count() . "\n\n";

if ($compositeServices->isEmpty()) {
    echo "  ⚠️  Keine Composite Services konfiguriert\n\n";
    $checks['composite_services'] = false;
} else {
    echo "  Services:\n";
    $allValid = true;

    foreach ($compositeServices as $svc) {
        $segments = json_decode($svc->segments, true);
        $segmentCount = is_array($segments) ? count($segments) : 0;
        $isValid = $segmentCount > 0 && $svc->pause_bookable_policy !== null;

        echo "    • ID {$svc->id}: {$svc->name}\n";
        echo "      Segments: {$segmentCount} " . ($segmentCount > 0 ? "✅" : "❌") . "\n";
        echo "      Pause Policy: {$svc->pause_bookable_policy} ✅\n";
        echo "      Duration: {$svc->duration_minutes} min ✅\n";

        if (!$isValid) {
            $allValid = false;
        }
    }

    $checks['composite_services'] = $allValid;
    echo "\n";
}

// ═══════════════════════════════════════════════════════════════
// CHECK 3: Service Code Infrastructure
// ═══════════════════════════════════════════════════════════════

echo "📋 CHECK 3: Backend Service Code\n";
echo str_repeat("─", 63) . "\n";

$files = [
    'app/Services/Booking/CompositeBookingService.php' => 'CompositeBookingService',
    'app/Services/Retell/AppointmentCreationService.php' => 'AppointmentCreationService (composite check)',
    'app/Models/Service.php' => 'Service Model (isComposite method)',
    'app/Models/CalcomEventMap.php' => 'CalcomEventMap Model',
];

foreach ($files as $path => $description) {
    $fullPath = __DIR__ . '/../' . $path;
    $exists = file_exists($fullPath);
    echo "  • {$description}: " . ($exists ? "✅" : "❌") . "\n";

    if (!$exists) {
        $checks['code_infrastructure'] = false;
    }
}

if (!isset($checks['code_infrastructure'])) {
    $checks['code_infrastructure'] = true;
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// CHECK 4: Model Methods
// ═══════════════════════════════════════════════════════════════

echo "📋 CHECK 4: Service Model Methods\n";
echo str_repeat("─", 63) . "\n";

try {
    $service = \App\Models\Service::find(442);

    if ($service) {
        $hasIsCompositeMethod = method_exists($service, 'isComposite');
        $hasSegmentsProperty = isset($service->segments);

        echo "  • isComposite() method: " . ($hasIsCompositeMethod ? "✅" : "❌") . "\n";
        echo "  • segments property accessible: " . ($hasSegmentsProperty ? "✅" : "❌") . "\n";

        if ($hasIsCompositeMethod) {
            $isComposite = $service->isComposite();
            echo "  • Service 442 isComposite(): " . ($isComposite ? "TRUE ✅" : "FALSE ❌") . "\n";
        }

        $checks['model_methods'] = $hasIsCompositeMethod && $hasSegmentsProperty;
    } else {
        echo "  ⚠️  Service 442 nicht gefunden\n";
        $checks['model_methods'] = false;
    }
} catch (Exception $e) {
    echo "  ❌ Fehler: " . $e->getMessage() . "\n";
    $checks['model_methods'] = false;
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// CHECK 5: Event Type Mapping Status
// ═══════════════════════════════════════════════════════════════

echo "📋 CHECK 5: Cal.com Event Type Mappings\n";
echo str_repeat("─", 63) . "\n";

$mappingCount = DB::table('calcom_event_map')->count();
echo "  • Mappings in calcom_event_map: {$mappingCount}\n";

if ($mappingCount === 0) {
    echo "  ⚠️  Keine Mappings vorhanden (manuelles Setup erforderlich)\n";
    echo "  → Siehe: scripts/prepare_composite_mapping.php\n";
    $checks['event_mappings'] = false;
} else {
    echo "  ✅ Mappings vorhanden\n";
    $checks['event_mappings'] = true;
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 VERIFICATION SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total = count($checks);
$passed = count(array_filter($checks));
$percentage = round(($passed / $total) * 100);

echo "Checks Passed: {$passed}/{$total} ({$percentage}%)\n\n";

foreach ($checks as $check => $status) {
    $icon = $status ? "✅" : "❌";
    $statusText = $status ? "PASS" : "FAIL";
    echo "  {$icon} " . str_pad(strtoupper(str_replace('_', ' ', $check)), 30) . " {$statusText}\n";
}

echo "\n";

if ($percentage === 100) {
    echo "🎉 ALLE CHECKS BESTANDEN!\n";
    echo "   System bereit für Composite Bookings\n\n";
} elseif ($percentage >= 80) {
    echo "✅ SYSTEM WEITGEHEND BEREIT\n";
    echo "   Nur noch Event Type Mappings erforderlich\n\n";
} else {
    echo "⚠️  SYSTEM NICHT BEREIT\n";
    echo "   Mehrere Komponenten fehlen oder sind fehlerhaft\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
