#!/usr/bin/env php
<?php

/**
 * Verification Script for Cal.com Sync Button Fix
 *
 * This script verifies that the ViewService.php file has been properly updated
 * and that all components are in place for Cal.com sync functionality.
 *
 * Usage: php verify_sync_button_fix.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Cal.com Sync Button Fix - Verification Script\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$errors = [];
$warnings = [];
$success = [];

// ═══════════════════════════════════════════════════════════
// 1. Verify ViewService.php has no TODO comments
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking for TODO comments...\n";
$viewServicePath = __DIR__ . '/app/Filament/Resources/ServiceResource/Pages/ViewService.php';
$viewServiceContent = file_get_contents($viewServicePath);

if (strpos($viewServiceContent, 'TODO') !== false) {
    $errors[] = "❌ TODO comments still exist in ViewService.php";
} else {
    $success[] = "✅ No TODO comments found";
}

// ═══════════════════════════════════════════════════════════
// 2. Verify UpdateCalcomEventTypeJob is imported
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking UpdateCalcomEventTypeJob import...\n";
if (strpos($viewServiceContent, 'use App\Jobs\UpdateCalcomEventTypeJob;') !== false) {
    $success[] = "✅ UpdateCalcomEventTypeJob imported";
} else {
    $errors[] = "❌ UpdateCalcomEventTypeJob not imported";
}

// ═══════════════════════════════════════════════════════════
// 3. Verify job dispatch is implemented
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking job dispatch implementation...\n";
if (strpos($viewServiceContent, 'UpdateCalcomEventTypeJob::dispatch') !== false) {
    $success[] = "✅ Job dispatch implemented";
} else {
    $errors[] = "❌ Job dispatch not found";
}

// ═══════════════════════════════════════════════════════════
// 4. Verify confirmation modal is implemented
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking confirmation modal...\n";
if (strpos($viewServiceContent, '->requiresConfirmation()') !== false &&
    strpos($viewServiceContent, '->modalHeading') !== false &&
    strpos($viewServiceContent, '->modalDescription') !== false) {
    $success[] = "✅ Confirmation modal implemented";
} else {
    $errors[] = "❌ Confirmation modal not properly configured";
}

// ═══════════════════════════════════════════════════════════
// 5. Verify edge case handling
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking edge case handling...\n";
$hasEventTypeCheck = strpos($viewServiceContent, 'if (!$this->record->calcom_event_type_id)') !== false;
$hasPendingCheck = strpos($viewServiceContent, "if (\$this->record->sync_status === 'pending')") !== false;
$hasTryCatch = strpos($viewServiceContent, 'try {') !== false && strpos($viewServiceContent, '} catch (\Exception $e) {') !== false;

if ($hasEventTypeCheck && $hasPendingCheck && $hasTryCatch) {
    $success[] = "✅ All edge cases handled (no Event Type ID, pending sync, exceptions)";
} else {
    if (!$hasEventTypeCheck) $warnings[] = "⚠️ Missing Event Type ID check";
    if (!$hasPendingCheck) $warnings[] = "⚠️ Missing pending sync check";
    if (!$hasTryCatch) $warnings[] = "⚠️ Missing try-catch block";
}

// ═══════════════════════════════════════════════════════════
// 6. Verify UpdateCalcomEventTypeJob exists
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking UpdateCalcomEventTypeJob existence...\n";
$jobPath = __DIR__ . '/app/Jobs/UpdateCalcomEventTypeJob.php';
if (file_exists($jobPath)) {
    $success[] = "✅ UpdateCalcomEventTypeJob.php exists";
} else {
    $errors[] = "❌ UpdateCalcomEventTypeJob.php not found";
}

// ═══════════════════════════════════════════════════════════
// 7. Verify Service model has sync fields
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking Service model...\n";
try {
    $service = \App\Models\Service::first();
    if ($service) {
        $hasFields = isset($service->sync_status) && isset($service->calcom_event_type_id);
        if ($hasFields || property_exists($service, 'sync_status')) {
            $success[] = "✅ Service model has required sync fields";
        } else {
            $warnings[] = "⚠️ Service model may be missing sync fields";
        }
    } else {
        $warnings[] = "⚠️ No services in database to verify";
    }
} catch (\Exception $e) {
    $warnings[] = "⚠️ Could not verify Service model: " . $e->getMessage();
}

// ═══════════════════════════════════════════════════════════
// 8. Check for test service (ID 32 - AskProAI)
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking test service (ID 32)...\n";
try {
    $testService = \App\Models\Service::find(32);
    if ($testService) {
        echo "   Service Name: {$testService->name}\n";
        echo "   Event Type ID: " . ($testService->calcom_event_type_id ?? 'null') . "\n";
        echo "   Sync Status: " . ($testService->sync_status ?? 'null') . "\n";

        if ($testService->calcom_event_type_id) {
            $success[] = "✅ Test service (ID 32) has Event Type ID";
        } else {
            $warnings[] = "⚠️ Test service (ID 32) has no Event Type ID";
        }
    } else {
        $warnings[] = "⚠️ Test service (ID 32) not found";
    }
} catch (\Exception $e) {
    $warnings[] = "⚠️ Could not check test service: " . $e->getMessage();
}

// ═══════════════════════════════════════════════════════════
// 9. Verify queue configuration
// ═══════════════════════════════════════════════════════════
echo "🔍 Checking queue configuration...\n";
$queueConnection = config('queue.default');
echo "   Queue Connection: {$queueConnection}\n";

if ($queueConnection !== 'sync') {
    $success[] = "✅ Queue configured for async processing ({$queueConnection})";
} else {
    $warnings[] = "⚠️ Queue set to 'sync' - jobs will run synchronously";
}

// ═══════════════════════════════════════════════════════════
// Results Summary
// ═══════════════════════════════════════════════════════════
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Verification Results\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (count($success) > 0) {
    echo "✅ SUCCESS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "   {$msg}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "   {$msg}\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "   {$msg}\n";
    }
    echo "\n";
}

// ═══════════════════════════════════════════════════════════
// Final Status
// ═══════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════\n";
if (count($errors) === 0) {
    echo "  ✅ VERIFICATION PASSED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "Next Steps:\n";
    echo "1. Test sync button in Filament UI: /admin/services/32\n";
    echo "2. Process queue job: php artisan queue:work --once\n";
    echo "3. Monitor logs: tail -f storage/logs/laravel.log\n\n";
    exit(0);
} else {
    echo "  ❌ VERIFICATION FAILED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "Please fix the errors above before proceeding.\n\n";
    exit(1);
}
