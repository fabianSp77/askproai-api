<?php

/**
 * COMPREHENSIVE TEST: Test ALL Resource List Pages and their Widgets
 * This catches widgets that are loaded on List pages (like AppointmentStats)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE LIST PAGES + WIDGETS TEST ===\n";
echo "Testing ALL Resource List pages and their widgets\n\n";

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
    echo str_pad($name, 45, ' ');

    try {
        // Try to find List page
        $listPageClass = null;
        $baseName = str_replace('Resource', '', $name);

        // Try different List page naming conventions
        $possibleClasses = [
            "App\\Filament\\Resources\\{$name}\\Pages\\List{$baseName}",
            "App\\Filament\\Resources\\{$name}\\Pages\\List{$baseName}s", // plural
            "App\\Filament\\Resources\\{$name}\\Pages\\Index",
        ];

        foreach ($possibleClasses as $tryClass) {
            if (class_exists($tryClass)) {
                $listPageClass = $tryClass;
                break;
            }
        }

        if (!$listPageClass) {
            // Try to find any List*.php file for this resource
            $listFiles = glob("app/Filament/Resources/{$name}/Pages/List*.php");
            if (!empty($listFiles)) {
                $fileName = basename($listFiles[0], '.php');
                $listPageClass = "App\\Filament\\Resources\\{$name}\\Pages\\{$fileName}";
            }
        }

        if (!$listPageClass || !class_exists($listPageClass)) {
            echo "✅ OK (no list page)\n";
            $results['ok'][] = $name;
            continue;
        }

        // Instantiate page and get widgets
        $page = new $listPageClass();

        if (method_exists($page, 'getHeaderWidgets')) {
            $reflection = new ReflectionClass($page);
            $method = $reflection->getMethod('getHeaderWidgets');
            $method->setAccessible(true);
            $widgets = $method->invoke($page);

            if (empty($widgets)) {
                echo "✅ OK (0 widgets)\n";
                $results['ok'][] = $name;
                continue;
            }

            // Test each widget
            $widgetErrors = [];
            foreach ($widgets as $widgetClass) {
                try {
                    // Check canView() first (matches production Filament behavior)
                    if (method_exists($widgetClass, 'canView') && !$widgetClass::canView()) {
                        continue; // Skip disabled widgets
                    }

                    $widget = new $widgetClass();

                    if (method_exists($widget, 'getStats')) {
                        $reflection = new ReflectionClass($widget);
                        $method = $reflection->getMethod('getStats');
                        $method->setAccessible(true);
                        $stats = $method->invoke($widget);
                    } elseif (method_exists($widget, 'getData')) {
                        $reflection = new ReflectionClass($widget);
                        $method = $reflection->getMethod('getData');
                        $method->setAccessible(true);
                        $data = $method->invoke($widget);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    $errorMsg = $e->getMessage();
                    $widgetName = class_basename($widgetClass);

                    if (preg_match("/Table '.*?\\.(\\w+)' doesn't exist/", $errorMsg, $matches)) {
                        $widgetErrors[] = "{$widgetName}: missing table '{$matches[1]}'";
                        $results['errors'][] = [
                            'resource' => $name,
                            'widget' => $widgetName,
                            'type' => 'missing_table',
                            'table' => $matches[1]
                        ];
                    } elseif (preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches)) {
                        $widgetErrors[] = "{$widgetName}: missing column '{$matches[1]}'";
                        $results['errors'][] = [
                            'resource' => $name,
                            'widget' => $widgetName,
                            'type' => 'missing_column',
                            'column' => $matches[1]
                        ];
                    } else {
                        $widgetErrors[] = "{$widgetName}: SQL error";
                        $results['errors'][] = [
                            'resource' => $name,
                            'widget' => $widgetName,
                            'type' => 'sql_error',
                            'error' => substr($errorMsg, 0, 200)
                        ];
                    }
                } catch (\Exception $e) {
                    $widgetName = class_basename($widgetClass);
                    $widgetErrors[] = "{$widgetName}: " . $e->getMessage();
                    $results['errors'][] = [
                        'resource' => $name,
                        'widget' => $widgetName,
                        'type' => 'php_error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            if (empty($widgetErrors)) {
                echo "✅ OK (" . count($widgets) . " widgets)\n";
                $results['ok'][] = $name;
            } else {
                echo "❌ " . count($widgetErrors) . " ERRORS\n";
                foreach ($widgetErrors as $error) {
                    echo "   • {$error}\n";
                }
            }
        } else {
            echo "✅ OK (no widgets)\n";
            $results['ok'][] = $name;
        }

    } catch (\Exception $e) {
        echo "❌ ERROR: " . substr($e->getMessage(), 0, 50) . "\n";
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
echo "✅ Working: " . count($results['ok']) . "/" . count($resources) . "\n";
echo "❌ Failed: " . count($results['errors']) . "\n\n";

if (!empty($results['errors'])) {
    echo "=== ERRORS FOUND ===\n";
    foreach ($results['errors'] as $error) {
        $widget = isset($error['widget']) ? $error['widget'] : 'general';
        echo "• {$error['resource']}::{$widget}: ";
        if ($error['type'] === 'missing_column') {
            echo "Missing column '{$error['column']}'\n";
        } elseif ($error['type'] === 'missing_table') {
            echo "Missing table '{$error['table']}'\n";
        } else {
            echo "{$error['type']}\n";
        }
    }
}

file_put_contents('list_pages_widgets_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nDetailed results: list_pages_widgets_test_results.json\n";

exit(count($results['errors']) > 0 ? 1 : 0);
