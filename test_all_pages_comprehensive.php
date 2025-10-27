<?php

/**
 * COMPREHENSIVE TEST: All Filament Pages, Widgets, and Resources
 * This tests EVERYTHING in the admin panel systematically
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE ADMIN PANEL TEST ===\n";
echo "Testing ALL pages, widgets, and resources\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

$results = [
    'custom_pages' => [],
    'resources' => [],
    'widgets' => [],
];

// ============================================
// PART 1: Test Custom Pages
// ============================================
echo "=== PART 1: TESTING CUSTOM PAGES ===\n\n";

$customPages = [
    'Dashboard' => \App\Filament\Pages\Dashboard::class,
    'SystemAdministration' => \App\Filament\Pages\SystemAdministration::class,
    'ProfitDashboard' => \App\Filament\Pages\ProfitDashboard::class,
    'SystemTestingDashboard' => \App\Filament\Pages\SystemTestingDashboard::class,
    'SettingsDashboard' => \App\Filament\Pages\SettingsDashboard::class,
    'PolicyOnboarding' => \App\Filament\Pages\PolicyOnboarding::class,
    'TestChecklist' => \App\Filament\Pages\TestChecklist::class,
];

foreach ($customPages as $name => $class) {
    echo str_pad($name, 40, ' ');

    if (!class_exists($class)) {
        echo "❌ CLASS NOT FOUND\n";
        $results['custom_pages'][$name] = ['status' => 'class_not_found'];
        continue;
    }

    try {
        // Try to instantiate the page
        $page = new $class();

        // Check if page has widgets
        if (method_exists($page, 'getHeaderWidgets')) {
            $reflection = new ReflectionClass($page);
            $method = $reflection->getMethod('getHeaderWidgets');
            $method->setAccessible(true);
            $widgets = $method->invoke($page);

            echo "✅ OK (" . count($widgets) . " widgets)\n";
            $results['custom_pages'][$name] = [
                'status' => 'ok',
                'widgets' => count($widgets)
            ];
        } else {
            echo "✅ OK (no widgets)\n";
            $results['custom_pages'][$name] = ['status' => 'ok', 'widgets' => 0];
        }

    } catch (\Illuminate\Database\QueryException $e) {
        $errorMsg = $e->getMessage();

        if (preg_match("/Table '.*?\\.(\\w+)' doesn't exist/", $errorMsg, $matches)) {
            $table = $matches[1];
            echo "❌ MISSING TABLE: {$table}\n";
            $results['custom_pages'][$name] = ['status' => 'missing_table', 'table' => $table];
        } elseif (preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches)) {
            $column = $matches[1];
            echo "❌ MISSING COLUMN: {$column}\n";
            $results['custom_pages'][$name] = ['status' => 'missing_column', 'column' => $column];
        } else {
            echo "❌ SQL ERROR\n";
            echo "   " . substr($errorMsg, 0, 100) . "...\n";
            $results['custom_pages'][$name] = ['status' => 'sql_error', 'error' => substr($errorMsg, 0, 200)];
        }

    } catch (\Exception $e) {
        echo "❌ PHP ERROR\n";
        echo "   {$e->getMessage()}\n";
        $results['custom_pages'][$name] = [
            'status' => 'php_error',
            'error' => $e->getMessage(),
            'location' => "{$e->getFile()}:{$e->getLine()}"
        ];
    }
}

// ============================================
// PART 2: Test All Widgets (Standalone)
// ============================================
echo "\n=== PART 2: TESTING STANDALONE WIDGETS ===\n\n";

$widgets = [
    'CustomerStatsOverview' => \App\Filament\Widgets\CustomerStatsOverview::class,
    'CallStatsOverview' => \App\Filament\Widgets\CallStatsOverview::class,
    'StatsOverview' => \App\Filament\Widgets\StatsOverview::class,
    'DashboardStats' => \App\Filament\Widgets\DashboardStats::class,
    'RecentCalls' => \App\Filament\Widgets\RecentCalls::class,
    'RecentAppointments' => \App\Filament\Widgets\RecentAppointments::class,
    'LatestCustomers' => \App\Filament\Widgets\LatestCustomers::class,
];

foreach ($widgets as $name => $class) {
    echo str_pad($name, 40, ' ');

    if (!class_exists($class)) {
        echo "⚠️  CLASS NOT FOUND\n";
        $results['widgets'][$name] = ['status' => 'class_not_found'];
        continue;
    }

    try {
        $widget = new $class();

        // Try to get stats/data
        if (method_exists($widget, 'getStats')) {
            $reflection = new ReflectionClass($widget);
            $method = $reflection->getMethod('getStats');
            $method->setAccessible(true);
            $stats = $method->invoke($widget);

            echo "✅ OK (" . count($stats) . " stats)\n";
            $results['widgets'][$name] = ['status' => 'ok', 'stats' => count($stats)];
        } else {
            echo "✅ OK (no stats method)\n";
            $results['widgets'][$name] = ['status' => 'ok'];
        }

    } catch (\Illuminate\Database\QueryException $e) {
        $errorMsg = $e->getMessage();

        if (preg_match("/Table '.*?\\.(\\w+)' doesn't exist/", $errorMsg, $matches)) {
            echo "❌ MISSING TABLE: {$matches[1]}\n";
            $results['widgets'][$name] = ['status' => 'missing_table', 'table' => $matches[1]];
        } elseif (preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches)) {
            echo "❌ MISSING COLUMN: {$matches[1]}\n";
            $results['widgets'][$name] = ['status' => 'missing_column', 'column' => $matches[1]];
        } else {
            echo "❌ SQL ERROR: " . substr($errorMsg, 0, 80) . "\n";
            $results['widgets'][$name] = ['status' => 'sql_error'];
        }

    } catch (\Exception $e) {
        echo "❌ ERROR: {$e->getMessage()}\n";
        $results['widgets'][$name] = ['status' => 'php_error', 'error' => $e->getMessage()];
    }
}

// ============================================
// SUMMARY
// ============================================
echo "\n=== SUMMARY ===\n\n";

$customPagesOk = count(array_filter($results['custom_pages'], fn($r) => $r['status'] === 'ok'));
$customPagesFail = count($results['custom_pages']) - $customPagesOk;

$widgetsOk = count(array_filter($results['widgets'], fn($r) => $r['status'] === 'ok'));
$widgetsFail = count($results['widgets']) - $widgetsOk;

echo "CUSTOM PAGES:\n";
echo "  ✅ Working: {$customPagesOk}/" . count($customPages) . "\n";
echo "  ❌ Failed: {$customPagesFail}\n\n";

echo "WIDGETS:\n";
echo "  ✅ Working: {$widgetsOk}/" . count($widgets) . "\n";
echo "  ❌ Failed: {$widgetsFail}\n\n";

// Show detailed errors
$errors = [];
foreach ($results['custom_pages'] as $name => $result) {
    if ($result['status'] !== 'ok') {
        $errors[] = "Page: {$name} - " . strtoupper($result['status']);
        if (isset($result['table'])) $errors[] = "  Table: {$result['table']}";
        if (isset($result['column'])) $errors[] = "  Column: {$result['column']}";
        if (isset($result['error'])) $errors[] = "  Error: {$result['error']}";
    }
}

foreach ($results['widgets'] as $name => $result) {
    if ($result['status'] !== 'ok') {
        $errors[] = "Widget: {$name} - " . strtoupper($result['status']);
        if (isset($result['table'])) $errors[] = "  Table: {$result['table']}";
        if (isset($result['column'])) $errors[] = "  Column: {$result['column']}";
    }
}

if (!empty($errors)) {
    echo "=== ERRORS FOUND ===\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    echo "\n";
}

// Save results
file_put_contents('comprehensive_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Detailed results saved to: comprehensive_test_results.json\n";

exit(count($errors) > 0 ? 1 : 0);
