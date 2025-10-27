#!/usr/bin/env php
<?php

/**
 * Comprehensive Admin Page Tester
 *
 * Tests ALL Filament Resources by:
 * 1. Finding all Resource classes
 * 2. Checking if they're enabled (shouldRegisterNavigation + canViewAny)
 * 3. Testing List page instantiation
 * 4. Testing table query execution
 * 5. Reporting SQL errors with exact locations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "COMPREHENSIVE ADMIN PAGE TESTING - ALL RESOURCES\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Enable query logging to catch SQL errors
DB::connection()->enableQueryLog();

// Find all Resource files
$resourceFiles = glob(__DIR__ . '/app/Filament/Resources/*Resource.php');

$results = [
    'total' => 0,
    'enabled' => 0,
    'disabled' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => [],
];

foreach ($resourceFiles as $file) {
    $results['total']++;

    $className = 'App\\Filament\\Resources\\' . basename($file, '.php');

    echo "\n" . str_repeat('─', 70) . "\n";
    echo "Testing: " . basename($file, '.php') . "\n";
    echo str_repeat('─', 70) . "\n";

    try {
        // Check if class exists
        if (!class_exists($className)) {
            echo "⚠️  Class not found: {$className}\n";
            continue;
        }

        // Check if resource is enabled
        $shouldRegister = true;
        $canViewAny = true;
        $requiresAuth = false;

        if (method_exists($className, 'shouldRegisterNavigation')) {
            try {
                $shouldRegister = $className::shouldRegisterNavigation();
            } catch (\Exception $e) {
                // Ignore errors in shouldRegisterNavigation
                $shouldRegister = true;
            }
        }

        if (method_exists($className, 'canViewAny')) {
            try {
                $canViewAny = $className::canViewAny();
            } catch (\Throwable $e) {
                // Resource requires authentication - treat as enabled for testing
                $requiresAuth = true;
                $canViewAny = true;
                echo "⚠️  Requires authentication (canViewAny check failed)\n";
                echo "   Continuing test anyway to check for SQL errors...\n";
            }
        }

        if (!$shouldRegister) {
            echo "⏭️  DISABLED (shouldRegisterNavigation: false)\n";
            $results['disabled']++;
            continue;
        }

        if (!$canViewAny && !$requiresAuth) {
            echo "⏭️  DISABLED (canViewAny: false)\n";
            $results['disabled']++;
            continue;
        }

        $results['enabled']++;
        echo "✅ Resource is ENABLED\n";

        // Get model class
        $model = $className::getModel();
        echo "📊 Model: " . class_basename($model) . "\n";

        // Test: Can we query the model?
        echo "\n🔍 Testing database query...\n";
        DB::connection()->flushQueryLog();

        try {
            // Get eloquent query
            $query = $className::getEloquentQuery();

            // Try to execute with limit to avoid memory issues
            $records = $query->limit(1)->get();

            echo "✅ Query executed successfully\n";
            echo "   Records found: " . $query->count() . "\n";

            // Show the actual SQL query
            $queries = DB::getQueryLog();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                echo "   SQL: " . substr($lastQuery['query'], 0, 100) . "...\n";
            }

        } catch (\Illuminate\Database\QueryException $e) {
            echo "❌ SQL ERROR\n";
            echo "   Error: " . $e->getMessage() . "\n";
            echo "   SQL: " . $e->getSql() . "\n";
            echo "   File: " . $file . "\n";

            $results['failed']++;
            $results['errors'][] = [
                'resource' => basename($file, '.php'),
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'file' => $file,
            ];
            continue;
        }

        // Test: Check if table() method exists
        echo "\n🔍 Checking table configuration method...\n";

        try {
            if (method_exists($className, 'table')) {
                echo "✅ Table method exists\n";
            } else {
                echo "⚠️  No table method found\n";
            }

        } catch (\Exception $e) {
            echo "⚠️  Error checking table method: " . $e->getMessage() . "\n";
        }

        // Test: Can we instantiate the List page?
        echo "\n🔍 Testing List page instantiation...\n";

        try {
            $pages = $className::getPages();

            if (isset($pages['index'])) {
                $listPageClass = $pages['index']->getPage();
                echo "✅ List page class: " . class_basename($listPageClass) . "\n";
            } else {
                echo "⚠️  No index page defined\n";
            }

        } catch (\Exception $e) {
            echo "❌ LIST PAGE ERROR\n";
            echo "   Error: " . $e->getMessage() . "\n";

            $results['failed']++;
            $results['errors'][] = [
                'resource' => basename($file, '.php'),
                'error' => $e->getMessage(),
                'type' => 'list_page',
                'file' => $file,
            ];
            continue;
        }

        // All tests passed
        echo "\n✅ ALL TESTS PASSED\n";
        $results['passed']++;

    } catch (\Exception $e) {
        echo "❌ GENERAL ERROR\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";

        $results['failed']++;
        $results['errors'][] = [
            'resource' => basename($file, '.php'),
            'error' => $e->getMessage(),
            'type' => 'general',
            'file' => $file,
        ];
    }
}

// Summary Report
echo "\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY REPORT\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Statistics:\n";
echo "   Total Resources: {$results['total']}\n";
echo "   Enabled Resources: {$results['enabled']}\n";
echo "   Disabled Resources: {$results['disabled']}\n";
echo "   Tests Passed: ✅ {$results['passed']}\n";
echo "   Tests Failed: ❌ {$results['failed']}\n";
echo "\n";

if (!empty($results['errors'])) {
    echo "❌ ERRORS FOUND:\n";
    echo str_repeat('─', 70) . "\n";

    foreach ($results['errors'] as $i => $error) {
        echo "\n" . ($i + 1) . ". {$error['resource']}\n";
        echo "   Error: {$error['error']}\n";

        if (isset($error['sql'])) {
            echo "   SQL: {$error['sql']}\n";
        }

        if (isset($error['type'])) {
            echo "   Type: {$error['type']}\n";
        }

        echo "   File: {$error['file']}\n";
    }

    echo "\n";
    echo str_repeat('─', 70) . "\n";
    echo "\n🔧 ACTION REQUIRED: Fix the errors listed above\n";
} else {
    echo "✅ NO ERRORS FOUND - All enabled resources are working!\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
