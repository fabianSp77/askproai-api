@extends('portal.layouts.app')

@section('title', 'Termin Details')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('portal.appointments') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Zurück zu meinen Terminen
            </a>
        </div>

        <!-- Appointment Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Termin Details</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Termin ID: #{{ $appointment->id }}
                        </p>
                    </div>
                    <div>
                        @php
                            $statusColors = [
                                'scheduled' => 'bg-blue-100 text-blue-800',
                                'confirmed' => 'bg-green-100 text-green-800',
                                'completed' => 'bg-gray-100 text-gray-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                'no_show' => 'bg-yellow-100 text-yellow-800',
                                'booked' => 'bg-green-100 text-green-800',
                            ];
                            $statusLabels = [
                                'scheduled' => 'Geplant',
                                'confirmed' => 'Bestätigt',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgesagt',
                                'no_show' => 'Nicht erschienen',
                                'booked' => 'Gebucht',
                            ];
                        @endphp
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $statusColors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$appointment->status] ?? $appointment->status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Appointment Details -->
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Date & Time -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Datum & Zeit</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($appointment->starts_at)
                                <div class="font-semibold">{{ $appointment->starts_at->format('d.m.Y') }}</div>
                                <div>{{ $appointment->starts_at->format('H:i') }} 
                                    @if($appointment->ends_at)
                                        - {{ $appointment->ends_at->format('H:i') }} Uhr
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">Nicht festgelegt</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Service -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Service</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($appointment->service)
                                {{ $appointment->service->name }}
                            @elseif($appointment->payload)
                                @php
                                    $payload = json_decode($appointment->payload, true);
                                @endphp
                                {{ $payload['service_name'] ?? 'N/A' }}
                            @else
                                <span class="text-gray-400">Nicht angegeben</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Staff -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mitarbeiter</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($appointment->staff)
                                {{ $appointment->staff->name }}
                            @elseif($appointment->payload)
                                @php
                                    $payload = json_decode($appointment->payload, true);
                                    $calcomResponse = $payload['calcom_response'] ?? [];
                                    $userName = $calcomResponse['user']['name'] ?? null;
                                @endphp
                                {{ $userName ?? 'Nicht zugewiesen' }}
                            @else
                                <span class="text-gray-400">Nicht zugewiesen</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Branch -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Filiale</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($appointment->branch)
                                {{ $appointment->branch->name }}
                            @else
                                <span class="text-gray-400">Nicht angegeben</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Price -->
                    @if($appointment->price)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Preis</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ number_format($appointment->price, 2, ',', '.') }} €
                        </dd>
                    </div>
                    @endif

                    <!-- External ID -->
                    @if($appointment->external_id || $appointment->calcom_booking_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Buchungsnummer</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $appointment->external_id ?? $appointment->calcom_booking_id }}
                        </dd>
                    </div>
                    @endif
                </dl>

                <!-- Notes -->
                @if($appointment->notes || (isset($payload) && isset($payload['notes'])))
                <div class="mt-6">
                    <dt class="text-sm font-medium text-gray-500">Notizen</dt>
                    <dd class="mt-1 text-sm text-gray-900 bg-gray-50 rounded-md p-3">
                        {{ $appointment->notes ?? $payload['notes'] ?? 'Keine Notizen' }}
                    </dd>
                </div>
                @endif

                <!-- Meeting Link -->
                @if($appointment->payload)
                    @php
                        $payload = json_decode($appointment->payload, true);
                        $calcomResponse = $payload['calcom_response'] ?? [];
                        $videoCallUrl = $calcomResponse['videoCallUrl'] ?? null;
                        $references = $calcomResponse['references'] ?? [];
                        $meetingUrl = null;
                        
                        foreach ($references as $ref) {
                            if (!empty($ref['meetingUrl'])) {
                                $meetingUrl = $ref['meetingUrl'];
                                break;
                            }
                        }
                        
                        $finalMeetingUrl = $videoCallUrl ?? $meetingUrl;
                    @endphp
                    
                    @if($finalMeetingUrl && $appointment->status !== 'cancelled' && $appointment->status !== 'completed')
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-gray-500 mb-2">Video-Meeting</dt>
                        <dd>
                            <a href="{{ $finalMeetingUrl }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Meeting beitreten
                            </a>
                        </dd>
                    </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if($appointment->status !== 'cancelled' && $appointment->status !== 'completed')
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Aktionen</h2>
                
                @if($appointment->starts_at && $appointment->starts_at->isFuture())
                <div class="flex space-x-4">
                    <form method="POST" action="{{ route('portal.appointments.cancel', $appointment) }}" 
                          onsubmit="return confirm('Möchten Sie diesen Termin wirklich absagen?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Termin absagen
                        </button>
                    </form>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Bitte beachten Sie eventuelle Stornierungsbedingungen.
                </p>
                @else
                <p class="text-sm text-gray-500">
                    Dieser Termin kann nicht mehr geändert werden.
                </p>
                @endif
            </div>
        </div>
        @endif

        <!-- Created/Updated Info -->
        <div class="mt-6 text-sm text-gray-500 text-center">
            <p>Erstellt am {{ $appointment->created_at->format('d.m.Y H:i') }}</p>
            @if($appointment->updated_at->ne($appointment->created_at))
            <p>Zuletzt aktualisiert am {{ $appointment->updated_at->format('d.m.Y H:i') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection