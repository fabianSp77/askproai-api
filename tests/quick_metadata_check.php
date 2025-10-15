#!/usr/bin/env php
<?php

/**
 * Quick Metadata Field Check
 *
 * Validates that all appointment metadata columns exist in database
 * without requiring full test data creation
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== APPOINTMENT METADATA FIELD CHECK ===\n\n";

// Get columns from appointments table
$columns = DB::select("SHOW COLUMNS FROM appointments");
$columnNames = array_column($columns, 'Field');

$metadataColumns = [
    'Booking Fields' => [
        'created_by',
        'booking_source',
        'booked_by_user_id',
    ],
    'Reschedule Fields' => [
        'rescheduled_at',
        'rescheduled_by',
        'reschedule_source',
        'previous_starts_at',
    ],
    'Cancellation Fields' => [
        'cancelled_at',
        'cancelled_by',
        'cancellation_source',
    ],
];

$totalChecks = 0;
$passed = 0;
$failed = 0;

foreach ($metadataColumns as $category => $fields) {
    echo "{$category}:\n";
    foreach ($fields as $field) {
        $totalChecks++;
        $exists = in_array($field, $columnNames);

        if ($exists) {
            echo "  ✓ {$field}\n";
            $passed++;
        } else {
            echo "  ✗ {$field} (MISSING)\n";
            $failed++;
        }
    }
    echo "\n";
}

echo "Summary:\n";
echo "  Total: {$totalChecks}\n";
echo "  Passed: {$passed}\n";
echo "  Failed: {$failed}\n\n";

if ($failed === 0) {
    echo "✓ All metadata columns exist!\n\n";
    exit(0);
} else {
    echo "✗ Some metadata columns are missing.\n";
    echo "  You may need to create or run migrations.\n\n";
    exit(1);
}
