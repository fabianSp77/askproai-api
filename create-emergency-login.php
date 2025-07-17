<?php
// Erstelle einen Emergency Demo User für die Demo morgen

$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Erstelle oder update den Demo-User
    $email = 'emergency@demo.de';
    $password = 'Demo2025!';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prüfe ob User existiert
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        echo "✅ Emergency Demo User aktualisiert!\n";
    } else {
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW(), NOW())
        ");
        $stmt->execute(['Emergency Demo User', $email, $hashedPassword]);
        echo "✅ Emergency Demo User erstellt!\n";
    }
    
    echo "\n";
    echo "🔑 LOGIN-DATEN für Emergency Access:\n";
    echo "=====================================\n";
    echo "URL: https://api.askproai.de/admin-emergency-access.php\n";
    echo "Email: emergency@demo.de\n";
    echo "Password: Demo2025!\n";
    echo "=====================================\n";
    
    // Zeige auch andere verfügbare Logins
    echo "\n";
    echo "📋 Alternative Admin-Accounts (falls du das Passwort kennst):\n";
    echo "- admin@askproai.de\n";
    echo "- demo@askproai.de\n";
    echo "- superadmin@askproai.de\n";
    
} catch(PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}