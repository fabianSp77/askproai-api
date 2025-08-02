<?php

// Test the business portal API directly

session_start();

// Simulate a logged-in portal user
$_SESSION['login_portal_'.sha1('App\Models\PortalUser')] = 41; // demo@askproai.de user ID

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.askproai.de/business/api/dashboard');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Cookie: askproai_portal_session=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "\nHeaders:\n$headers\n";
echo "\nBody:\n$body\n";

curl_close($ch);