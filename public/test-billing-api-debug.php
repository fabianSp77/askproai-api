<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get current session cookie
$cookies = $_COOKIE;
$sessionCookie = $cookies['portal_session'] ?? $cookies['askproai_portal_session'] ?? '';

// Test billing API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business/api/billing");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Cookie: portal_session=' . $sessionCookie
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Also check if user is authenticated
$user = \Illuminate\Support\Facades\Auth::guard('portal')->user();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Billing API Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Billing API Debug</h1>
    
    <h2>Session Info</h2>
    <pre><?php 
    echo "Session ID: " . session_id() . "\n";
    echo "Portal Session Cookie: " . substr($sessionCookie, 0, 50) . "...\n";
    echo "Authenticated User: " . ($user ? $user->email : 'NOT AUTHENTICATED') . "\n";
    if ($user) {
        echo "User ID: " . $user->id . "\n";
        echo "Company ID: " . $user->company_id . "\n";
    }
    ?></pre>
    
    <h2>API Response</h2>
    <p>HTTP Code: <span class="<?php echo $httpCode == 200 ? 'success' : 'error'; ?>"><?php echo $httpCode; ?></span></p>
    
    <?php if ($error): ?>
        <p class="error">CURL Error: <?php echo $error; ?></p>
    <?php endif; ?>
    
    <h3>Response Body:</h3>
    <pre><?php 
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo htmlspecialchars($response);
    }
    ?></pre>
    
    <h2>Direct Database Check</h2>
    <?php
    if ($user && $user->company_id) {
        $company = \App\Models\Company::find($user->company_id);
        if ($company) {
            echo "<pre>";
            echo "Company Name: " . $company->name . "\n";
            echo "Balance: " . ($company->balance ?? 0) . "\n";
            echo "Bonus Balance: " . ($company->bonus_balance ?? 0) . "\n";
            echo "Auto Topup Enabled: " . ($company->auto_topup_enabled ? 'Yes' : 'No') . "\n";
            echo "</pre>";
            
            // Check for recent topups
            $topupCount = \App\Models\BalanceTopup::where('company_id', $company->id)->count();
            echo "<p>Total Topups in Database: " . $topupCount . "</p>";
        }
    }
    ?>
    
    <h2>Test Links</h2>
    <ul>
        <li><a href="/business/dashboard">Dashboard (Working)</a></li>
        <li><a href="/business/billing">Billing Page</a></li>
        <li><a href="/business/api/billing" target="_blank">API Endpoint (JSON)</a></li>
    </ul>
</body>
</html>