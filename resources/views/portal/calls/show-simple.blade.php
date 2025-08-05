<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anruf Details - {{ $company->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <h1 class="text-xl font-semibold">{{ $company->name }}</h1>
                        <div class="hidden md:flex space-x-4">
                            <a href="{{ route('business.dashboard') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                            <a href="{{ route('business.calls.index') }}" class="bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium">Anrufe</a>
                            <a href="{{ route('business.appointments.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Termine</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <!-- Back Link -->
                <div class="mb-6">
                    <a href="{{ route('business.calls.index') }}" class="text-indigo-600 hover:text-indigo-900">
                        ← Zurück zur Übersicht
                    </a>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Anruf Details</h2>
                
                <!-- Call Details -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Anruf Informationen
                        </h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Telefonnummer</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $call->phone_number }}</dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Kunde</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ $call->customer->name ?? 'Unbekannt' }}
                                    @if($call->customer && $call->customer->email)
                                        <br><span class="text-gray-500">{{ $call->customer->email }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Datum & Zeit</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $call->created_at->format('d.m.Y H:i:s') }}</dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    @if($call->duration_seconds)
                                        {{ gmdate('H:i:s', $call->duration_seconds) }}
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($call->call_status === 'ended') bg-green-100 text-green-800
                                        @elseif($call->call_status === 'in_progress') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($call->call_status ?? 'Unknown') }}
                                    </span>
                                </dd>
                            </div>
                            @if($call->retellAgent)
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">AI Agent</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $call->retellAgent->name }}</dd>
                                </div>
                            @endif
                            @if($call->appointment)
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Gebuchter Termin</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ $call->appointment->start_time->format('d.m.Y H:i') }} Uhr
                                        @if($call->appointment->service)
                                            <br>{{ $call->appointment->service->name }}
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
                
                <!-- Summary -->
                @if($call->summary)
                    <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Zusammenfassung
                            </h3>
                        </div>
                        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                            <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $call->summary }}</div>
                        </div>
                    </div>
                @endif
                
                <!-- Transcript -->
                @if($call->transcript)
                    <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Transkript
                            </h3>
                        </div>
                        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                            <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $call->transcript }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>