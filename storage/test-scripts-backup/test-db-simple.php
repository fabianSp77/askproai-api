<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force reload config
config()->set('database.connections.mysql.host', '127.0.0.1');
config()->set('database.connections.mysql.username', 'askproai_user');
config()->set('database.connections.mysql.password', 'lkZ57Dju9EDjrMxn');

try {
    // Test basic database connection
    $result = \DB::select('SELECT 1 as test');
    echo "Database connection successful\n";
    print_r($result);
    
    // Test Call model
    $callCount = \App\Models\Call::withoutGlobalScopes()->count();
    echo "Total calls in database: $callCount\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}