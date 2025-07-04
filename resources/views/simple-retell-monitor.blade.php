<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Retell Monitor</h1>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Anrufe heute</h3>
                <p class="text-2xl font-bold">{{ $stats['calls_today'] ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Termine heute</h3>
                <p class="text-2xl font-bold">{{ $stats['appointments_today'] ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">Webhooks heute</h3>
                <p class="text-2xl font-bold">{{ $stats['webhooks_today'] ?? 0 }}</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-500 text-sm">System Status</h3>
                <p class="text-2xl font-bold {{ $systemStatus['retell_api'] ? 'text-green-500' : 'text-red-500' }}">
                    {{ $systemStatus['retell_api'] ? 'OK' : 'Error' }}
                </p>
            </div>
        </div>

        <!-- Recent Data -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Webhooks -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Recent Webhooks</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($recentWebhooks as $webhook)
                    <div class="border-l-4 {{ $webhook->status === 'processed' ? 'border-green-500' : 'border-red-500' }} pl-4 py-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-semibold">
                                {{ json_decode($webhook->payload)->event ?? 'unknown' }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($webhook->created_at)->format('H:i:s') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Calls -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Recent Calls</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($recentCalls as $call)
                    <div class="border rounded p-3">
                        <div class="flex justify-between">
                            <span class="text-sm">{{ $call->from_number ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-500">{{ $call->duration ?? 0 }}s</span>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($call->created_at)->format('d.m.Y H:i') }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Appointments -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Recent Phone Appointments</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($recentAppointments as $appointment)
                    <div class="border rounded p-3">
                        <p class="font-semibold text-sm">{{ $appointment->customer_name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">{{ $appointment->service_name ?? 'N/A' }}</p>
                        <p class="text-xs">
                            {{ $appointment->date ? \Carbon\Carbon::parse($appointment->date)->format('d.m.Y') : 'N/A' }}
                            {{ $appointment->start_time ?? '' }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">System Status</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>Database</span>
                        <span class="{{ $systemStatus['database'] ? 'text-green-500' : 'text-red-500' }}">
                            {{ $systemStatus['database'] ? '✓' : '✗' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Retell API</span>
                        <span class="{{ $systemStatus['retell_api'] ? 'text-green-500' : 'text-red-500' }}">
                            {{ $systemStatus['retell_api'] ? '✓' : '✗' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cal.com API</span>
                        <span class="{{ $systemStatus['calcom_api'] ? 'text-green-500' : 'text-red-500' }}">
                            {{ $systemStatus['calcom_api'] ? '✓' : '✗' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-6 text-center">
            <a href="/retell-test" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                Back to Test Hub
            </a>
        </div>
    </div>

    <script>
        // Simple auto-refresh every 10 seconds
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>