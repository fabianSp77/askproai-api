<?php

$html = file_get_contents('/var/www/api-gateway/login-page-source.html');

// Look for wire:model attributes
echo "=== LIVEWIRE MODELS ===\n";
preg_match_all('/wire:model="([^"]+)"/', $html, $matches);
foreach ($matches[1] as $model) {
    echo "- $model\n";
}

// Look for x-bind:type (Alpine.js password reveal)
echo "\n=== ALPINE.JS BINDINGS ===\n";
preg_match_all('/x-bind:type="([^"]+)"/', $html, $matches);
foreach ($matches[1] as $binding) {
    echo "- x-bind:type=\"$binding\"\n";
}

// Look for any input with password in the attributes
echo "\n=== PASSWORD-RELATED INPUTS ===\n";
preg_match_all('/<input[^>]*password[^>]*>/i', $html, $matches);
foreach ($matches[0] as $input) {
    echo substr($input, 0, 200) . "...\n";
}

// Check form action
echo "\n=== FORM DETAILS ===\n";
preg_match('/<form[^>]*wire:submit[^>]*>/', $html, $formMatch);
if ($formMatch) {
    echo "Form found: " . $formMatch[0] . "\n";
}

// Look for hidden inputs
echo "\n=== HIDDEN INPUTS ===\n";
preg_match_all('/<input[^>]*type="hidden"[^>]*>/', $html, $matches);
foreach ($matches[0] as $hidden) {
    echo $hidden . "\n";
}

// Check for JavaScript that might be hiding the password field
echo "\n=== JAVASCRIPT THAT MIGHT AFFECT PASSWORD FIELD ===\n";
if (strpos($html, 'isPasswordRevealed') !== false) {
    echo "✅ Found password reveal toggle functionality\n";
}

// Check if there's any CSS hiding elements
echo "\n=== CSS VISIBILITY ISSUES ===\n";
if (strpos($html, 'display: none') !== false) {
    echo "⚠️  Found 'display: none' in page\n";
}
if (strpos($html, 'visibility: hidden') !== false) {
    echo "⚠️  Found 'visibility: hidden' in page\n";
}
if (strpos($html, 'opacity: 0') !== false) {
    echo "⚠️  Found 'opacity: 0' in page\n";
}

// Extract the actual password field using a more specific search
echo "\n=== SEARCHING FOR PASSWORD FIELD MORE SPECIFICALLY ===\n";
$lines = explode("\n", $html);
$inPasswordSection = false;
foreach ($lines as $i => $line) {
    if (strpos($line, 'data.password') !== false || strpos($line, 'current-password') !== false) {
        // Print 5 lines before and after
        for ($j = max(0, $i-5); $j <= min(count($lines)-1, $i+5); $j++) {
            echo ($j == $i ? ">>> " : "    ") . trim($lines[$j]) . "\n";
        }
        echo "---\n";
    }
}
?>