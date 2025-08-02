<?php
/**
 * Test actual HTTP access to admin pages
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Get demo user
$user = User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die("Demo user not found!");
}

// Force login
Auth::login($user);

// Prepare session
session_start();
$_SESSION['forced_auth'] = true;
$_SESSION['forced_user_id'] = $user->id;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Access Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .test-result { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        iframe { width: 100%; height: 400px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Admin Access Test</h1>
    
    <div class="status info">
        <strong>Logged in as:</strong> <?= $user->email ?><br>
        <strong>User ID:</strong> <?= $user->id ?><br>
        <strong>Company ID:</strong> <?= $user->company_id ?><br>
        <strong>Is Super Admin:</strong> <?= $user->hasRole('super_admin') ? 'YES' : 'NO' ?>
    </div>
    
    <div class="test-result">
        <h2>Test 1: Direct Access Links</h2>
        <p>Click these links to test direct access:</p>
        <ul>
            <li><a href="/admin" target="_blank">Admin Dashboard</a></li>
            <li><a href="/admin/calls" target="_blank">Calls Page</a></li>
            <li><a href="/admin/appointments" target="_blank">Appointments Page</a></li>
        </ul>
    </div>
    
    <div class="test-result">
        <h2>Test 2: AJAX Request Tests</h2>
        <button onclick="testEndpoint('/admin/calls')">Test /admin/calls</button>
        <button onclick="testEndpoint('/admin/appointments')">Test /admin/appointments</button>
        <div id="ajax-results"></div>
    </div>
    
    <div class="test-result">
        <h2>Test 3: Embedded iFrame Test</h2>
        <p>Testing /admin/calls in iframe:</p>
        <iframe src="/admin/calls" id="test-iframe"></iframe>
    </div>
    
    <script>
    function testEndpoint(url) {
        const resultsDiv = document.getElementById('ajax-results');
        resultsDiv.innerHTML += '<div class="status info">Testing ' + url + '...</div>';
        
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            let statusClass = response.ok ? 'success' : 'error';
            let message = `${url}: ${response.status} ${response.statusText}`;
            
            if (response.status === 403) {
                message += ' - ❌ FORBIDDEN';
            } else if (response.status === 200) {
                message += ' - ✅ SUCCESS';
            } else if (response.status === 302) {
                message += ' - 🔄 REDIRECT (check auth)';
            }
            
            resultsDiv.innerHTML += '<div class="status ' + statusClass + '">' + message + '</div>';
            
            return response.text();
        })
        .then(text => {
            if (text.includes('403') || text.includes('Forbidden')) {
                resultsDiv.innerHTML += '<div class="status error">Response contains 403/Forbidden</div>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML += '<div class="status error">Error: ' + error + '</div>';
        });
    }
    
    // Monitor iframe load
    document.getElementById('test-iframe').onload = function() {
        try {
            const iframeDoc = this.contentDocument || this.contentWindow.document;
            const bodyText = iframeDoc.body.innerText || '';
            
            if (bodyText.includes('403') || bodyText.includes('Forbidden')) {
                document.querySelector('#test-iframe').insertAdjacentHTML('afterend', 
                    '<div class="status error">iFrame loaded with 403 error</div>');
            } else {
                document.querySelector('#test-iframe').insertAdjacentHTML('afterend', 
                    '<div class="status success">iFrame loaded successfully</div>');
            }
        } catch (e) {
            console.log('Cannot access iframe content (cross-origin)');
        }
    };
    </script>
</body>
</html>