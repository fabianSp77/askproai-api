<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST APPOINTMENT PAGE ===\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

// Test ListAppointments page
echo "Testing ListAppointments page widgets:\n\n";

$page = new \App\Filament\Resources\AppointmentResource\Pages\ListAppointments();

try {
    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderWidgets');
    $method->setAccessible(true);
    $widgets = $method->invoke($page);

    echo "Found " . count($widgets) . " widgets\n\n";

    foreach ($widgets as $widgetClass) {
        $widgetName = class_basename($widgetClass);
        echo str_pad($widgetName, 40, ' ');

        try {
            $widget = new $widgetClass();

            if (method_exists($widget, 'getStats')) {
                $reflection = new ReflectionClass($widget);
                $method = $reflection->getMethod('getStats');
                $method->setAccessible(true);
                $stats = $method->invoke($widget);
                echo "✅ OK (" . count($stats) . " stats)\n";
            } else {
                echo "✅ OK (no stats method)\n";
            }
        } catch (\Exception $e) {
            echo "❌ ERROR\n";
            echo "   " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n✅ ALL APPOINTMENT PAGE WIDGETS WORKING!\n";

} catch (\Exception $e) {
    echo "❌ ERROR loading page widgets\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
