#!/usr/bin/env php
<?php
/**
 * Monitor ALL incoming requests - not just MCP
 */

echo "\n";
echo "================================================================================\n";
echo "                    🔍 COMPREHENSIVE REQUEST MONITOR\n";
echo "================================================================================\n";
echo "Monitoring ALL incoming requests to find Retell activity\n";
echo "Press Ctrl+C to stop\n";
echo "================================================================================\n\n";

$logFile = '/var/www/api-gateway/storage/logs/laravel-2025-08-07.log';
$lastPosition = filesize($logFile);
$requestCount = 0;

// Patterns to highlight
$patterns = [
    'retell' => '📞 RETELL',
    'mcp' => '🔧 MCP',
    'webhook' => '🔔 WEBHOOK',
    'hair' => '💇 HAIR',
    'salon' => '💇 SALON',
    'appointment' => '📅 APPOINTMENT',
    'POST' => '📮 POST',
    'initialize' => '🚀 INITIALIZE',
    'list_services' => '📋 LIST_SERVICES',
    'check_availability' => '🔍 CHECK_AVAILABILITY',
    'book_appointment' => '✅ BOOK_APPOINTMENT'
];

function highlightLine($line) {
    global $patterns;
    
    $highlighted = $line;
    foreach ($patterns as $pattern => $emoji) {
        if (stripos($line, $pattern) !== false) {
            $highlighted = str_ireplace($pattern, $emoji . ' ' . strtoupper($pattern), $highlighted);
        }
    }
    return $highlighted;
}

echo "🟢 MONITORING STARTED at " . date('H:i:s') . "\n\n";

while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastPosition) {
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastPosition);
        
        while (!feof($handle)) {
            $line = fgets($handle);
            
            // Look for ANY HTTP request
            if (stripos($line, 'production.INFO') !== false && 
                (stripos($line, 'Request received') !== false || 
                 stripos($line, 'method":"POST') !== false ||
                 stripos($line, 'method":"GET') !== false)) {
                
                $requestCount++;
                
                // Extract timestamp
                if (preg_match('/\[([\d-]+ [\d:]+)\]/', $line, $matches)) {
                    echo "[" . date('H:i:s', strtotime($matches[1])) . "] ";
                }
                
                // Highlight and show
                echo "REQUEST #$requestCount:\n";
                echo highlightLine(substr($line, 0, 500)) . "\n";
                
                // Extract key info if it's JSON
                if (preg_match('/{.*}/', $line, $jsonMatch)) {
                    $data = @json_decode($jsonMatch[0], true);
                    if ($data) {
                        if (isset($data['path'])) {
                            echo "  📍 Path: " . $data['path'] . "\n";
                        }
                        if (isset($data['method'])) {
                            echo "  🔧 Method: " . $data['method'] . "\n";
                        }
                        if (isset($data['body']['method'])) {
                            echo "  🎯 MCP Method: " . $data['body']['method'] . "\n";
                        }
                        if (isset($data['headers']['user-agent'])) {
                            echo "  🤖 User-Agent: " . $data['headers']['user-agent'][0] . "\n";
                        }
                    }
                }
                echo "---\n";
            }
            
            // Also look for errors
            if (stripos($line, 'ERROR') !== false || stripos($line, 'Exception') !== false) {
                echo "❌ ERROR: " . substr($line, 0, 200) . "\n---\n";
            }
        }
        
        fclose($handle);
        $lastPosition = $currentSize;
    }
    
    usleep(100000); // 0.1 second
}