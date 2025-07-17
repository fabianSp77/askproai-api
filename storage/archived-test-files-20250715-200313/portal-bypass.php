<?php
session_start();

// Direct database connection
$db = new mysqli("127.0.0.1", "askproai_user", "lkZ57Dju9EDjrMxn", "askproai_db");

// Force set session
$_SESSION["portal_user_id"] = 10;
$_SESSION["is_portal_authenticated"] = true;
$_SESSION["portal_company_id"] = 1;

// Also set Laravel session directly
$sessionId = session_id();
$sessionData = serialize([
    "_token" => bin2hex(random_bytes(16)),
    "portal_user_id" => 10,
    "url" => [],
    "_previous" => ["url" => "https://api.askproai.de/business/dashboard"],
    "_flash" => ["old" => [], "new" => []],
    "PHPDEBUGBAR_STACK_DATA" => []
]);

// Update Laravel session in database
$stmt = $db->prepare("REPLACE INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, ?, ?, ?, ?, ?)");
$userId = "portal_10";
$ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
$userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "Mozilla/5.0";
$payload = base64_encode($sessionData);
$lastActivity = time();

$stmt->bind_param("sssssi", $sessionId, $userId, $ip, $userAgent, $payload, $lastActivity);
$stmt->execute();

// Set cookies
setcookie("askproai_session", $sessionId, time() + 3600, "/", ".askproai.de", true, true);
setcookie("XSRF-TOKEN", bin2hex(random_bytes(16)), time() + 3600, "/", ".askproai.de", true, false);

echo "<h2>Session Set!</h2>";
echo "<p>Redirecting to dashboard in 2 seconds...</p>";
echo "<script>setTimeout(function() { window.location.href = \"/business/dashboard\"; }, 2000);</script>";
echo "<p>Or click here: <a href=\"/business/dashboard\">Go to Dashboard</a></p>";
