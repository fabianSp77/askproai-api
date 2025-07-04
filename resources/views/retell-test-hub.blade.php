<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell Test Hub - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-6xl w-full">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-400 to-purple-600 bg-clip-text text-transparent mb-4">
                    Retell Test Hub
                </h1>
                <p class="text-xl text-gray-400">Zentrale für alle Retell Telefonfunktion Tests</p>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Live Monitor -->
                <a href="/retell-monitor" class="group">
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-blue-500 transition-all hover:shadow-2xl hover:shadow-blue-500/20">
                        <div class="flex items-center mb-4">
                            <div class="bg-blue-500/20 p-3 rounded-lg mr-4">
                                <i class="fas fa-chart-line text-2xl text-blue-400"></i>
                            </div>
                            <h2 class="text-2xl font-semibold">Live Monitor</h2>
                        </div>
                        <p class="text-gray-400 mb-4">Echtzeit-Dashboard mit allen wichtigen Metriken und Live-Updates</p>
                        <div class="flex items-center text-blue-400 group-hover:text-blue-300">
                            <span>Dashboard öffnen</span>
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </a>

                <!-- Admin Panel -->
                <a href="/admin" class="group">
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-green-500 transition-all hover:shadow-2xl hover:shadow-green-500/20">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-500/20 p-3 rounded-lg mr-4">
                                <i class="fas fa-cog text-2xl text-green-400"></i>
                            </div>
                            <h2 class="text-2xl font-semibold">Admin Panel</h2>
                        </div>
                        <p class="text-gray-400 mb-4">Hauptverwaltung für Anrufe, Termine und Konfiguration</p>
                        <div class="flex items-center text-green-400 group-hover:text-green-300">
                            <span>Zum Admin Panel</span>
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </a>

                <!-- Test Scripts -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-500/20 p-3 rounded-lg mr-4">
                            <i class="fas fa-terminal text-2xl text-purple-400"></i>
                        </div>
                        <h2 class="text-2xl font-semibold">Test Scripts</h2>
                    </div>
                    <p class="text-gray-400 mb-4">Command-Line Tools für detaillierte Tests</p>
                    <div class="space-y-2 text-sm">
                        <code class="block bg-gray-900 px-3 py-2 rounded">./monitor-retell-webhooks.php</code>
                        <code class="block bg-gray-900 px-3 py-2 rounded">./retell-test-dashboard.php</code>
                        <code class="block bg-gray-900 px-3 py-2 rounded">./test-retell-webhook-*.php</code>
                    </div>
                </div>

            </div>

            <!-- Quick Actions -->
            <div class="mt-12 bg-gray-800 rounded-xl p-8 border border-gray-700">
                <h3 class="text-2xl font-semibold mb-6 text-center">Quick Test Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <button onclick="testCallStarted()" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-all hover:shadow-lg hover:shadow-green-600/30">
                        <i class="fas fa-phone mr-2"></i>
                        Test Call Started
                    </button>
                    
                    <button onclick="testCallEnded()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all hover:shadow-lg hover:shadow-blue-600/30">
                        <i class="fas fa-calendar-check mr-2"></i>
                        Test Call Ended
                    </button>
                    
                    <button onclick="testFunction()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all hover:shadow-lg hover:shadow-purple-600/30">
                        <i class="fas fa-code mr-2"></i>
                        Test Function
                    </button>
                    
                    <button onclick="window.open('/retell-monitor', '_blank')" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-6 rounded-lg transition-all hover:shadow-lg hover:shadow-yellow-600/30">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Open Monitor
                    </button>
                    
                </div>
            </div>

            <!-- Status Overview -->
            <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                    <p class="text-gray-400 text-sm mb-2">Environment</p>
                    <p class="text-2xl font-bold text-green-400">{{ app()->environment() }}</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                    <p class="text-gray-400 text-sm mb-2">Test Routes</p>
                    <p class="text-2xl font-bold {{ app()->environment(['local', 'development', 'testing']) ? 'text-green-400' : 'text-red-400' }}">
                        {{ app()->environment(['local', 'development', 'testing']) ? 'Aktiv' : 'Inaktiv' }}
                    </p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                    <p class="text-gray-400 text-sm mb-2">Retell API</p>
                    <p class="text-2xl font-bold {{ config('services.retell.api_key') ? 'text-green-400' : 'text-red-400' }}">
                        {{ config('services.retell.api_key') ? 'Konfiguriert' : 'Fehlt' }}
                    </p>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 text-center border border-gray-700">
                    <p class="text-gray-400 text-sm mb-2">Cal.com API</p>
                    <p class="text-2xl font-bold {{ \App\Models\Company::whereNotNull('calcom_api_key')->exists() ? 'text-green-400' : 'text-red-400' }}">
                        {{ \App\Models\Company::whereNotNull('calcom_api_key')->exists() ? 'Konfiguriert' : 'Fehlt' }}
                    </p>
                </div>
            </div>

            <!-- Documentation Link -->
            <div class="mt-8 text-center">
                <a href="/RETELL_TELEFON_TEST_ANLEITUNG.md" target="_blank" class="text-blue-400 hover:text-blue-300 underline">
                    <i class="fas fa-book mr-2"></i>
                    Vollständige Test-Dokumentation
                </a>
            </div>

        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-4 hidden">
        <div class="flex items-center">
            <div id="toast-icon" class="mr-3"></div>
            <div>
                <p id="toast-title" class="font-semibold"></p>
                <p id="toast-message" class="text-sm text-gray-400"></p>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
        function showToast(type, title, message) {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const titleEl = document.getElementById('toast-title');
            const messageEl = document.getElementById('toast-message');
            
            // Set icon based on type
            if (type === 'success') {
                icon.innerHTML = '<i class="fas fa-check-circle text-2xl text-green-400"></i>';
            } else if (type === 'error') {
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-2xl text-red-400"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-info-circle text-2xl text-blue-400"></i>';
            }
            
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
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
                showToast('success', 'Test erfolgreich', 'Call Started webhook wurde gesendet');
            })
            .catch(error => {
                showToast('error', 'Fehler', error.message);
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
                showToast('success', 'Test erfolgreich', 'Call Ended webhook mit Termindaten wurde gesendet');
            })
            .catch(error => {
                showToast('error', 'Fehler', error.message);
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
                showToast('success', 'Function Test', 'Response: ' + JSON.stringify(response.data.message || response.data));
            })
            .catch(error => {
                showToast('error', 'Fehler', error.message);
            });
        }
    </script>
</body>
</html>