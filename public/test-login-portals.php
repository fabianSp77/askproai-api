<?php
// Test Login Script for AskProAI

echo "<h1>AskProAI Login Test</h1>";

// Admin Portal
echo "<h2>Admin Portal</h2>";
echo "<p>URL: <a href=\"/admin/login\">/admin/login</a></p>";
echo "<p>Use admin credentials from User model</p>";

// Business Portal  
echo "<h2>Business Portal</h2>";
echo "<p>URL: <a href=\"/business/login\">/business/login</a></p>";
echo "<p>Use portal user credentials from PortalUser model</p>";

// Clear Sessions
if (isset($_GET["clear"])) {
    setcookie("askproai_admin_session", "", time() - 3600, "/");
    setcookie("askproai_portal_session", "", time() - 3600, "/");
    setcookie("askproai_session", "", time() - 3600, "/");
    echo "<p style=\"color: green;\">✓ All cookies cleared!</p>";
}

echo "<hr>";
echo "<a href=\"?clear=1\">Clear All Cookies</a>";
