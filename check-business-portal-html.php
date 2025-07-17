<?php
// Check what HTML is being generated for the business portal

// Simulate a portal user session
session_start();
$_SESSION['portal_guard'] = 'portal';

// Get the HTML output
$ch = curl_init('http://localhost/business');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

if ($httpCode == 200) {
    // Extract script tags
    preg_match_all('/<script[^>]*>.*?<\/script>/is', $html, $scripts);
    echo "Script tags found:\n";
    foreach ($scripts[0] as $i => $script) {
        echo "\n[$i] " . substr($script, 0, 200) . (strlen($script) > 200 ? '...' : '') . "\n";
    }
    
    // Extract link tags
    preg_match_all('/<link[^>]*>/i', $html, $links);
    echo "\n\nLink tags found:\n";
    foreach ($links[0] as $i => $link) {
        echo "[$i] $link\n";
    }
    
    // Check for app div
    if (strpos($html, 'id="app"') !== false) {
        echo "\n✅ App div found\n";
        preg_match('/<div[^>]*id="app"[^>]*>/', $html, $appDiv);
        if ($appDiv) {
            echo "App div: " . $appDiv[0] . "\n";
        }
    } else {
        echo "\n❌ App div NOT found\n";
    }
    
    // Check for Vite assets
    if (strpos($html, '/build/assets/') !== false) {
        echo "\n✅ Vite build assets found\n";
        preg_match_all('/\/build\/assets\/[^"\']+/', $html, $assets);
        echo "Assets:\n";
        foreach (array_unique($assets[0]) as $asset) {
            echo "- $asset\n";
        }
    } else {
        echo "\n❌ No Vite build assets found\n";
    }
} else {
    echo "Failed to fetch page. Response:\n";
    echo substr($html, 0, 1000);
}