<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "=== Checking Portal Demo User ===\n\n";

// Connect to database
$db = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');

// Check if demo user exists
$stmt = $db->prepare("SELECT * FROM portal_users WHERE email = 'demo@askproai.de'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ Demo user exists:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Name: " . $user['name'] . "\n";
    echo "   Company ID: " . $user['company_id'] . "\n";
    echo "   Is Active: " . $user['is_active'] . "\n";
    echo "   Created: " . $user['created_at'] . "\n";
} else {
    echo "❌ Demo user does not exist!\n\n";
    echo "Creating demo user...\n";
    
    // Get first company
    $stmt = $db->prepare("SELECT id, name FROM companies LIMIT 1");
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        echo "❌ No company exists! Cannot create user.\n";
        exit(1);
    }
    
    // Create user with bcrypt password
    $password = password_hash('password', PASSWORD_BCRYPT);
    $stmt = $db->prepare("
        INSERT INTO portal_users (name, email, password, company_id, is_active, created_at, updated_at)
        VALUES (:name, :email, :password, :company_id, 1, NOW(), NOW())
    ");
    
    $stmt->execute([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => $password,
        'company_id' => $company['id']
    ]);
    
    echo "✅ Demo user created:\n";
    echo "   ID: " . $db->lastInsertId() . "\n";
    echo "   Company: " . $company['name'] . " (ID: " . $company['id'] . ")\n";
}

echo "\n=== Portal Users Table ===\n";
$stmt = $db->prepare("SELECT id, name, email, company_id, is_active FROM portal_users ORDER BY id DESC LIMIT 5");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo sprintf("   [%d] %s (%s) - Company: %d, Active: %s\n", 
        $u['id'], 
        $u['name'], 
        $u['email'], 
        $u['company_id'],
        $u['is_active'] ? 'Yes' : 'No'
    );
}

echo "\nDone.\n";