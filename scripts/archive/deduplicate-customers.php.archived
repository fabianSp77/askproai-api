#!/usr/bin/env php
<?php

// Deduplicate Customer Email Records
// Keeps the customer with the most data and updates references

$db = new PDO('mysql:host=localhost;dbname=askproai_db', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Find duplicate emails
$sql = "SELECT email, GROUP_CONCAT(id) as ids, COUNT(*) as cnt
        FROM customers
        WHERE email IS NOT NULL AND email != ''
        GROUP BY email
        HAVING cnt > 1";

$duplicates = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($duplicates) . " duplicate email addresses\n\n";

foreach ($duplicates as $dup) {
    echo "Processing email: {$dup['email']}\n";
    $ids = explode(',', $dup['ids']);

    // Get all customer records for this email
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT * FROM customers WHERE id IN ($placeholders)";
    $stmt = $db->prepare($sql);
    $stmt->execute($ids);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Find the best record to keep (most complete data)
    $bestScore = -1;
    $keepId = null;

    foreach ($customers as $customer) {
        $score = 0;
        // Score based on data completeness
        if (!empty($customer['name'])) $score += 2;
        if (!empty($customer['phone'])) $score += 2;
        if (!empty($customer['company_id'])) $score += 1;
        if (!empty($customer['notes'])) $score += 1;
        if ($customer['is_vip']) $score += 3;
        if ($customer['status'] == 'active') $score += 2;
        if (!empty($customer['total_revenue']) && $customer['total_revenue'] > 0) $score += 3;

        // Prefer older records (likely original)
        $score += (1 / (strtotime($customer['created_at']) ?: 1)) * 1000000;

        if ($score > $bestScore) {
            $bestScore = $score;
            $keepId = $customer['id'];
        }
    }

    echo "  Keeping customer ID: $keepId\n";

    // Merge data from other records into the keeper
    $mergeIds = array_values(array_diff($ids, [$keepId]));

    if (count($mergeIds) > 0) {
        // Update references in related tables
        $tables = [
            'calls' => 'customer_id',
            'appointments' => 'customer_id',
            'invoices' => 'customer_id',
        ];

        foreach ($tables as $table => $column) {
            $placeholders = str_repeat('?,', count($mergeIds) - 1) . '?';
            $sql = "UPDATE $table SET $column = ? WHERE $column IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $params = array_merge([$keepId], $mergeIds);
            $updated = $stmt->execute($params);
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "    Updated $count records in $table\n";
            }
        }

        // Merge notes and update total_revenue
        if (count($mergeIds) > 0) {
            $placeholders = str_repeat('?,', count($mergeIds) - 1) . '?';
            $sql = "SELECT SUM(total_revenue) as revenue, GROUP_CONCAT(notes SEPARATOR '\n---\n') as all_notes
                    FROM customers WHERE id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute($mergeIds);
            $mergeData = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $mergeData = ['revenue' => 0, 'all_notes' => ''];
        }

        if (($mergeData['revenue'] > 0) || !empty(trim($mergeData['all_notes'] ?? ''))) {
            $sql = "UPDATE customers SET
                    total_revenue = IFNULL(total_revenue, 0) + ?,
                    notes = IF(? = '', notes, CONCAT(IFNULL(notes, ''), '\n---\nMerged from duplicates:\n', ?))
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $mergeData['revenue'] ?: 0,
                $mergeData['all_notes'] ?: '',
                $mergeData['all_notes'] ?: '',
                $keepId
            ]);
        }

        // Delete duplicate records
        $placeholders = str_repeat('?,', count($mergeIds) - 1) . '?';
        $sql = "DELETE FROM customers WHERE id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($mergeIds);
        echo "    Deleted " . count($mergeIds) . " duplicate records\n";
    }

    echo "\n";
}

// Verify no duplicates remain
$sql = "SELECT COUNT(*) FROM (SELECT email, COUNT(*) as cnt FROM customers WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1) as t";
$remaining = $db->query($sql)->fetchColumn();

if ($remaining == 0) {
    echo "✓ Successfully deduplicated all customer records!\n";
} else {
    echo "⚠ Warning: $remaining duplicate emails still remain\n";
}

echo "\nDeduplication complete!\n";