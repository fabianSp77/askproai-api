<?php
// Business Portal Login Success Handler
session_start();

// Set long-lasting cookie
setcookie('portal_auth', 'active', time() + (86400 * 365), '/', '', true, true);

// Store in session
$_SESSION['portal_authenticated'] = true;
$_SESSION['portal_login_time'] = time();

// Redirect to React app
header('Location: /business');
exit;