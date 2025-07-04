<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell Monitor - Basic</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Retell Monitor - Basic Version</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <p class="text-gray-600">Dies ist eine vereinfachte Version des Monitors.</p>
            <p class="text-gray-600">Die Live-Daten werden über JavaScript geladen.</p>
        </div>

        <!-- Loading indicator -->
        <div id="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            <p class="mt-2">Lade Daten...</p>
        </div>

        <!-- Stats will be loaded here -->
        <div id="stats-container" class="hidden">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Anrufe heute</h3>
                    <p class="text-2xl font-bold" id="calls-today">-</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Termine heute</h3>
                    <p class="text-2xl font-bold" id="appointments-today">-</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Webhooks heute</h3>
                    <p class="text-2xl font-bold" id="webhooks-today">-</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Status</h3>
                    <p class="text-2xl font-bold text-green-500">OK</p>
                </div>
            </div>
        </div>

        <!-- Test Buttons -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">Test Functions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <button onclick="testCallStarted()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Test Call Started
                </button>
                <button onclick="testCallEnded()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Test Call Ended
                </button>
                <button onclick="loadStats()" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Refresh Stats
                </button>
                <a href="/retell-test" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-center">
                    Back to Hub
                </a>
            </div>
        </div>

        <!-- Results -->
        <div id="results" class="mt-6"></div>
    </div>

    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
        // Load stats on page load
        window.onload = function() {
            loadStats();
            
            // Refresh every 5 seconds
            setInterval(loadStats, 5000);
        };

        function loadStats() {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('stats-container').classList.add('hidden');
            
            // Load real stats from API
            axios.get('/retell-monitor/stats')
                .then(response => {
                    const data = response.data;
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('stats-container').classList.remove('hidden');
                    
                    // Update stats
                    document.getElementById('calls-today').textContent = data.calls_today || '0';
                    document.getElementById('appointments-today').textContent = data.appointments_today || '0';
                    document.getElementById('webhooks-today').textContent = data.webhooks_today || '0';
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('stats-container').classList.remove('hidden');
                    
                    // Show error values
                    document.getElementById('calls-today').textContent = 'Error';
                    document.getElementById('appointments-today').textContent = 'Error';
                    document.getElementById('webhooks-today').textContent = 'Error';
                });
        }

        function showMessage(type, message) {
            const resultsDiv = document.getElementById('results');
            const alertClass = type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            resultsDiv.innerHTML = `
                <div class="${alertClass} p-4 rounded-lg">
                    <p>${message}</p>
                </div>
            `;
            
            setTimeout(() => {
                resultsDiv.innerHTML = '';
            }, 5000);
        }

        function testCallStarted() {
            axios.post('/api/retell/test-webhook', {
                event: 'call_started',
                call: {
                    call_id: 'test_' + Date.now(),
                    from_number: '+4915112345678',
                    to_number: '+4930123456789',
                    direction: 'inbound',
                    start_timestamp: Date.now()
                }
            })
            .then(response => {
                showMessage('success', 'Call Started webhook sent successfully!');
                loadStats();
            })
            .catch(error => {
                showMessage('error', 'Error: ' + error.message);
            });
        }

        function testCallEnded() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            axios.post('/api/retell/test-webhook', {
                event: 'call_ended',
                call: {
                    call_id: 'test_' + Date.now(),
                    from_number: '+4915112345678',
                    to_number: '+4930123456789',
                    direction: 'inbound',
                    start_timestamp: Date.now() - 300000,
                    end_timestamp: Date.now(),
                    call_duration: 300,
                    retell_llm_dynamic_variables: {
                        appointment_date: tomorrow.toISOString().split('T')[0],
                        appointment_time: '14:00',
                        service: 'Haarschnitt',
                        customer_name: 'Test Kunde',
                        customer_phone: '+4915112345678'
                    }
                }
            })
            .then(response => {
                showMessage('success', 'Call Ended webhook with appointment data sent!');
                loadStats();
            })
            .catch(error => {
                showMessage('error', 'Error: ' + error.message);
            });
        }
    </script>
</body>
</html>