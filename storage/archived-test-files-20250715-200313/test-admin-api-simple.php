<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Direct test of admin API endpoints

// Get user
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    die("Admin user not found");
}

// Create token
$token = $user->createToken('test-token')->plainTextToken;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin API Test</title>
</head>
<body>
    <h1>Admin API Direct Test</h1>
    
    <h2>Token: <?php echo $token; ?></h2>
    
    <div id="results"></div>
    
    <script>
        const token = '<?php echo $token; ?>';
        
        async function testAPI() {
            const results = document.getElementById('results');
            
            // Test 1: Direct API call
            try {
                console.log('Testing /admin-api/calls with token:', token);
                
                const response = await fetch('/admin-api/calls', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const text = await response.text();
                console.log('Response text:', text);
                
                results.innerHTML += `<h3>API Response (Status: ${response.status}):</h3>`;
                results.innerHTML += `<pre>${text}</pre>`;
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    results.innerHTML += `<h3>Parsed JSON:</h3>`;
                    results.innerHTML += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                }
                
            } catch (error) {
                console.error('API Error:', error);
                results.innerHTML += `<p style="color: red;">Error: ${error.message}</p>`;
            }
        }
        
        // Run test
        testAPI();
        
        // Also set token in localStorage for the React app
        localStorage.setItem('admin_token', token);
        console.log('Token saved to localStorage');
    </script>
</body>
</html>