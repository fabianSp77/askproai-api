#!/usr/bin/env php
<?php
/**
 * Real-time MCP Test Monitor
 * Watches for incoming Retell MCP requests
 */

echo "\n";
echo "================================================================================\n";
echo "                    🔍 MCP Test Monitor - LIVE\n";
echo "================================================================================\n";
echo "Watching for MCP activity...\n";
echo "Test number: +49 30 33081738\n";
echo "Press Ctrl+C to stop\n";
echo "================================================================================\n\n";

$logFile = '/var/www/api-gateway/storage/logs/laravel.log';
$lastPosition = filesize($logFile);

// Function to check recent database activity
function checkRecentActivity() {
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=askproai_db',
            'askproai_user',
            'lkZ57Dju9EDjrMxn'
        );
        
        // Check recent calls
        $stmt = $pdo->query("
            SELECT id, phone_number, status, created_at 
            FROM calls 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($calls) {
            echo "📞 Recent Calls:\n";
            foreach ($calls as $call) {
                echo "  - {$call['phone_number']} | Status: {$call['status']} | {$call['created_at']}\n";
            }
            echo "\n";
        }
        
        // Check recent appointments
        $stmt = $pdo->query("
            SELECT id, customer_id, service_id, datetime, created_at 
            FROM appointments 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($appointments) {
            echo "📅 Recent Appointments:\n";
            foreach ($appointments as $apt) {
                echo "  - Service: {$apt['service_id']} | Time: {$apt['datetime']} | Created: {$apt['created_at']}\n";
            }
            echo "\n";
        }
        
    } catch (Exception $e) {
        echo "DB Error: " . $e->getMessage() . "\n";
    }
}

// Main monitoring loop
while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastPosition) {
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastPosition);
        
        while (!feof($handle)) {
            $line = fgets($handle);
            
            // Filter for MCP related logs
            if (stripos($line, 'MCP') !== false || 
                stripos($line, 'Retell') !== false ||
                stripos($line, 'hair') !== false ||
                stripos($line, 'appointment') !== false ||
                stripos($line, 'service') !== false ||
                stripos($line, 'webhook') !== false) {
                
                // Parse timestamp if present
                if (preg_match('/\[([\d-]+ [\d:]+)\]/', $line, $matches)) {
                    echo "[" . date('H:i:s', strtotime($matches[1])) . "] ";
                }
                
                // Highlight important keywords
                $line = str_replace('MCP', '🔧 MCP', $line);
                $line = str_replace('Retell', '📞 Retell', $line);
                $line = str_replace('ERROR', '❌ ERROR', $line);
                $line = str_replace('SUCCESS', '✅ SUCCESS', $line);
                
                echo trim($line) . "\n";
            }
        }
        
        fclose($handle);
        $lastPosition = $currentSize;
    }
    
    // Check database every 10 seconds
    static $lastDBCheck = 0;
    if (time() - $lastDBCheck > 10) {
        checkRecentActivity();
        $lastDBCheck = time();
    }
    
    usleep(500000); // Sleep for 0.5 seconds
}