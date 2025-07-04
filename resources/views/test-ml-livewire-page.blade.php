<!DOCTYPE html>
<html>
<head>
    <title>Test ML Livewire Page</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    <script>
        // Log all network requests
        window.addEventListener('load', function() {
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                console.log('Fetch request:', args[0], args[1]);
                return originalFetch.apply(this, args).then(response => {
                    console.log('Fetch response:', response.status, response.statusText);
                    if (!response.ok) {
                        response.clone().text().then(text => {
                            console.error('Error response body:', text);
                        });
                    }
                    return response;
                }).catch(error => {
                    console.error('Fetch error:', error);
                    throw error;
                });
            };
        });
    </script>
</head>
<body>
    <h1>Test ML Livewire Page</h1>
    
    <div>
        <h2>Debug Information:</h2>
        <ul>
            <li>CSRF Token: {{ csrf_token() }}</li>
            <li>Session ID: {{ session()->getId() }}</li>
            <li>Livewire Loaded: <span id="livewire-status">Checking...</span></li>
        </ul>
    </div>
    
    <div>
        <h2>Simple Livewire Test:</h2>
        <button onclick="testLivewireUpdate()">Test Livewire Update</button>
        <div id="test-result"></div>
    </div>
    
    @livewireScripts
    
    <script>
        // Check if Livewire is loaded
        setTimeout(() => {
            document.getElementById('livewire-status').textContent = 
                (typeof window.Livewire !== 'undefined') ? 'Yes' : 'No';
        }, 1000);
        
        function testLivewireUpdate() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = 'Testing...';
            
            // Create a minimal Livewire update request
            const payload = {
                components: [{
                    snapshot: JSON.stringify({
                        data: {},
                        memo: {
                            id: 'test-id',
                            name: 'test-component',
                            path: window.location.pathname,
                            method: 'GET',
                            children: [],
                            scripts: [],
                            assets: [],
                            errors: [],
                            locale: 'en'
                        }
                    }),
                    updates: {},
                    calls: []
                }]
            };
            
            fetch('/livewire/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Livewire': 'true',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                resultDiv.innerHTML = `Status: ${response.status}<br>`;
                return response.text();
            })
            .then(text => {
                resultDiv.innerHTML += `Response: <pre>${text}</pre>`;
            })
            .catch(error => {
                resultDiv.innerHTML = `Error: ${error.message}`;
            });
        }
    </script>
</body>
</html>