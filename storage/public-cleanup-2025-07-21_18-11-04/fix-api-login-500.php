<?php
/**
 * Fix API Login 500 Error
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== FIXING API LOGIN 500 ERROR ===\n\n";

// 1. Test API endpoint directly
echo "1. Testing API login endpoint...\n";

$testUser = \App\Models\PortalUser::first();
if (!$testUser) {
    echo "❌ No portal user found! Creating one...\n";
    $testUser = \App\Models\PortalUser::create([
        'name' => 'API Test User',
        'email' => 'api-test@portal.de',
        'password' => bcrypt('password'),
        'company_id' => 1,
        'is_active' => true
    ]);
    echo "✅ Created test user: {$testUser->email}\n";
}

// 2. Check if PortalController exists
echo "\n2. Checking PortalController...\n";
$controllerPath = app_path('Http/Controllers/Api/V2/PortalController.php');

if (!file_exists($controllerPath)) {
    echo "❌ PortalController missing at: {$controllerPath}\n";
} else {
    echo "✅ PortalController exists\n";
    
    // Check if it has proper namespace
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'namespace App\Http\Controllers\Api\V2;') === false) {
        echo "⚠️  Namespace might be incorrect\n";
    }
}

// 3. Check Sanctum configuration
echo "\n3. Checking Sanctum configuration...\n";

// Check if PortalUser has HasApiTokens trait
$portalUserPath = app_path('Models/PortalUser.php');
if (file_exists($portalUserPath)) {
    $content = file_get_contents($portalUserPath);
    
    if (strpos($content, 'use Laravel\Sanctum\HasApiTokens;') === false) {
        echo "❌ PortalUser missing HasApiTokens trait\n";
        
        // Add the trait
        $content = str_replace(
            "use Illuminate\Database\Eloquent\Model;",
            "use Illuminate\Database\Eloquent\Model;\nuse Laravel\Sanctum\HasApiTokens;",
            $content
        );
        
        $content = str_replace(
            "use HasFactory;",
            "use HasFactory, HasApiTokens;",
            $content
        );
        
        file_put_contents($portalUserPath, $content);
        echo "✅ Added HasApiTokens trait to PortalUser\n";
    } else {
        echo "✅ PortalUser has HasApiTokens trait\n";
    }
}

// 4. Check if personal_access_tokens table exists
echo "\n4. Checking personal_access_tokens table...\n";

if (!\Schema::hasTable('personal_access_tokens')) {
    echo "❌ personal_access_tokens table missing!\n";
    echo "   Running: php artisan migrate\n";
    \Artisan::call('migrate', ['--force' => true]);
    echo "✅ Migration completed\n";
} else {
    echo "✅ personal_access_tokens table exists\n";
}

// 5. Test the actual API call
echo "\n5. Testing API login call...\n";

try {
    // Simulate API request
    $request = \Illuminate\Http\Request::create('/api/v2/portal/auth/login', 'POST', [
        'email' => $testUser->email,
        'password' => 'password'
    ]);
    
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "Response Status: {$statusCode}\n";
    
    if ($statusCode === 500) {
        echo "❌ Still getting 500 error\n";
        
        // Try to decode error
        $json = json_decode($content, true);
        if (isset($json['message'])) {
            echo "Error: " . $json['message'] . "\n";
        }
        
        // Check logs
        $logFile = storage_path('logs/laravel.log');
        $logs = file_get_contents($logFile);
        
        // Get last error
        if (preg_match('/\[.*?\] production\.ERROR: (.*)/', $logs, $matches)) {
            echo "Last Error: " . substr($matches[1], 0, 200) . "...\n";
        }
    } else {
        echo "✅ API responding with status: {$statusCode}\n";
        
        $json = json_decode($content, true);
        if (isset($json['success'])) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . substr($e->getTraceAsString(), 0, 500) . "\n";
}

// 6. Clear caches
echo "\n6. Clearing caches...\n";
\Artisan::call('optimize:clear');
echo "✅ Caches cleared\n";

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "If still getting 500 error, check:\n";
echo "1. storage/logs/laravel.log for detailed error\n";
echo "2. Ensure PortalUser extends Authenticatable (not just Model)\n";
echo "3. Check if guard 'portal' is properly configured\n";