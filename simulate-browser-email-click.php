<?php

echo "=== SIMULATING EXACT BROWSER EMAIL CLICK ===\n\n";

// 1. First, login to Business Portal to get session
echo "1. Logging in to Business Portal...\n";

$ch = curl_init();
$cookieFile = '/tmp/portal-browser-cookies.txt';

// Get login page for CSRF token
curl_setopt($ch, CURLOPT_URL, "https://askproai.de/business/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$loginPage = curl_exec($ch);

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? null;
echo "   CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . "..." : "NOT FOUND") . "\n";

// Login
curl_setopt($ch, CURLOPT_URL, "https://askproai.de/business/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'admin+1@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_HEADER, true);
$loginResponse = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "   Login Response: $loginCode\n";

// 2. Navigate to call page
echo "\n2. Navigating to call page...\n";
curl_setopt($ch, CURLOPT_URL, "https://askproai.de/business/calls/232/v2");
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);
$callPage = curl_exec($ch);

// Get new CSRF token from call page
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $callPage, $matches);
$newCsrfToken = $matches[1] ?? $csrfToken;
echo "   Page loaded: " . (strlen($callPage) > 0 ? "YES" : "NO") . "\n";
echo "   New CSRF Token: " . ($newCsrfToken ? substr($newCsrfToken, 0, 20) . "..." : "NOT FOUND") . "\n";

// 3. Send email via API (exactly like browser would)
echo "\n3. Sending email via API (browser simulation)...\n";

// Clear activities first
$callId = 232;
$pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
$pdo->exec("DELETE FROM call_activities WHERE call_id = $callId AND activity_type = 'email_sent' AND created_at > NOW() - INTERVAL 10 MINUTE");

// Make the API request
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business/api/calls/{$callId}/send-summary");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'recipients' => ['fabianspitzer@icloud.com'],
    'include_transcript' => true,
    'include_csv' => true,
    'message' => 'Browser simulation test - ' . date('H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $newCsrfToken,
    'X-Requested-With: XMLHttpRequest',
    'Origin: https://askproai.de',
    'Referer: https://askproai.de/business/calls/232/v2'
]);
curl_setopt($ch, CURLOPT_HEADER, true);

$apiResponse = curl_exec($ch);
$apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$apiHeaders = substr($apiResponse, 0, $header_size);
$apiBody = substr($apiResponse, $header_size);

echo "   API Status: $apiCode\n";
echo "   API Response: " . substr($apiBody, 0, 200) . "...\n";

// 4. Show important headers
echo "\n4. Response Headers:\n";
$headerLines = explode("\n", $apiHeaders);
foreach ($headerLines as $line) {
    if (stripos($line, 'set-cookie') !== false || 
        stripos($line, 'access-control') !== false) {
        echo "   $line\n";
    }
}

// 5. Check if email was queued
echo "\n5. Checking email queue...\n";
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$emailsInQueue = $redis->lLen('queues:emails');
echo "   Emails in queue: $emailsInQueue\n";

// 6. Check activities
$stmt = $pdo->query("SELECT * FROM call_activities WHERE call_id = $callId AND activity_type = 'email_sent' ORDER BY created_at DESC LIMIT 1");
$activity = $stmt->fetch(PDO::FETCH_ASSOC);
if ($activity) {
    echo "   Email activity created: YES\n";
    $metadata = json_decode($activity['metadata'], true);
    echo "   Recipients: " . implode(', ', $metadata['recipients'] ?? ['unknown']) . "\n";
} else {
    echo "   Email activity created: NO\n";
}

curl_close($ch);

echo "\n=== ANALYSIS ===\n";
if ($apiCode == 200 && $activity) {
    echo "✅ Email was sent successfully!\n";
    echo "Check https://resend.com/dashboard/emails\n";
} else {
    echo "❌ Email sending failed!\n";
    echo "Status code: $apiCode\n";
    if ($apiCode == 401) {
        echo "Authentication lost between domains!\n";
    } elseif ($apiCode == 403) {
        echo "CSRF token or permission issue!\n";
    } elseif ($apiCode == 419) {
        echo "Session expired or CSRF mismatch!\n";
    }
}