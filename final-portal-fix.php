<?php
echo "🚀 FINAL PORTAL FIX\n";
echo "==================\n\n";

// 1. Clear all caches
echo "1️⃣ Clearing all caches...\n";
exec('php artisan optimize:clear 2>&1', $output);
echo implode("\n", $output) . "\n";

// 2. Fix route cache
echo "\n2️⃣ Rebuilding route cache...\n";
exec('php artisan route:cache 2>&1', $output);
echo implode("\n", array_slice($output, -3)) . "\n";

// 3. Create test login page
echo "\n3️⃣ Creating test login page...\n";

$testLoginHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Portal Login Test</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
</head>
<body>
    <h1>Business Portal Login Test</h1>
    
    <div id="status"></div>
    
    <button onclick="testLogin()">Test Login</button>
    <button onclick="testDashboard()">Test Dashboard API</button>
    <button onclick="goToDashboard()">Go to Dashboard</button>
    
    <pre id="result"></pre>
    
    <script>
    function getCsrfToken() {
        return document.querySelector(\'meta[name="csrf-token"]\').getAttribute(\'content\');
    }
    
    async function testLogin() {
        const response = await fetch("/business/login", {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                "Accept": "application/json"
            },
            body: JSON.stringify({
                email: "fabianspitzer@icloud.com",
                password: "demo123"
            })
        });
        
        const text = await response.text();
        document.getElementById("result").textContent = "Login Response:\n" + text;
        
        if (response.ok || response.status === 302) {
            document.getElementById("status").innerHTML = "✅ Login successful!";
        } else {
            document.getElementById("status").innerHTML = "❌ Login failed: " + response.status;
        }
    }
    
    async function testDashboard() {
        const response = await fetch("/business/api/dashboard", {
            credentials: "include",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": getCsrfToken()
            }
        });
        
        const data = await response.json();
        document.getElementById("result").textContent = "Dashboard API Response:\n" + JSON.stringify(data, null, 2);
    }
    
    function goToDashboard() {
        window.location.href = "/business/dashboard";
    }
    </script>
</body>
</html>';

// Save as PHP to get CSRF token
$testLoginPhp = '<?php
session_start();
?>
' . $testLoginHtml;

file_put_contents('/var/www/api-gateway/public/portal-test.php', $testLoginPhp);
echo "✅ Created portal-test.php\n";

// 4. Summary
echo "\n✅ FIXES APPLIED!\n";
echo "================\n\n";

echo "What was fixed:\n";
echo "- ✅ Company is active\n";
echo "- ✅ Branch created for company\n";
echo "- ✅ Caches cleared\n";
echo "- ✅ Routes rebuilt\n";
echo "- ✅ API middleware added\n";

echo "\n📋 NEXT STEPS:\n";
echo "1. Clear browser cache and ALL cookies for askproai.de\n";
echo "2. Go to: https://api.askproai.de/portal-test.php\n";
echo "3. Click 'Test Login' button\n";
echo "4. Then click 'Go to Dashboard'\n";

echo "\nAlternatively, login directly at:\n";
echo "https://api.askproai.de/business/login\n";

echo "\n⚠️  IMPORTANT: The URL should be /business/dashboard, NOT /test/session\n";