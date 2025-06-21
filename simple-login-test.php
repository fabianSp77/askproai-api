<?php
// Simple login test without Laravel bootstrapping issues

$email = 'fabian@askproai.de';
$password = 'Qwe421as1!1';

// Database connection
$db = new mysqli('127.0.0.1', 'root', 'V9LGz2tdR5gpDQz', 'askproai_db');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== Simple Login Test ===\n\n";

// Get user
$stmt = $db->prepare("SELECT user_id, email, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found!\n");
}

echo "User found: {$user['email']} (ID: {$user['user_id']})\n";
echo "Current hash: " . substr($user['password'], 0, 30) . "...\n";

// Verify password
if (password_verify($password, $user['password'])) {
    echo "✓ Password verification: PASSED\n";
} else {
    echo "✗ Password verification: FAILED\n";
    
    // Create new hash
    echo "\nCreating new password hash...\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->bind_param("si", $newHash, $user['user_id']);
    
    if ($updateStmt->execute()) {
        echo "✓ Password updated successfully\n";
        
        // Verify again
        if (password_verify($password, $newHash)) {
            echo "✓ New password verification: PASSED\n";
        } else {
            echo "✗ New password verification: FAILED\n";
        }
    } else {
        echo "✗ Failed to update password: " . $db->error . "\n";
    }
}

// Check hash length
echo "\nHash length: " . strlen($user['password']) . " characters\n";
echo "DB column max length: ";
$colResult = $db->query("SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'askproai_db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'");
$colInfo = $colResult->fetch_assoc();
echo $colInfo['CHARACTER_MAXIMUM_LENGTH'] . " characters\n";

$db->close();

echo "\nLogin credentials:\n";
echo "- Email: $email\n";
echo "- Password: $password\n";
echo "\nPlease try logging in at: https://api.askproai.de/admin/login\n";