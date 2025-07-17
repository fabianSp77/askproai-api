<?php
// Quick fix for appointment display issue
session_start();

// Ensure we're authenticated
if (!isset($_SESSION['admin_logged_in'])) {
    die('Not authenticated');
}

// Set company_id in session if not present
if (!isset($_SESSION['company_id'])) {
    $_SESSION['company_id'] = 1; // Default to company 1
}

// Clear any cached queries
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Redirect back to appointments
header('Location: /admin/appointments');
exit;