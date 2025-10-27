<?php

/**
 * SYSTEMATIC RESOURCE PAGE TEST
 * Tests each Resource's pages and their widgets
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SYSTEMATIC RESOURCE PAGE TEST ===\n";
echo "Testing ALL Resource pages and their widgets\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

$results = ['ok' => [], 'errors' => []];

// Get all Resource files
$resourceFiles = glob('app/Filament/Resources/*Resource.php');
$resources = [];

foreach ($resourceFiles as $file) {
    $className = basename($file, '.php');
    $fullClass = "App\\Filament\\Resources\\{$className}";
    if (class_exists($fullClass)) {
        $resources[$className] = $fullClass;
    }
}

echo "Found " . count($resources) . " resources\n\n";

foreach ($resources as $name => $class) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "TESTING: {$name}\n";
    echo str_repeat('=', 60) . "\n";

    try {
        // Get pages for this resource
        $pages = $class::getPages();

        foreach ($pages as $pageName => $pageClass) {
            echo "  Page: " . str_pad($pageName, 30, ' ');

            try {
                // Try to get widgets for this page
                if (method_exists($pageClass, 'getHeaderWidgets')) {
                    $reflection = new ReflectionClass($pageClass);
                    $method = $reflection->getMethod('getHeaderWidgets');
                    $method->setAccessible(true);

                    // Create a fake page instance
                    $pageInstance = new $pageClass();
                    $widgets = $method->invoke($pageInstance);

                    if (!empty($widgets)) {
                        echo "✅ OK (" . count($widgets) . " widgets)\n";

                        // Test each widget
                        foreach ($widgets as $widgetClass) {
                            if (is_string($widgetClass)) {
                                echo "    Widget: " . str_pad(class_basename($widgetClass), 25, ' ');

                                try {
                                    $widget = new $widgetClass();

                                    if (method_exists($widget, 'getStats')) {
                                        $reflection = new ReflectionClass($widget);
                                        $method = $reflection->getMethod('getStats');
                                        $method->setAccessible(true);
                                        $stats = $method->invoke($widget);
                                        echo "✅ OK\n";
                                    } elseif (method_exists($widget, 'getData')) {
                                        $reflection = new ReflectionClass($widget);
                                        $method = $reflection->getMethod('getData');
                                        $method->setAccessible(true);
                                        $data = $method->invoke($widget);
                                        echo "✅ OK\n";
                                    } else {
                                        echo "✅ OK (no testable methods)\n";
                                    }
                                } catch (\Illuminate\Database\QueryException $e) {
                                    $errorMsg = $e->getMessage();

                                    if (preg_match("/Table '.*?\\.(\\w+)' doesn't exist/", $errorMsg, $matches)) {
                                        echo "❌ MISSING TABLE: {$matches[1]}\n";
                                        $results['errors'][] = [
                                            'resource' => $name,
                                            'page' => $pageName,
                                            'widget' => class_basename($widgetClass),
                                            'type' => 'missing_table',
                                            'table' => $matches[1]
                                        ];
                                    } elseif (preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches)) {
                                        echo "❌ MISSING COLUMN: {$matches[1]}\n";
                                        $results['errors'][] = [
                                            'resource' => $name,
                                            'page' => $pageName,
                                            'widget' => class_basename($widgetClass),
                                            'type' => 'missing_column',
                                            'column' => $matches[1]
                                        ];
                                    } else {
                                        echo "❌ SQL ERROR\n";
                                        echo "       " . substr($errorMsg, 0, 80) . "\n";
                                        $results['errors'][] = [
                                            'resource' => $name,
                                            'page' => $pageName,
                                            'widget' => class_basename($widgetClass),
                                            'type' => 'sql_error',
                                            'error' => substr($errorMsg, 0, 200)
                                        ];
                                    }
                                } catch (\Exception $e) {
                                    echo "❌ ERROR: " . substr($e->getMessage(), 0, 50) . "\n";
                                    $results['errors'][] = [
                                        'resource' => $name,
                                        'page' => $pageName,
                                        'widget' => class_basename($widgetClass),
                                        'type' => 'php_error',
                                        'error' => $e->getMessage()
                                    ];
                                }
                            }
                        }
                    } else {
                        echo "✅ OK (no widgets)\n";
                    }
                } else {
                    echo "✅ OK (no widgets method)\n";
                }

                $results['ok'][] = "{$name}::{$pageName}";

            } catch (\Exception $e) {
                echo "❌ ERROR: " . substr($e->getMessage(), 0, 50) . "\n";
                $results['errors'][] = [
                    'resource' => $name,
                    'page' => $pageName,
                    'type' => 'page_error',
                    'error' => $e->getMessage()
                ];
            }
        }

    } catch (\Exception $e) {
        echo "  ❌ ERROR loading resource\n";
        echo "     " . substr($e->getMessage(), 0, 100) . "\n";
        $results['errors'][] = [
            'resource' => $name,
            'type' => 'resource_error',
            'error' => $e->getMessage()
        ];
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "=== SUMMARY ===\n";
echo str_repeat('=', 60) . "\n";
echo "✅ Working: " . count($results['ok']) . "\n";
echo "❌ Failed: " . count($results['errors']) . "\n\n";

if (!empty($results['errors'])) {
    echo "=== ERRORS FOUND ===\n";
    $grouped = [];
    foreach ($results['errors'] as $error) {
        $key = $error['resource'] . '::' . ($error['page'] ?? 'general');
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $error;
    }

    foreach ($grouped as $location => $errors) {
        echo "\n{$location}:\n";
        foreach ($errors as $error) {
            if (isset($error['widget'])) {
                echo "  • Widget {$error['widget']}: ";
            } else {
                echo "  • ";
            }

            if ($error['type'] === 'missing_column') {
                echo "Missing column '{$error['column']}'\n";
            } elseif ($error['type'] === 'missing_table') {
                echo "Missing table '{$error['table']}'\n";
            } else {
                echo "{$error['type']}: " . substr($error['error'] ?? '', 0, 80) . "\n";
            }
        }
    }
}

file_put_contents('resource_pages_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\n\nDetailed results: resource_pages_test_results.json\n";

exit(count($results['errors']) > 0 ? 1 : 0);
