<?php

echo "RETELL ENV CONFIGURATION CHECK\n";
echo str_repeat('=', 50) . "\n\n";

// Read .env file directly
$envFile = file_get_contents('.env');
$lines = explode("\n", $envFile);

$retellVars = [];
foreach ($lines as $lineNum => $line) {
    if (strpos($line, 'RETELL') !== false && strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1] ?? '');
        
        if (!isset($retellVars[$key])) {
            $retellVars[$key] = [];
        }
        
        $retellVars[$key][] = [
            'line' => $lineNum + 1,
            'value' => $value
        ];
    }
}

echo "Found Retell variables:\n\n";

foreach ($retellVars as $key => $occurrences) {
    echo "$key:\n";
    foreach ($occurrences as $occ) {
        $displayValue = substr($occ['value'], 0, 20) . '...';
        echo "  Line {$occ['line']}: $displayValue\n";
        
        if (count($occurrences) > 1) {
            echo "  ⚠️  DUPLICATE ENTRY!\n";
        }
    }
    echo "\n";
}

// Check which one is actually loaded by env()
echo "\nActual values loaded by env():\n";
echo "RETELL_TOKEN: " . substr($_ENV['RETELL_TOKEN'] ?? getenv('RETELL_TOKEN'), 0, 20) . "...\n";
echo "DEFAULT_RETELL_API_KEY: " . substr($_ENV['DEFAULT_RETELL_API_KEY'] ?? getenv('DEFAULT_RETELL_API_KEY'), 0, 20) . "...\n";

echo "\n⚠️  PROBLEM FOUND:\n";
echo "There are duplicate DEFAULT_RETELL_API_KEY entries in .env\n";
echo "The second one (line 142) will override the first one (line 37)\n";
echo "\nRECOMMENDATION: Remove one of the duplicate entries\n";