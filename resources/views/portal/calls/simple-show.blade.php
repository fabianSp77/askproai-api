@extends('portal.layouts.unified')

@section('page-title', 'Anruf Details')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('business.calls.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zurück zur Übersicht
            </a>
        </div>

        <!-- Call Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Anruf #{{ $call['id'] }}</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        {{ ucfirst($call['status']) }}
                    </span>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Telefonnummer</p>
                        <p class="text-lg font-medium">{{ $call['phone_number'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Datum & Zeit</p>
                        <p class="text-lg font-medium">{{ $call['created_at']->format('d.m.Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Dauer</p>
                        <p class="text-lg font-medium">{{ floor($call['duration_sec'] / 60) }}:{{ str_pad($call['duration_sec'] % 60, 2, '0', STR_PAD_LEFT) }} Min</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Summary -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Zusammenfassung</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-700">{{ $call['summary'] }}</p>
                    </div>
                </div>

                <!-- Transcript -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Transkript</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="whitespace-pre-line text-gray-700">{{ $call['transcript'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Customer Info -->
                @if($call['customer'])
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Kunde</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium">{{ $call['customer']['name'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">E-Mail</p>
                            <p class="font-medium">{{ $call['customer']['email'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Telefon</p>
                            <p class="font-medium">{{ $call['customer']['phone'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Appointment Info -->
                @if($call['appointment'])
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Gebuchter Termin</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Datum & Zeit</p>
                            <p class="font-medium">{{ $call['appointment']['scheduled_at']->format('d.m.Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Service</p>
                            <p class="font-medium">{{ $call['appointment']['service'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Dauer</p>
                            <p class="font-medium">{{ $call['appointment']['duration'] }} Minuten</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Aktionen</h3>
                    </div>
                    <div class="px-6 py-4 space-y-2">
                        <button class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            <i class="fas fa-phone mr-2"></i>
                            Zurückrufen
                        </button>
                        <button class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition">
                            <i class="fas fa-calendar mr-2"></i>
                            Termin buchen
                        </button>
                        <button class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition">
                            <i class="fas fa-download mr-2"></i>
                            Als PDF exportieren
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection