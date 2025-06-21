#!/usr/bin/env php
<?php

// Direct database connection
$host = 'localhost';
$dbname = 'askproai_db';
$username = 'askproai_user';
$password = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!\n\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Query failed webhooks
$stmt = $pdo->query("SELECT * FROM webhook_logs WHERE provider = 'retell' AND status = 'error' ORDER BY created_at ASC");
$webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($webhooks) . " failed webhooks\n\n";

$processed = 0;
$errors = 0;

foreach ($webhooks as $webhook) {
    echo "Processing webhook ID: {$webhook['id']} from {$webhook['created_at']}\n";
    
    $payload = json_decode($webhook['payload'], true);
    
    if (!$payload || !isset($payload['call'])) {
        echo "  ❌ Invalid payload\n";
        $errors++;
        continue;
    }
    
    $callData = $payload['call'];
    $callId = $callData['call_id'] ?? null;
    
    if (!$callId) {
        echo "  ❌ No call_id\n";
        $errors++;
        continue;
    }
    
    // Check if call exists
    $checkStmt = $pdo->prepare("SELECT id FROM calls WHERE retell_call_id = ? OR call_id = ? LIMIT 1");
    $checkStmt->execute([$callId, $callId]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        echo "  ⚠️  Call already exists (ID: {$existing['id']})\n";
        continue;
    }
    
    // Get first company
    $companyStmt = $pdo->query("SELECT id, name FROM companies LIMIT 1");
    $company = $companyStmt->fetch();
    
    if (!$company) {
        echo "  ❌ No company found\n";
        $errors++;
        continue;
    }
    
    echo "  Using company: {$company['name']} (ID: {$company['id']})\n";
    
    // Insert call
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO calls (
                company_id, retell_call_id, call_id, agent_id, 
                from_number, to_number, direction, call_status,
                start_timestamp, end_timestamp, duration_sec,
                cost, transcript, summary, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, NOW(), NOW()
            )
        ");
        
        $startTime = isset($callData['start_timestamp']) 
            ? date('Y-m-d H:i:s', $callData['start_timestamp'] / 1000) 
            : date('Y-m-d H:i:s');
            
        $endTime = isset($callData['end_timestamp']) 
            ? date('Y-m-d H:i:s', $callData['end_timestamp'] / 1000) 
            : date('Y-m-d H:i:s');
            
        $duration = isset($callData['duration_ms']) 
            ? round($callData['duration_ms'] / 1000) 
            : 0;
            
        $cost = isset($callData['cost']) ? $callData['cost'] / 100 : 0;
        
        $insertStmt->execute([
            $company['id'],
            $callId,
            $callId,
            $callData['agent_id'] ?? null,
            $callData['from_number'] ?? null,
            $callData['to_number'] ?? null,
            $callData['call_type'] ?? 'inbound',
            $callData['call_status'] ?? 'completed',
            $startTime,
            $endTime,
            $duration,
            $cost,
            $callData['transcript'] ?? null,
            $callData['call_analysis']['call_summary'] ?? null
        ]);
        
        $callDbId = $pdo->lastInsertId();
        echo "  ✓ Call saved (ID: $callDbId)\n";
        
        // Extract data from call analysis
        if (isset($callData['call_analysis']['custom_analysis_data'])) {
            $customData = $callData['call_analysis']['custom_analysis_data'];
            
            $extractedData = [
                'name' => $customData['_name'] ?? null,
                'email' => $customData['_email'] ?? null,
                'date' => $customData['_datum__termin'] ?? null,
                'time' => $customData['_uhrzeit__termin'] ?? null
            ];
            
            if (array_filter($extractedData)) {
                $updateStmt = $pdo->prepare("
                    UPDATE calls 
                    SET extracted_name = ?, extracted_email = ?, 
                        extracted_date = ?, extracted_time = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $extractedData['name'],
                    $extractedData['email'],
                    $extractedData['date'],
                    $extractedData['time'],
                    $callDbId
                ]);
                echo "  ✓ Extracted data saved\n";
            }
        }
        
        // Update webhook log
        $updateWebhook = $pdo->prepare("
            UPDATE webhook_logs 
            SET status = 'success', updated_at = NOW() 
            WHERE id = ?
        ");
        $updateWebhook->execute([$webhook['id']]);
        
        $processed++;
        echo "  ✅ Successfully reprocessed!\n\n";
        
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Total webhooks: " . count($webhooks) . "\n";
echo "Successfully processed: $processed\n";
echo "Errors: $errors\n";

// Check total calls
$countStmt = $pdo->query("SELECT COUNT(*) as count FROM calls");
$count = $countStmt->fetch();
echo "\nTotal calls in database: {$count['count']}\n";