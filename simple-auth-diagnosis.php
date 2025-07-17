#!/usr/bin/env php
<?php

echo "🔍 Simple Auth Diagnosis\n";
echo "========================\n\n";

// Check .env file
echo "1. Checking .env SESSION configuration:\n";
$envContent = file_get_contents(__DIR__ . '/.env');
preg_match_all('/SESSION_.*=.*/', $envContent, $matches);
foreach ($matches[0] as $match) {
    echo "   $match\n";
}
echo "\n";

// Check if session table exists
echo "2. Checking database connection:\n";
$dbConfig = [
    'host' => '127.0.0.1',
    'database' => 'askproai_db',
    'username' => 'askproai_user',
    'password' => 'lkZ57Dju9EDjrMxn'
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    echo "   ✅ Database connection successful\n";
    
    // Check sessions table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Sessions table exists with $count records\n";
    
    // Check recent sessions
    $stmt = $pdo->query("SELECT id, user_id, last_activity FROM sessions ORDER BY last_activity DESC LIMIT 5");
    echo "\n3. Recent sessions:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $age = time() - $row['last_activity'];
        echo "   - {$row['id']} (User: " . ($row['user_id'] ?: 'NULL') . ", Age: {$age}s)\n";
    }
    
} catch (PDOException $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Quick Fix Commands:\n";
echo "   # Fix SESSION_DOMAIN:\n";
echo "   sed -i 's/SESSION_DOMAIN=api.askproai.de/SESSION_DOMAIN=.askproai.de/' .env\n";
echo "\n";
echo "   # Clear caches:\n";
echo "   php artisan config:clear && php artisan cache:clear\n";
echo "\n";
echo "   # Restart services:\n";
echo "   sudo systemctl restart php8.3-fpm && sudo systemctl restart nginx\n";

echo "\n5. Test URLs:\n";
echo "   Admin: https://api.askproai.de/admin/login\n";
echo "   Business: https://api.askproai.de/business/login\n";
echo "   Debug: https://api.askproai.de/portal-auth-debug.html\n";

echo "\n✅ Diagnosis complete!\n";