<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Appointments API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #2563eb;
        }
        .result {
            background: white;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Appointments API</h1>
        
        <div>
            <h2>Test Endpoints</h2>
            <button onclick="testEndpoint('/admin/api/appointments')">Get Appointments</button>
            <button onclick="testEndpoint('/admin/api/appointments/stats')">Get Stats</button>
            <button onclick="testEndpoint('/admin/api/appointments/quick-filters')">Get Quick Filters</button>
            <button onclick="testEndpoint('/admin/api/companies')">Get Companies</button>
            <button onclick="testEndpoint('/admin/api/customers')">Get Customers</button>
            <button onclick="testEndpoint('/admin/api/staff')">Get Staff</button>
            <button onclick="testEndpoint('/admin/api/services')">Get Services</button>
        </div>
        
        <div id="result" class="result"></div>
    </div>

    <script>
        async function testEndpoint(url) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = `<div>Testing ${url}...</div>`;
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `<div class="success">✓ Success (${response.status})</div><pre>${JSON.stringify(data, null, 2)}</pre>`;
                } else {
                    resultDiv.innerHTML = `<div class="error">✗ Error (${response.status})</div><pre>${JSON.stringify(data, null, 2)}</pre>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">✗ Network Error</div><pre>${error.message}</pre>`;
            }
        }
        
        // Test appointments on load
        window.onload = () => {
            testEndpoint('/admin/api/appointments');
        };
    </script>
</body>
</html>