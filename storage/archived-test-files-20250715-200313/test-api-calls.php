<?php
// Simple test to check API response

// Get CSRF token from cookies
$csrfToken = $_COOKIE['XSRF-TOKEN'] ?? '';
if ($csrfToken) {
    $csrfToken = str_replace('%3D', '=', $csrfToken);
    $csrfToken = json_decode(base64_decode($csrfToken), true)['value'] ?? '';
}

// Get session cookie
$sessionCookie = $_COOKIE['askproai_session'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
</head>
<body>
    <h1>Testing /business/api/calls endpoint</h1>
    
    <div id="result"></div>
    
    <script>
        async function testApi() {
            const resultDiv = document.getElementById('result');
            
            try {
                // Get CSRF token from meta tag
                const csrfToken = '<?php echo $csrfToken; ?>';
                
                console.log('Testing API with CSRF token:', csrfToken);
                
                const response = await fetch('/business/api/calls', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                let data;
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }
                
                resultDiv.innerHTML = `
                    <h2>Response:</h2>
                    <p>Status: ${response.status}</p>
                    <p>Content-Type: ${contentType}</p>
                    <pre>${typeof data === 'string' ? data.substring(0, 500) : JSON.stringify(data, null, 2)}</pre>
                `;
                
            } catch (error) {
                resultDiv.innerHTML = `<h2>Error:</h2><pre>${error.message}</pre>`;
                console.error('Error:', error);
            }
        }
        
        // Run test on load
        testApi();
    </script>
</body>
</html>