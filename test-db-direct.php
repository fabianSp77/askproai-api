<?php
// Load Laravel bootstrap
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test database connection
echo "Testing database connection...\n";
echo "DB_HOST: " . env('DB_HOST') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME') . "\n";
echo "DB_PASSWORD: " . (env('DB_PASSWORD') ? '***' : 'EMPTY') . "\n\n";

try {
    // Test with PDO directly
    $pdo = new PDO(
        'mysql:host=localhost;dbname=askproai_db',
        'askproai_user',
        'lkZ57Dju9EDjrMxn'
    );
    echo "✅ Direct PDO connection successful\n";
    
    // Test Laravel DB connection
    DB::connection()->getPdo();
    echo "✅ Laravel DB connection successful\n";
    
    // Test a simple query
    $result = DB::select('SELECT 1 as test');
    echo "✅ Query test passed\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}