#!/usr/bin/env php
<?php

// Direct database test to verify webhook processing

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

// Create test call data
$callId = '550e8400-e29b-41d4-test-' . uniqid();
$now = date('Y-m-d H:i:s');

echo "Creating test call with ID: $callId\n";

// Insert call directly
$stmt = $pdo->prepare("
    INSERT INTO calls (
        company_id, retell_call_id, call_id, 
        from_number, to_number, direction, call_status,
        start_timestamp, end_timestamp, duration_sec,
        transcript, extracted_name, extracted_date, extracted_time,
        created_at, updated_at
    ) VALUES (
        85, ?, ?,
        '+491601234567', '+493083793369', 'inbound', 'completed',
        ?, ?, 120,
        'Test call for webhook verification', 'Max Mustermann', '2025-06-20', '14:00',
        ?, ?
    )
");

$startTime = date('Y-m-d H:i:s', strtotime('-2 minutes'));
$endTime = $now;

$stmt->execute([$callId, $callId, $startTime, $endTime, $now, $now]);

$callDbId = $pdo->lastInsertId();
echo "✅ Call created with ID: $callDbId\n";

// Create customer
$custStmt = $pdo->prepare("
    INSERT INTO customers (company_id, name, phone, email, created_via, created_at, updated_at)
    VALUES (85, 'Max Mustermann', '+491601234567', 'max@example.com', 'phone_call', ?, ?)
");
$custStmt->execute([$now, $now]);
$customerId = $pdo->lastInsertId();
echo "✅ Customer created with ID: $customerId\n";

// Update call with customer
$updateStmt = $pdo->prepare("UPDATE calls SET customer_id = ? WHERE id = ?");
$updateStmt->execute([$customerId, $callDbId]);

// Create appointment
$appStmt = $pdo->prepare("
    INSERT INTO appointments (
        company_id, customer_id, starts_at, ends_at, 
        status, notes, call_id, created_at, updated_at
    ) VALUES (
        85, ?, '2025-06-20 14:00:00', '2025-06-20 14:30:00',
        'scheduled', 'Test appointment from webhook', ?, ?, ?
    )
");
$appStmt->execute([$customerId, $callDbId, $now, $now]);
$appointmentId = $pdo->lastInsertId();
echo "✅ Appointment created with ID: $appointmentId\n";

// Update call with appointment
$updateStmt = $pdo->prepare("UPDATE calls SET appointment_id = ? WHERE id = ?");
$updateStmt->execute([$appointmentId, $callDbId]);

echo "\n✅ Test data created successfully!\n";
echo "Call ID: $callDbId\n";
echo "Customer ID: $customerId\n";
echo "Appointment ID: $appointmentId\n";

// Verify
$checkStmt = $pdo->query("
    SELECT c.id, c.retell_call_id, c.extracted_name, c.extracted_date, c.extracted_time,
           a.id as appointment_id, a.starts_at
    FROM calls c
    LEFT JOIN appointments a ON a.call_id = c.id
    WHERE c.id = $callDbId
");
$result = $checkStmt->fetch(PDO::FETCH_ASSOC);

echo "\nVerification:\n";
print_r($result);