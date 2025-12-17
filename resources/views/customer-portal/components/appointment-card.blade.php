@props([
    'appointment' => null,
    'showActions' => true,
    'compact' => false
])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200 overflow-hidden">
    <!-- Status Badge -->
    @if(!$compact)
    <div class="px-4 py-2 border-b border-gray-100"
         :class="{
            'bg-green-50': appointment.status === 'confirmed',
            'bg-yellow-50': appointment.status === 'pending',
            'bg-red-50': appointment.status === 'cancelled',
            'bg-gray-50': appointment.status === 'completed'
         }">
        <span class="inline-flex items-center text-xs font-medium"
              :class="{
                'text-green-700': appointment.status === 'confirmed',
                'text-yellow-700': appointment.status === 'pending',
                'text-red-700': appointment.status === 'cancelled',
                'text-gray-700': appointment.status === 'completed'
              }">
            <i class="fas mr-1"
               :class="{
                'fa-check-circle': appointment.status === 'confirmed',
                'fa-clock': appointment.status === 'pending',
                'fa-times-circle': appointment.status === 'cancelled',
                'fa-calendar-check': appointment.status === 'completed'
               }"></i>
            <span x-text="getStatusText(appointment.status)"></span>
        </span>
    </div>
    @endif

    <div class="{{ $compact ? 'p-4' : 'p-6' }}">
        <!-- Date & Time -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-14 h-14 bg-primary rounded-lg flex flex-col items-center justify-center text-white">
                    <span class="text-xs font-medium" x-text="formatDate(appointment.date, 'short-month')"></span>
                    <span class="text-xl font-bold" x-text="formatDate(appointment.date, 'day')"></span>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-900" x-text="formatDate(appointment.date, 'weekday')"></p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        <span x-text="appointment.start_time"></span>
                        -
                        <span x-text="appointment.end_time"></span>
                        <span class="text-xs text-gray-500 ml-1">
                            (<span x-text="appointment.duration"></span> Min.)
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Service -->
        <div class="mb-3">
            <p class="text-sm font-medium text-gray-500 mb-1">Dienstleistung</p>
            <p class="text-base font-medium text-gray-900" x-text="appointment.service_name"></p>
        </div>

        <!-- Staff -->
        <div class="mb-3" x-show="appointment.staff_name">
            <p class="text-sm font-medium text-gray-500 mb-1">Mitarbeiter</p>
            <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium mr-2">
                    <span x-text="appointment.staff_name ? appointment.staff_name.charAt(0).toUpperCase() : '?'"></span>
                </div>
                <span class="text-base text-gray-900" x-text="appointment.staff_name"></span>
            </div>
        </div>

        <!-- Location -->
        <div x-show="appointment.location">
            <p class="text-sm font-medium text-gray-500 mb-1">Standort</p>
            <p class="text-sm text-gray-700">
                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                <span x-text="appointment.location"></span>
            </p>
        </div>

        <!-- Notes -->
        <div x-show="appointment.notes" class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-sm font-medium text-gray-500 mb-1">Notizen</p>
            <p class="text-sm text-gray-700" x-text="appointment.notes"></p>
        </div>

        <!-- Actions -->
        @if($showActions)
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
            <a :href="`/meine-termine/${appointment.id}`"
               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-eye mr-2"></i>
                Details
            </a>

            <template x-if="['confirmed', 'pending'].includes(appointment.status)">
                <a :href="`/meine-termine/${appointment.id}/umbuchen`"
                   class="inline-flex items-center px-3 py-2 border border-primary shadow-sm text-sm font-medium rounded-md text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Umbuchen
                </a>
            </template>

            <template x-if="['confirmed', 'pending'].includes(appointment.status)">
                <a :href="`/meine-termine/${appointment.id}/stornieren`"
                   class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times-circle mr-2"></i>
                    Stornieren
                </a>
            </template>
        </div>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
    // Helper functions for appointment cards
    function getStatusText(status) {
        const statusMap = {
            'confirmed': 'Bestätigt',
            'pending': 'Ausstehend',
            'cancelled': 'Storniert',
            'completed': 'Abgeschlossen'
        };
        return statusMap[status] || status;
    }

    function formatDate(dateString, format) {
        if (!dateString) return '';
        const date = new Date(dateString);

        switch (format) {
            case 'weekday':
                return date.toLocaleDateString('de-DE', { weekday: 'long' });
            case 'day':
                return date.getDate();
            case 'short-month':
                return date.toLocaleDateString('de-DE', { month: 'short' }).toUpperCase();
            case 'full':
                return date.toLocaleDateString('de-DE', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            default:
                return date.toLocaleDateString('de-DE');
        }
    }

    // Add to Alpine global scope if needed
    if (typeof Alpine !== 'undefined') {
        document.addEventListener('alpine:init', () => {
            Alpine.store('helpers', {
                getStatusText,
                formatDate
            });
        });
    }
</script>
@endpush
@endonce
