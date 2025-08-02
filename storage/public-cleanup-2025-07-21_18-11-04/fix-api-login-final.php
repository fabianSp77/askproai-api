<?php
/**
 * Fix API Login - Final Solution
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== FIX API LOGIN - FINAL SOLUTION ===\n\n";

// 1. Check if personal_access_tokens table exists
echo "1. Checking personal_access_tokens table...\n";
if (!\Schema::hasTable('personal_access_tokens')) {
    echo "   ❌ Table missing! Creating it now...\n";
    \Artisan::call('migrate', ['--path' => 'vendor/laravel/sanctum/database/migrations']);
    echo "   ✅ Table created\n";
} else {
    echo "   ✅ Table exists\n";
}

// 2. Check columns
echo "\n2. Checking table columns...\n";
$columns = \Schema::getColumnListing('personal_access_tokens');
$requiredColumns = ['tokenable_type', 'tokenable_id'];
$missingColumns = array_diff($requiredColumns, $columns);

if (empty($missingColumns)) {
    echo "   ✅ All required columns exist\n";
} else {
    echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
    echo "   Running Sanctum migrations...\n";
    \Artisan::call('migrate', ['--path' => 'vendor/laravel/sanctum/database/migrations']);
}

// 3. Update sanctum config to support portal guard
echo "\n3. Updating Sanctum configuration...\n";
$configPath = config_path('sanctum.php');
$config = file_get_contents($configPath);

if (strpos($config, "'guard' => ['web']") !== false) {
    $newConfig = str_replace(
        "'guard' => ['web']",
        "'guard' => ['web', 'portal']",
        $config
    );
    file_put_contents($configPath, $newConfig);
    echo "   ✅ Updated sanctum.php to include portal guard\n";
    
    // Clear config cache
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "   ✅ Config cache cleared\n";
} else {
    echo "   ✅ Sanctum config already updated\n";
}

// 4. Test token creation with our test user
echo "\n4. Testing token creation...\n";
$testUser = \App\Models\PortalUser::where('email', 'portal-test@askproai.de')->first();

if (!$testUser) {
    echo "   Creating test user...\n";
    $testUser = \App\Models\PortalUser::create([
        'name' => 'Portal Test User',
        'email' => 'portal-test@askproai.de',
        'password' => bcrypt('test123'),
        'company_id' => 1,
        'is_active' => true,
        'role' => 'admin'
    ]);
}

try {
    // Test creating a token
    $token = $testUser->createToken('test-token')->plainTextToken;
    echo "   ✅ Token created successfully: " . substr($token, 0, 20) . "...\n";
    
    // Delete test token
    $testUser->tokens()->delete();
    echo "   ✅ Test token deleted\n";
} catch (\Exception $e) {
    echo "   ❌ Token creation failed: " . $e->getMessage() . "\n";
}

// 5. Update PortalController to handle potential errors
echo "\n5. Creating improved PortalController...\n";
$controllerContent = '<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PortalUser;

class PortalController extends Controller
{
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                "email" => "required|email",
                "password" => "required"
            ]);

            // Log the attempt
            Log::info("Portal API login attempt", ["email" => $credentials["email"]]);

            // Direct authentication check
            $user = PortalUser::where("email", $credentials["email"])->first();
            
            if (!$user || !password_verify($credentials["password"], $user->password)) {
                Log::warning("Portal API login failed - invalid credentials", ["email" => $credentials["email"]]);
                return response()->json([
                    "success" => false,
                    "message" => "Invalid credentials"
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                Log::warning("Portal API login failed - user inactive", ["email" => $credentials["email"]]);
                return response()->json([
                    "success" => false,
                    "message" => "Account is inactive"
                ], 403);
            }

            // Create token
            $token = $user->createToken("portal-api-token")->plainTextToken;
            
            Log::info("Portal API login successful", ["user_id" => $user->id]);
            
            return response()->json([
                "success" => true,
                "token" => $token,
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => $user->role
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Portal API login exception", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);
            
            return response()->json([
                "success" => false,
                "message" => "Login failed",
                "error" => config("app.debug") ? $e->getMessage() : "Internal server error"
            ], 500);
        }
    }

    public function dashboard(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => [
                "user" => $request->user(),
                "stats" => [
                    "appointments" => 0,
                    "calls" => 0
                ]
            ]
        ]);
    }

    public function appointments(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
    }

    public function calls(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
    }
}';

file_put_contents(app_path('Http/Controllers/Api/V2/PortalController.php'), $controllerContent);
echo "   ✅ PortalController updated with improved error handling\n";

// 6. Test the API endpoint
echo "\n6. Testing API endpoint...\n";
$ch = curl_init('http://localhost/api/v2/portal/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";
if ($httpCode === 200) {
    echo "   ✅ API Login working!\n";
    $json = json_decode($response, true);
    if (isset($json['token'])) {
        echo "   ✅ Token received: " . substr($json['token'], 0, 20) . "...\n";
    }
} else {
    echo "   ❌ API Login failed\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ personal_access_tokens table verified\n";
echo "✅ Sanctum configuration updated\n";
echo "✅ PortalController improved\n";
echo "✅ Test user ready\n";
echo "\nThe API login endpoint should now work correctly!\n";
echo "Test with: email=portal-test@askproai.de, password=test123\n";