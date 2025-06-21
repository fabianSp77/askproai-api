#!/usr/bin/env php
<?php

// Simple webhook processor that bypasses Laravel's complexity

$dbHost = 'localhost';
$dbName = 'askproai_db';
$dbUser = 'askproai_user';
$dbPass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Process a webhook payload directly
function processWebhook($pdo, $payload) {
    $data = json_decode($payload, true);
    if (!$data || !isset($data['call'])) {
        return ['success' => false, 'error' => 'Invalid payload'];
    }
    
    $callData = $data['call'];
    $callId = $callData['call_id'] ?? null;
    
    if (!$callId) {
        return ['success' => false, 'error' => 'No call_id'];
    }
    
    // Check if already processed
    $stmt = $pdo->prepare("SELECT id FROM calls WHERE retell_call_id = ?");
    $stmt->execute([$callId]);
    if ($stmt->fetch()) {
        return ['success' => true, 'message' => 'Already processed'];
    }
    
    $pdo->beginTransaction();
    
    try {
        // Get company ID (default to 85 - AskProAI)
        $companyId = 85;
        $branchId = null;
        
        // Try to find branch by phone number
        $toNumber = $callData['to_number'] ?? null;
        if ($toNumber) {
            $branchStmt = $pdo->prepare("SELECT id, company_id FROM branches WHERE phone_number = ? AND is_active = 1 LIMIT 1");
            $branchStmt->execute([$toNumber]);
            $branch = $branchStmt->fetch(PDO::FETCH_ASSOC);
            if ($branch) {
                $branchId = $branch['id'];  // This is a UUID
                $companyId = $branch['company_id'];
            }
        }
        
        // Create call record
        $callStmt = $pdo->prepare("
            INSERT INTO calls (
                company_id, branch_id, retell_call_id, call_id, agent_id,
                from_number, to_number, direction, call_status,
                start_timestamp, end_timestamp, duration_sec, cost,
                transcript, audio_url, public_log_url,
                extracted_name, extracted_email, extracted_date, extracted_time,
                summary, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, NOW(), NOW()
            )
        ");
        
        // Parse timestamps
        $startTime = isset($callData['start_timestamp']) 
            ? date('Y-m-d H:i:s', $callData['start_timestamp'] / 1000) 
            : null;
        $endTime = isset($callData['end_timestamp']) 
            ? date('Y-m-d H:i:s', $callData['end_timestamp'] / 1000) 
            : null;
        
        // Extract data
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? [];
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        
        $extractedName = $customData['_name'] ?? $dynamicVars['name'] ?? null;
        $extractedEmail = $customData['_email'] ?? $dynamicVars['email'] ?? null;
        $extractedDate = $customData['_datum__termin'] ?? $dynamicVars['datum'] ?? null;
        $extractedTime = $customData['_uhrzeit__termin'] ?? $dynamicVars['uhrzeit'] ?? null;
        
        $callStmt->execute([
            $companyId,
            $branchId,
            $callId,
            $callId,
            $callData['agent_id'] ?? null,
            $callData['from_number'] ?? null,
            $toNumber,
            $callData['call_type'] ?? 'inbound',
            $callData['call_status'] ?? 'completed',
            $startTime,
            $endTime,
            isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0,
            isset($callData['cost']) ? $callData['cost'] / 100 : 0,
            $callData['transcript'] ?? null,
            $callData['recording_url'] ?? null,
            $callData['public_log_url'] ?? null,
            $extractedName,
            $extractedEmail,
            $extractedDate,
            $extractedTime,
            $callData['call_analysis']['call_summary'] ?? null
        ]);
        
        $callDbId = $pdo->lastInsertId();
        
        // Create/find customer
        $customerId = null;
        if ($callData['from_number'] ?? null) {
            $custStmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND company_id = ?");
            $custStmt->execute([$callData['from_number'], $companyId]);
            $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                $custInsert = $pdo->prepare("
                    INSERT INTO customers (company_id, name, phone, email, created_via, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'phone_call', NOW(), NOW())
                ");
                $custInsert->execute([
                    $companyId,
                    $extractedName ?? 'Unknown Customer',
                    $callData['from_number'],
                    $extractedEmail
                ]);
                $customerId = $pdo->lastInsertId();
            } else {
                $customerId = $customer['id'];
            }
            
            // Update call with customer
            $updateCall = $pdo->prepare("UPDATE calls SET customer_id = ? WHERE id = ?");
            $updateCall->execute([$customerId, $callDbId]);
        }
        
        // Create appointment if booking confirmed
        if (!empty($dynamicVars['booking_confirmed']) && $extractedDate && $extractedTime) {
            // Parse date and time
            $date = $extractedDate;
            $time = $extractedTime;
            
            // Handle time format
            $timeParts = explode(':', $time);
            $hour = (int)($timeParts[0] ?? 0);
            $minute = (int)($timeParts[1] ?? 0);
            
            $startDateTime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $minute);
            $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime . ' +30 minutes'));
            
            $appStmt = $pdo->prepare("
                INSERT INTO appointments (
                    company_id, branch_id, customer_id, call_id,
                    starts_at, ends_at, status, notes, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, 'scheduled', ?, NOW(), NOW()
                )
            ");
            
            $notes = "Gebucht über Telefon-KI\n";
            $notes .= "Service: " . ($dynamicVars['dienstleistung'] ?? 'Nicht angegeben') . "\n";
            $notes .= "Wunsch: " . ($dynamicVars['kundenwunsch'] ?? '');
            
            $appStmt->execute([
                $companyId,
                $branchId,
                $customerId,
                $callDbId,
                $startDateTime,
                $endDateTime,
                $notes
            ]);
            
            $appointmentId = $pdo->lastInsertId();
            
            // Update call with appointment
            $updateCall = $pdo->prepare("UPDATE calls SET appointment_id = ? WHERE id = ?");
            $updateCall->execute([$appointmentId, $callDbId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'call_id' => $callDbId,
            'customer_id' => $customerId,
            'appointment_id' => $appointmentId ?? null
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Test with a sample webhook payload
$testPayload = '{
    "event": "call_ended",
    "call": {
        "call_id": "test-simple-' . time() . '",
        "agent_id": "agent_12345",
        "from_number": "+491601234567",
        "to_number": "+493083793369",
        "call_type": "inbound",
        "call_status": "completed",
        "start_timestamp": ' . ((time() - 120) * 1000) . ',
        "end_timestamp": ' . (time() * 1000) . ',
        "duration_ms": 120000,
        "cost": 250,
        "transcript": "Test call transcript from simple processor",
        "call_analysis": {
            "custom_analysis_data": {
                "_name": "Simple Test Customer",
                "_email": "simple@example.com",
                "_datum__termin": "2025-06-20",
                "_uhrzeit__termin": "16:00"
            },
            "call_summary": "Customer wants to book an appointment"
        },
        "retell_llm_dynamic_variables": {
            "name": "Simple Test Customer",
            "datum": "2025-06-20",
            "uhrzeit": "16:00",
            "booking_confirmed": true,
            "dienstleistung": "Beratung",
            "kundenwunsch": "Simple test booking"
        }
    }
}';

echo "Processing test webhook...\n";
$result = processWebhook($pdo, $testPayload);

if ($result['success']) {
    echo "✅ Webhook processed successfully!\n";
    echo "   Call ID: " . ($result['call_id'] ?? 'N/A') . "\n";
    echo "   Customer ID: " . ($result['customer_id'] ?? 'N/A') . "\n";
    echo "   Appointment ID: " . ($result['appointment_id'] ?? 'N/A') . "\n";
} else {
    echo "❌ Processing failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}

// Now process all failed webhooks from the logs
echo "\n\nProcessing failed webhooks from logs...\n";
echo str_repeat('-', 50) . "\n";

$stmt = $pdo->query("
    SELECT * FROM webhook_logs 
    WHERE provider = 'retell' 
    AND status = 'error' 
    ORDER BY created_at ASC
");
$failedWebhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processed = 0;
$failed = 0;

foreach ($failedWebhooks as $webhook) {
    $result = processWebhook($pdo, $webhook['payload']);
    
    if ($result['success']) {
        if ($result['message'] !== 'Already processed') {
            $processed++;
            echo "✅ Processed webhook ID {$webhook['id']}\n";
        }
        
        // Update webhook log
        $updateStmt = $pdo->prepare("UPDATE webhook_logs SET status = 'success' WHERE id = ?");
        $updateStmt->execute([$webhook['id']]);
    } else {
        $failed++;
        echo "❌ Failed webhook ID {$webhook['id']}: {$result['error']}\n";
    }
}

echo "\n\nSummary:\n";
echo "Processed: $processed\n";
echo "Failed: $failed\n";
echo "Total webhooks: " . count($failedWebhooks) . "\n";