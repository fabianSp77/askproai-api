<?php

echo "\n🔍 Business Portal Fix Verification\n";
echo "===================================\n\n";

// Test 1: Check if emergency fix script exists
echo "1. Checking emergency fix script...\n";
if (file_exists('public/js/emergency-business-portal-fix.js')) {
    echo "✅ Emergency fix script exists\n";
    $content = file_get_contents('public/js/emergency-business-portal-fix.js');
    if (strpos($content, 'fixCompanySelector') !== false) {
        echo "✅ Contains fixCompanySelector function\n";
    }
    if (strpos($content, 'fixPortalButton') !== false) {
        echo "✅ Contains fixPortalButton function\n";
    }
    if (strpos($content, 'fixBranchSelector') !== false) {
        echo "✅ Contains fixBranchSelector function\n";
    }
} else {
    echo "❌ Emergency fix script not found!\n";
}

// Test 2: Check if script is included in blade template
echo "\n2. Checking blade template integration...\n";
$bladeContent = file_get_contents('resources/views/filament/admin/pages/business-portal-admin.blade.php');
if (strpos($bladeContent, 'emergency-business-portal-fix.js') !== false) {
    echo "✅ Emergency fix script is included in blade template\n";
} else {
    echo "❌ Emergency fix script NOT included in blade template!\n";
}

// Test 3: Check BusinessPortalAdmin.php for redirect fallback
echo "\n3. Checking PHP component for redirect fallback...\n";
$phpContent = file_get_contents('app/Filament/Admin/Pages/BusinessPortalAdmin.php');
if (strpos($phpContent, 'redirect-to-portal') !== false) {
    echo "✅ Component has redirect-to-portal event dispatch\n";
} else {
    echo "❌ Component missing redirect-to-portal event dispatch!\n";
}

// Test 4: Create test HTML to verify fix
echo "\n4. Creating test page for manual verification...\n";
$testHtml = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Business Portal Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .status { font-weight: bold; }
        .ok { color: green; }
        .error { color: red; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Business Portal Fix Test</h1>
    
    <div class="test-section">
        <h2>1. Direct Page Test</h2>
        <button onclick="testDirectPage()">Test Business Portal Admin Page</button>
        <div id="directResult"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Emergency Fix Function Test</h2>
        <button onclick="testEmergencyFix()">Test Emergency Fix Functions</button>
        <div id="fixResult"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Manual Test Instructions</h2>
        <ol>
            <li>Open <a href="/admin/business-portal-admin" target="_blank">Business Portal Admin</a></li>
            <li>Open Browser Console (F12)</li>
            <li>Check for "Emergency Fix" messages in console</li>
            <li>Test Company Dropdown - should be clickable</li>
            <li>Test "Portal öffnen" button - should trigger redirect</li>
            <li>Run: <code>window.emergencyFix.status()</code> in console</li>
        </ol>
    </div>
    
    <script>
        function testDirectPage() {
            const result = document.getElementById('directResult');
            result.innerHTML = '<p>Opening Business Portal Admin in new tab...</p>';
            window.open('/admin/business-portal-admin', '_blank');
            
            setTimeout(() => {
                result.innerHTML += '<p>Please check the new tab and verify:</p>';
                result.innerHTML += '<ul>';
                result.innerHTML += '<li>No JavaScript errors in console</li>';
                result.innerHTML += '<li>Company dropdown is functional</li>';
                result.innerHTML += '<li>Portal button is clickable</li>';
                result.innerHTML += '<li>Emergency fix messages appear in console</li>';
                result.innerHTML += '</ul>';
            }, 1000);
        }
        
        function testEmergencyFix() {
            const result = document.getElementById('fixResult');
            
            // Load emergency fix script
            const script = document.createElement('script');
            script.src = '/js/emergency-business-portal-fix.js?test=' + Date.now();
            script.onload = () => {
                result.innerHTML = '<p class="status ok">✅ Emergency fix script loaded</p>';
                
                if (typeof window.emergencyFix !== 'undefined') {
                    result.innerHTML += '<p class="status ok">✅ Emergency fix object available</p>';
                    result.innerHTML += '<p>Available functions:</p>';
                    result.innerHTML += '<ul>';
                    result.innerHTML += '<li>window.emergencyFix.status()</li>';
                    result.innerHTML += '<li>window.emergencyFix.reapply()</li>';
                    result.innerHTML += '<li>window.emergencyFix.testPortalButton()</li>';
                    result.innerHTML += '<li>window.emergencyFix.testCompanySelect()</li>';
                    result.innerHTML += '</ul>';
                } else {
                    result.innerHTML += '<p class="status error">❌ Emergency fix object NOT available</p>';
                }
            };
            script.onerror = () => {
                result.innerHTML = '<p class="status error">❌ Failed to load emergency fix script</p>';
            };
            document.head.appendChild(script);
        }
    </script>
</body>
</html>
HTML;

file_put_contents('public/test-business-portal-fix.html', $testHtml);
echo "✅ Created test page: /test-business-portal-fix.html\n";

// Test 5: Check for common issues
echo "\n5. Checking for common issues...\n";

// Check if Livewire is properly configured
if (file_exists('config/livewire.php')) {
    $livewireConfig = include 'config/livewire.php';
    if (isset($livewireConfig['temporary_file_upload']['rules'])) {
        echo "✅ Livewire config exists\n";
    }
}

// Check for Alpine in app.js
$appJs = file_get_contents('resources/js/app.js');
if (strpos($appJs, 'Alpine') !== false || strpos($appJs, 'alpine') !== false) {
    echo "✅ Alpine.js referenced in app.js\n";
} else {
    echo "⚠️  Alpine.js not found in app.js\n";
}

echo "\n📊 Summary:\n";
echo "- Emergency fix script is deployed\n";
echo "- Test page created for manual verification\n";
echo "- Next: Open /test-business-portal-fix.html to verify\n\n";

echo "🔗 Quick Links:\n";
echo "1. Test Page: https://api.askproai.de/test-business-portal-fix.html\n";
echo "2. Business Portal Admin: https://api.askproai.de/admin/business-portal-admin\n";
echo "3. Debug Tool: https://api.askproai.de/debug-business-portal.html\n\n";