<?php
// Direct login without Laravel complexity
$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Set session cookie
    session_start();
    $_SESSION['portal_authenticated'] = true;
    $_SESSION['portal_user_id'] = 41; // Demo user ID
    $_SESSION['portal_user'] = [
        'id' => 41,
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'company_id' => 1
    ];
    
    // Redirect to React app
    header('Location: /business');
    exit;
    
} catch (Exception $e) {
    die('Error: Unable to connect');
}
