<?php
// Test appointments API
require_once __DIR__ . '/../vendor/autoload.php';

$ch = curl_init();

// Set the URL
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business/api/appointments?per_page=10");

// Set headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Cookie: portal_session=eyJpdiI6Ijk0Qlh0VXJsRXlEOVgrN0h5SEdLRFE9PSIsInZhbHVlIjoiUUJOT21LczMraVZBWHR5cXllM0ROZUJ1YXcxc1YzTENHMjZIb0FwNG96OGFvb0tCbTV6ZWdBTEFGb1JUMUQySGxncjU3TnlWbER5ZVZYQjgrNDMxK09jdlJaOGppYXRKNnI0aUdYQ0FwL005QmhnRTRBOGVPS0p5bVZhN2NxQXoiLCJtYWMiOiI2MDQ3YjJhYWYzMzRkNzQyMDY3N2U2YWFmYzAxMTA5NjU0NDRmNDFmNGI3YjIzMzBhMjQ2N2QzYmJjMmQwOWRkIiwidGFnIjoiIn0%3D'
]);

// Return response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Execute
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Display results
echo "HTTP Code: $httpCode\n\n";

if ($error) {
    echo "CURL Error: $error\n";
} else {
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Raw Response:\n$response\n";
    }
}