#!/usr/bin/env php
<?php
/**
 * Real-time Retell MCP Test Monitor
 * Watches for incoming calls and MCP requests
 */

echo "\n";
echo "================================================================================\n";
echo "                    📞 RETELL MCP MONITOR - READY\n";
echo "================================================================================\n";
echo "Test Phone: +49 30 33081738\n";
echo "MCP Endpoint: https://api.askproai.de/api/v2/hair-salon-mcp\n";
echo "Monitoring: Laravel logs + Database activity\n";
echo "Press Ctrl+C to stop\n";
echo "================================================================================\n\n";

$logFile = '/var/www/api-gateway/storage/logs/laravel.log';
$lastPosition = filesize($logFile);
$lastCallCheck = 0;
$lastAppointmentCheck = 0;

// Function to check recent database activity
function checkDatabaseActivity() {
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
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($calls) {
            echo "📞 NEW CALLS DETECTED:\n";
            foreach ($calls as $call) {
                echo "  ✓ Phone: {$call['phone_number']}\n";
                echo "    Status: {$call['status']}\n";
                echo "    Time: {$call['created_at']}\n";
                echo "    ID: {$call['id']}\n\n";
            }
        }
        
        // Check recent appointments
        $stmt = $pdo->query("
            SELECT a.id, a.customer_id, a.service_id, a.starts_at, a.created_at,
                   c.name as customer_name, c.phone as customer_phone,
                   s.name as service_name
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ORDER BY a.created_at DESC 
            LIMIT 3
        ");
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($appointments) {
            echo "📅 NEW APPOINTMENTS BOOKED:\n";
            foreach ($appointments as $apt) {
                echo "  ✓ Customer: {$apt['customer_name']} ({$apt['customer_phone']})\n";
                echo "    Service: {$apt['service_name']}\n";
                echo "    Time: {$apt['starts_at']}\n";
                echo "    Created: {$apt['created_at']}\n\n";
            }
        }
        
        return ['calls' => count($calls), 'appointments' => count($appointments)];
        
    } catch (Exception $e) {
        echo "⚠️  DB Error: " . $e->getMessage() . "\n";
        return ['calls' => 0, 'appointments' => 0];
    }
}

// Function to parse and highlight log entries
function processLogLine($line) {
    // Skip empty lines
    if (trim($line) === '') return null;
    
    // Extract timestamp if present
    $timestamp = '';
    if (preg_match('/\[([\d-]+ [\d:]+)\]/', $line, $matches)) {
        $timestamp = '[' . date('H:i:s', strtotime($matches[1])) . '] ';
    }
    
    // Highlight MCP-related entries
    if (stripos($line, 'Retell MCP Request') !== false) {
        return $timestamp . "🔧 MCP REQUEST RECEIVED\n" . trim($line) . "\n";
    }
    
    if (stripos($line, 'MCP Bridge Error') !== false) {
        return $timestamp . "❌ MCP ERROR\n" . trim($line) . "\n";
    }
    
    if (stripos($line, 'list_services') !== false) {
        return $timestamp . "📋 Tool: list_services called\n";
    }
    
    if (stripos($line, 'check_availability') !== false) {
        return $timestamp . "🔍 Tool: check_availability called\n";
    }
    
    if (stripos($line, 'book_appointment') !== false) {
        return $timestamp . "✅ Tool: book_appointment called\n";
    }
    
    if (stripos($line, 'schedule_callback') !== false) {
        return $timestamp . "📞 Tool: schedule_callback called\n";
    }
    
    // Filter for relevant logs
    if (stripos($line, 'MCP') !== false || 
        stripos($line, 'Retell') !== false ||
        stripos($line, 'hair') !== false ||
        stripos($line, 'salon') !== false ||
        stripos($line, 'appointment') !== false ||
        stripos($line, 'webhook') !== false) {
        return $timestamp . trim($line) . "\n";
    }
    
    return null;
}

// Initial status
echo "🟢 MONITORING ACTIVE - Waiting for calls...\n\n";
$lastActivity = time();

// Main monitoring loop
while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    // Read new log entries
    if ($currentSize > $lastPosition) {
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastPosition);
        
        $newLogs = false;
        while (!feof($handle)) {
            $line = fgets($handle);
            $processed = processLogLine($line);
            if ($processed) {
                echo $processed;
                $newLogs = true;
                $lastActivity = time();
            }
        }
        
        fclose($handle);
        $lastPosition = $currentSize;
        
        if ($newLogs) {
            echo "---\n";
        }
    }
    
    // Check database every 5 seconds
    if (time() - $lastCallCheck > 5) {
        $activity = checkDatabaseActivity();
        if ($activity['calls'] > 0 || $activity['appointments'] > 0) {
            echo "================================================================================\n";
            $lastActivity = time();
        }
        $lastCallCheck = time();
    }
    
    // Show heartbeat every 30 seconds of inactivity
    if (time() - $lastActivity > 30) {
        echo "💚 Still monitoring... (" . date('H:i:s') . ")\n";
        $lastActivity = time();
    }
    
    usleep(250000); // Sleep for 0.25 seconds
}