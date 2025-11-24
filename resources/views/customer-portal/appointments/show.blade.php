@extends('customer-portal.layouts.app')

@section('title', 'Termindetails')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8" x-data="appointmentDetail" x-init="init('{{ $appointmentId }}')">
    <!-- Back Button -->
    <div class="px-4 sm:px-0 mb-6">
        <a href="{{ url('/meine-termine') }}"
           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>
            Zurück zu meinen Terminen
        </a>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="px-4 sm:px-0">
        <div class="bg-white rounded-lg shadow-sm p-8">
            @include('customer-portal.components.loading-spinner')
            <p class="mt-4 text-center text-gray-600">Termindetails werden geladen...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="!loading && error" class="px-4 sm:px-0">
        <x-customer-portal.error-message :message="error" type="error">
            <div class="mt-4">
                <a href="{{ url('/meine-termine') }}"
                   class="text-sm font-medium text-red-700 hover:text-red-600">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Zurück zu meinen Terminen
                </a>
            </div>
        </x-customer-portal.error-message>
    </div>

    <!-- Appointment Details -->
    <div x-show="!loading && !error && appointment" x-cloak class="px-4 sm:px-0 space-y-6">
        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Status Banner -->
            <div class="px-6 py-4 border-b"
                 :class="{
                    'bg-green-50 border-green-200': appointment.status === 'confirmed',
                    'bg-yellow-50 border-yellow-200': appointment.status === 'pending',
                    'bg-red-50 border-red-200': appointment.status === 'cancelled',
                    'bg-gray-50 border-gray-200': appointment.status === 'completed'
                 }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="text-2xl mr-3"
                           :class="{
                            'fas fa-check-circle text-green-600': appointment.status === 'confirmed',
                            'fas fa-clock text-yellow-600': appointment.status === 'pending',
                            'fas fa-times-circle text-red-600': appointment.status === 'cancelled',
                            'fas fa-calendar-check text-gray-600': appointment.status === 'completed'
                           }"></i>
                        <div>
                            <h2 class="text-xl font-bold"
                                :class="{
                                'text-green-900': appointment.status === 'confirmed',
                                'text-yellow-900': appointment.status === 'pending',
                                'text-red-900': appointment.status === 'cancelled',
                                'text-gray-900': appointment.status === 'completed'
                                }">
                                Termin <span x-text="getStatusText(appointment.status)"></span>
                            </h2>
                            <p class="text-sm"
                               :class="{
                                'text-green-700': appointment.status === 'confirmed',
                                'text-yellow-700': appointment.status === 'pending',
                                'text-red-700': appointment.status === 'cancelled',
                                'text-gray-700': appointment.status === 'completed'
                               }">
                                Termin-ID: <span x-text="appointment.id"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Info -->
            <div class="p-6">
                <!-- Date & Time -->
                <div class="flex items-center mb-6 pb-6 border-b border-gray-200">
                    <div class="flex-shrink-0 w-20 h-20 bg-primary rounded-lg flex flex-col items-center justify-center text-white shadow-lg">
                        <span class="text-sm font-medium" x-text="formatDate(appointment.date, 'short-month')"></span>
                        <span class="text-3xl font-bold" x-text="formatDate(appointment.date, 'day')"></span>
                    </div>
                    <div class="ml-6">
                        <h3 class="text-2xl font-bold text-gray-900" x-text="formatDate(appointment.date, 'weekday-long')"></h3>
                        <p class="text-lg text-gray-600 mt-1">
                            <i class="fas fa-clock mr-2"></i>
                            <span x-text="appointment.start_time"></span>
                            -
                            <span x-text="appointment.end_time"></span>
                            Uhr
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            Dauer: <span x-text="appointment.duration"></span> Minuten
                        </p>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Service -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-cut text-primary text-xl"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Dienstleistung</p>
                                <p class="text-lg font-semibold text-gray-900" x-text="appointment.service_name"></p>
                                <p x-show="appointment.service_description"
                                   class="text-sm text-gray-600 mt-1"
                                   x-text="appointment.service_description"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Staff -->
                    <div class="bg-gray-50 rounded-lg p-4" x-show="appointment.staff_name">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-12 w-12 rounded-full bg-primary text-white flex items-center justify-center text-lg font-medium">
                                    <span x-text="appointment.staff_name ? appointment.staff_name.charAt(0).toUpperCase() : '?'"></span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Mitarbeiter</p>
                                <p class="text-lg font-semibold text-gray-900" x-text="appointment.staff_name"></p>
                                <p x-show="appointment.staff_specialty"
                                   class="text-sm text-gray-600 mt-1"
                                   x-text="appointment.staff_specialty"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="bg-gray-50 rounded-lg p-4" x-show="appointment.location">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-primary text-xl"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Standort</p>
                                <p class="text-base text-gray-900" x-text="appointment.location"></p>
                                <p x-show="appointment.location_address"
                                   class="text-sm text-gray-600 mt-1"
                                   x-text="appointment.location_address"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="bg-gray-50 rounded-lg p-4" x-show="appointment.price">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-euro-sign text-primary text-xl"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Preis</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    <span x-text="appointment.price"></span> €
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div x-show="appointment.notes" class="mt-6 pt-6 border-t border-gray-200">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-900 mb-1">Notizen</p>
                                <p class="text-sm text-blue-800" x-text="appointment.notes"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-6 py-4 flex flex-wrap gap-3" x-show="['confirmed', 'pending'].includes(appointment.status)">
                <a :href="`/meine-termine/${appointment.id}/umbuchen`"
                   class="flex-1 sm:flex-none inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Termin umbuchen
                </a>
                <a :href="`/meine-termine/${appointment.id}/stornieren`"
                   class="flex-1 sm:flex-none inline-flex justify-center items-center px-6 py-3 border border-red-300 text-base font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times-circle mr-2"></i>
                    Termin stornieren
                </a>
            </div>
        </div>

        <!-- Additional Info Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Weitere Informationen</h3>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                    <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(appointment.created_at, 'datetime')"></dd>
                </div>
                <div x-show="appointment.updated_at !== appointment.created_at">
                    <dt class="text-sm font-medium text-gray-500">Zuletzt aktualisiert</dt>
                    <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(appointment.updated_at, 'datetime')"></dd>
                </div>
                <div x-show="appointment.cancelled_at">
                    <dt class="text-sm font-medium text-gray-500">Storniert am</dt>
                    <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(appointment.cancelled_at, 'datetime')"></dd>
                </div>
                <div x-show="appointment.cancellation_reason">
                    <dt class="text-sm font-medium text-gray-500">Stornierungsgrund</dt>
                    <dd class="mt-1 text-sm text-gray-900" x-text="appointment.cancellation_reason"></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentDetail', () => ({
            loading: true,
            error: null,
            appointment: null,

            init(appointmentId) {
                // Check authentication
                if (!this.$root.isAuthenticated()) {
                    window.location.href = '/kundenportal/login';
                    return;
                }

                this.loadAppointment(appointmentId);
            },

            async loadAppointment(id) {
                this.loading = true;
                this.error = null;

                try {
                    const response = await axios.get(`/api/customer-portal/appointments/${id}`);

                    if (response.data.success) {
                        this.appointment = response.data.data;
                    } else {
                        this.error = response.data.message || 'Termin konnte nicht geladen werden.';
                    }
                } catch (error) {
                    console.error('Error loading appointment:', error);

                    if (error.response?.status === 404) {
                        this.error = 'Dieser Termin wurde nicht gefunden.';
                    } else {
                        this.error = 'Termindetails konnten nicht geladen werden. Bitte versuchen Sie es später erneut.';
                    }

                    this.$root.handleApiError(error);
                } finally {
                    this.loading = false;
                }
            },

            formatDate(dateString, format) {
                if (!dateString) return '';
                const date = new Date(dateString);

                switch (format) {
                    case 'weekday-long':
                        return date.toLocaleDateString('de-DE', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    case 'day':
                        return date.getDate();
                    case 'short-month':
                        return date.toLocaleDateString('de-DE', { month: 'short' }).toUpperCase();
                    case 'datetime':
                        return date.toLocaleDateString('de-DE', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    default:
                        return date.toLocaleDateString('de-DE');
                }
            },

            getStatusText(status) {
                const statusMap = {
                    'confirmed': 'Bestätigt',
                    'pending': 'Ausstehend',
                    'cancelled': 'Storniert',
                    'completed': 'Abgeschlossen'
                };
                return statusMap[status] || status;
            }
        }));
    });
</script>
@endsection
