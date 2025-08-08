<?php

echo "=== ANALYZING LOGIN PAGE AND ALL LINKS ===\n\n";

// Get login page HTML
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Status: $httpCode\n\n";

// Check for redirects
if (preg_match('/Location: (.+)/', $headers, $matches)) {
    echo "❌ REDIRECT FOUND: " . trim($matches[1]) . "\n";
} else {
    echo "✅ No redirects\n";
}

// Parse HTML
$dom = new DOMDocument();
@$dom->loadHTML($body);
$xpath = new DOMXPath($dom);

// Find all forms
echo "\n=== FORMS ===\n";
$forms = $xpath->query('//form');
foreach ($forms as $index => $form) {
    echo "Form #" . ($index + 1) . ":\n";
    echo "  Action: " . $form->getAttribute('action') . "\n";
    echo "  Method: " . $form->getAttribute('method') . "\n";
    echo "  Wire:submit: " . $form->getAttribute('wire:submit') . "\n";
    
    // Find submit buttons
    $buttons = $xpath->query('.//button[@type="submit"]', $form);
    foreach ($buttons as $button) {
        echo "  Submit button: " . trim($button->textContent) . "\n";
    }
}

// Find all input fields
echo "\n=== INPUT FIELDS ===\n";
$inputs = $xpath->query('//input[@type!="hidden"]');
foreach ($inputs as $input) {
    echo "- " . $input->getAttribute('type') . ": " . $input->getAttribute('name') . "\n";
    echo "  ID: " . $input->getAttribute('id') . "\n";
    echo "  Wire:model: " . $input->getAttribute('wire:model') . "\n";
}

// Find all links
echo "\n=== ALL LINKS ===\n";
$links = $xpath->query('//a[@href]');
foreach ($links as $link) {
    $href = $link->getAttribute('href');
    $text = trim($link->textContent);
    echo "- \"$text\" -> $href\n";
}

// Check for JavaScript blocking
echo "\n=== JAVASCRIPT ANALYSIS ===\n";
if (strpos($body, 'event.preventDefault()') !== false) {
    echo "❌ Found event.preventDefault() in page\n";
}
if (strpos($body, 'pointer-events: none') !== false) {
    echo "❌ Found pointer-events: none in page\n";
}
if (strpos($body, 'return false') !== false) {
    echo "❌ Found return false in page\n";
}

// Check for Livewire
if (strpos($body, 'wire:submit') !== false) {
    echo "✅ Livewire form detected\n";
}

// Check for Alpine.js
if (strpos($body, 'x-data') !== false) {
    echo "✅ Alpine.js detected\n";
}

echo "\n=== CSS CLASSES ON BODY ===\n";
$bodyElements = $xpath->query('//body');
if ($bodyElements->length > 0) {
    echo $bodyElements[0]->getAttribute('class') . "\n";
}

// Save the HTML for inspection
file_put_contents('/var/www/api-gateway/login-page-source.html', $body);
echo "\n✅ Full HTML saved to login-page-source.html\n";
?>