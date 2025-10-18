{{-- Service Selector Component
    Reusable component for service selection in booking flow

    Props:
    - $availableServices: Array of services
    - $selectedServiceId: Currently selected service
    - $serviceName: Selected service name
    - $serviceDuration: Selected service duration

    Methods:
    - selectService(serviceId): Select a service
--}}

<div class="booking-section">
    <div class="booking-section-title">
        🎯 Service auswählen
    </div>
    <div class="booking-section-subtitle">
        Wählen Sie die gewünschte Leistung
    </div>

    @if(count($availableServices) === 0)
        {{-- No Services Available --}}
        <div class="booking-alert alert-warning">
            ℹ️ Keine Services verfügbar für diese Filiale
        </div>

    @else
        {{-- Services Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3" role="radiogroup" aria-labelledby="service-selector-label">
            @foreach($availableServices as $service)
                <button
                    wire:click="selectService('{{ $service['id'] }}')"
                    type="button"
                    class="selector-card {{ $selectedServiceId === $service['id'] ? 'active' : '' }}"
                    wire:key="service-{{ $service['id'] }}"
                    role="radio"
                    :aria-checked="$selectedServiceId === '{{ $service['id'] }}'"
                    aria-label="Service: {{ $service['name'] }}, Dauer: {{ $service['duration_minutes'] }} Minuten">

                    <div class="selector-card-header">
                        <div class="selector-card-icon">💇</div>
                        <div class="flex-1">
                            <div class="selector-card-title">
                                {{ $service['name'] }}
                            </div>
                            <div class="selector-card-subtitle">
                                ⏱️ {{ $service['duration_minutes'] }} Minuten
                            </div>
                        </div>
                        @if($selectedServiceId === $service['id'])
                            <div class="ml-2 text-green-500 dark:text-green-400">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Selection Info --}}
    @if($selectedServiceId && $serviceName)
        <div class="booking-alert alert-success mt-3">
            ✅ Service gewählt: <strong>{{ $serviceName }}</strong> ({{ $serviceDuration }} Min)
        </div>
    @endif
</div>
