<?php
// Simple session debug without Laravel bootstrap

session_name('askproai_portal_session');
session_set_cookie_params([
    'lifetime' => 120 * 60,
    'path' => '/',
    'domain' => null,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_save_path(__DIR__ . '/../storage/framework/sessions/portal');
session_start();

header('Content-Type: text/plain');

echo "=== SESSION DEBUG ===\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());

echo "\n\nSession Data:\n";
print_r($_SESSION);

echo "\n\nCookies:\n";
print_r($_COOKIE);

echo "\n\nHeaders Sent:\n";
print_r(headers_list());

// Test setting a value
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;
$_SESSION['last_access'] = date('Y-m-d H:i:s');

echo "\n\nUpdated Session Data:\n";
print_r($_SESSION);