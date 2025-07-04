<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell Monitor - Live Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Retell Monitor Dashboard</h1>
                    <p class="text-gray-600 mt-2">Live-Überwachung der Telefonfunktion</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div id="connection-status" class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse-slow mr-2"></div>
                        <span class="text-sm text-gray-600">Live</span>
                    </div>
                    <button onclick="location.reload()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Aktualisieren
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Anrufe heute</p>
                        <p class="text-2xl font-bold text-gray-800" id="calls-today">{{ $stats['calls_today'] ?? 0 }}</p>
                    </div>
                    <div class="text-green-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Termine heute</p>
                        <p class="text-2xl font-bold text-gray-800" id="appointments-today">{{ $stats['appointments_today'] ?? 0 }}</p>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zM4 8h12v8H4V8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Webhooks heute</p>
                        <p class="text-2xl font-bold text-gray-800" id="webhooks-today">{{ $stats['webhooks_today'] ?? 0 }}</p>
                    </div>
                    <div class="text-purple-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Erfolgsrate</p>
                        <p class="text-2xl font-bold text-gray-800" id="success-rate">{{ $stats['success_rate'] ?? 0 }}%</p>
                    </div>
                    <div class="text-yellow-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Live Webhooks -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Live Webhooks</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto" id="webhook-list">
                    @foreach($recentWebhooks as $webhook)
                    <div class="border-l-4 {{ $webhook->status === 'processed' ? 'border-green-500' : 'border-red-500' }} pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-sm font-semibold">{{ $webhook->payload['event'] ?? 'unknown' }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ $webhook->created_at->format('H:i:s') }}</span>
                            </div>
                            <span class="text-xs px-2 py-1 rounded {{ $webhook->status === 'processed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $webhook->status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Call ID: {{ $webhook->payload['call']['call_id'] ?? 'N/A' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Calls -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Aktuelle Anrufe</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto" id="calls-list">
                    @foreach($recentCalls as $call)
                    <div class="border rounded p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-sm">{{ $call->from_number ?? 'Unbekannt' }}</p>
                                <p class="text-xs text-gray-500">{{ $call->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800">
                                    {{ $call->duration ?? 0 }}s
                                </span>
                            </div>
                        </div>
                        @if($call->appointment_id)
                        <p class="text-xs text-green-600 mt-1">✓ Termin gebucht</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Gebuchte Termine</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto" id="appointments-list">
                    @foreach($recentAppointments as $appointment)
                    <div class="border rounded p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-sm">{{ $appointment->customer->name ?? 'Unbekannt' }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->service->name ?? 'N/A' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ $appointment->date?->format('d.m.Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->start_time }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Tel: {{ $appointment->customer->phone ?? 'N/A' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">System Status</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Horizon Queue</span>
                        <span class="flex items-center">
                            <div class="w-2 h-2 {{ $systemStatus['horizon'] ? 'bg-green-500' : 'bg-red-500' }} rounded-full mr-2"></div>
                            <span class="text-sm">{{ $systemStatus['horizon'] ? 'Läuft' : 'Gestoppt' }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Datenbank</span>
                        <span class="flex items-center">
                            <div class="w-2 h-2 {{ $systemStatus['database'] ? 'bg-green-500' : 'bg-red-500' }} rounded-full mr-2"></div>
                            <span class="text-sm">{{ $systemStatus['database'] ? 'Verbunden' : 'Getrennt' }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Retell API</span>
                        <span class="flex items-center">
                            <div class="w-2 h-2 {{ $systemStatus['retell_api'] ? 'bg-green-500' : 'bg-red-500' }} rounded-full mr-2"></div>
                            <span class="text-sm">{{ $systemStatus['retell_api'] ? 'Konfiguriert' : 'Fehlt' }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Cal.com API</span>
                        <span class="flex items-center">
                            <div class="w-2 h-2 {{ $systemStatus['calcom_api'] ? 'bg-green-500' : 'bg-red-500' }} rounded-full mr-2"></div>
                            <span class="text-sm">{{ $systemStatus['calcom_api'] ? 'Konfiguriert' : 'Fehlt' }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Tools -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Test Tools</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <button onclick="testCallStarted()" class="bg-green-500 text-white px-4 py-3 rounded hover:bg-green-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    Test Call Started
                </button>
                <button onclick="testCallEnded()" class="bg-blue-500 text-white px-4 py-3 rounded hover:bg-blue-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zM4 8h12v8H4V8z" clip-rule="evenodd"></path>
                    </svg>
                    Test Call Ended
                </button>
                <button onclick="testFunction()" class="bg-purple-500 text-white px-4 py-3 rounded hover:bg-purple-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Test Function
                </button>
                <button onclick="checkCalcom()" class="bg-yellow-500 text-white px-4 py-3 rounded hover:bg-yellow-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Check Cal.com
                </button>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Aktivität (letzte 24 Stunden)</h2>
            <canvas id="activityChart" width="400" height="100"></canvas>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 seconds
        setInterval(function() {
            refreshData();
        }, 5000);

        function refreshData() {
            axios.get('/retell-monitor/stats')
                .then(response => {
                    const data = response.data;
                    document.getElementById('calls-today').textContent = data.calls_today;
                    document.getElementById('appointments-today').textContent = data.appointments_today;
                    document.getElementById('webhooks-today').textContent = data.webhooks_today;
                    document.getElementById('success-rate').textContent = data.success_rate + '%';
                    
                    // Update lists if we add them to the API response later
                    // updateWebhookList(data.recent_webhooks);
                    // updateCallsList(data.recent_calls);
                    // updateAppointmentsList(data.recent_appointments);
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                });
        }

        function updateWebhookList(webhooks) {
            const container = document.getElementById('webhook-list');
            container.innerHTML = webhooks.map(webhook => `
                <div class="border-l-4 ${webhook.status === 'processed' ? 'border-green-500' : 'border-red-500'} pl-4 py-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-semibold">${webhook.event}</span>
                            <span class="text-xs text-gray-500 ml-2">${webhook.time}</span>
                        </div>
                        <span class="text-xs px-2 py-1 rounded ${webhook.status === 'processed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${webhook.status}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Call ID: ${webhook.call_id || 'N/A'}</p>
                </div>
            `).join('');
        }

        function updateCallsList(calls) {
            const container = document.getElementById('calls-list');
            container.innerHTML = calls.map(call => `
                <div class="border rounded p-3 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-sm">${call.from_number || 'Unbekannt'}</p>
                            <p class="text-xs text-gray-500">${call.created_at}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800">
                                ${call.duration || 0}s
                            </span>
                        </div>
                    </div>
                    ${call.has_appointment ? '<p class="text-xs text-green-600 mt-1">✓ Termin gebucht</p>' : ''}
                </div>
            `).join('');
        }

        function updateAppointmentsList(appointments) {
            const container = document.getElementById('appointments-list');
            container.innerHTML = appointments.map(appointment => `
                <div class="border rounded p-3 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-sm">${appointment.customer_name || 'Unbekannt'}</p>
                            <p class="text-xs text-gray-500">${appointment.service_name || 'N/A'}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">${appointment.date}</p>
                            <p class="text-xs text-gray-500">${appointment.time}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Tel: ${appointment.customer_phone || 'N/A'}</p>
                </div>
            `).join('');
        }

        // Test functions
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
                alert('Test Call Started webhook sent successfully!');
                refreshData();
            })
            .catch(error => {
                alert('Error: ' + error.message);
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
                alert('Test Call Ended webhook with appointment data sent successfully!');
                refreshData();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function testFunction() {
            axios.post('/api/retell/test-function', {
                function_name: 'collect_appointment',
                arguments: {
                    date: '30.06.2025',
                    time: '14:00',
                    service: 'Haarschnitt',
                    customer_name: 'Test Kunde',
                    customer_phone: '+4915112345678'
                },
                call_id: 'test_' + Date.now()
            })
            .then(response => {
                alert('Function response: ' + JSON.stringify(response.data));
                refreshData();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function checkCalcom() {
            axios.get('/api/retell/monitor/calcom-status')
                .then(response => {
                    alert('Cal.com Status:\n' + JSON.stringify(response.data, null, 2));
                })
                .catch(error => {
                    alert('Error checking Cal.com: ' + error.message);
                });
        }

        // Initialize activity chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + 'h'),
                datasets: [{
                    label: 'Anrufe',
                    data: Array.from({length: 24}, () => Math.floor(Math.random() * 10)),
                    borderColor: 'rgb(34, 197, 94)',
                    tension: 0.1
                }, {
                    label: 'Termine',
                    data: Array.from({length: 24}, () => Math.floor(Math.random() * 5)),
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>