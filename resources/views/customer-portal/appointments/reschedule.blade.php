@extends('customer-portal.layouts.app')

@section('title', 'Termin umbuchen')

@section('content')
<div class="max-w-6xl mx-auto py-6 sm:px-6 lg:px-8" x-data="appointmentReschedule" x-init="init('{{ $appointmentId }}')">
    <!-- Back Button -->
    <div class="px-4 sm:px-0 mb-6">
        <a :href="`/meine-termine/${appointmentId}`"
           class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>
            Zurück zu Termindetails
        </a>
    </div>

    <!-- Header -->
    <div class="px-4 sm:px-0 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Termin umbuchen</h1>
        <p class="mt-2 text-sm text-gray-600">
            Wählen Sie einen neuen Zeitpunkt für Ihren Termin.
        </p>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="px-4 sm:px-0">
        <div class="bg-white rounded-lg shadow-sm p-8">
            @include('customer-portal.components.loading-spinner')
            <p class="mt-4 text-center text-gray-600">Daten werden geladen...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="!loading && error" class="px-4 sm:px-0">
        <x-customer-portal.error-message :message="error" type="error" />
    </div>

    <!-- Reschedule Form -->
    <div x-show="!loading && !error && appointment" x-cloak class="px-4 sm:px-0 space-y-6">
        <!-- Current Appointment (Read-only) -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-calendar text-gray-400 text-xl mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Aktueller Termin</h2>
            </div>

            <div class="bg-gray-100 rounded-lg p-4 opacity-75">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Datum</p>
                        <p class="text-sm text-gray-900" x-text="formatDate(appointment.date, 'long')"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Uhrzeit</p>
                        <p class="text-sm text-gray-900">
                            <span x-text="appointment.start_time"></span> - <span x-text="appointment.end_time"></span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Dienstleistung</p>
                        <p class="text-sm text-gray-900" x-text="appointment.service_name"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alternative Slots Loading -->
        <div x-show="loadingAlternatives" class="bg-white rounded-lg shadow-sm p-8">
            @include('customer-portal.components.loading-spinner')
            <p class="mt-4 text-center text-gray-600">Alternative Termine werden geladen...</p>
        </div>

        <!-- Alternative Slots -->
        <div x-show="!loadingAlternatives" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-6">
                <i class="fas fa-clock text-primary text-xl mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Neuen Termin wählen</h2>
            </div>

            <!-- No Alternatives -->
            <template x-if="alternativeSlots.length === 0">
                <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600">Momentan sind keine alternativen Termine verfügbar.</p>
                    <p class="text-sm text-gray-500 mt-2">Bitte versuchen Sie es später erneut oder kontaktieren Sie uns telefonisch.</p>
                    <a href="tel:{{ config('app.phone', '') }}"
                       class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-phone mr-2"></i>
                        Telefonisch kontaktieren
                    </a>
                </div>
            </template>

            <!-- Time Slot Picker -->
            <div x-show="alternativeSlots.length > 0"
                 x-data="{ selectedSlot: null }"
                 @slot-selected="selectedSlot = $event.detail">

                <!-- Quick Suggestion Chips -->
                <div class="mb-6" x-show="quickSuggestions.length > 0">
                    <p class="text-sm font-medium text-gray-700 mb-3">Empfohlene Termine:</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="suggestion in quickSuggestions" :key="suggestion.id">
                            <button @click="selectSlot(suggestion)"
                                    type="button"
                                    class="px-4 py-2 border rounded-lg text-sm font-medium transition-all"
                                    :class="{
                                        'border-primary bg-primary text-white': selectedSlot?.id === suggestion.id,
                                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50': selectedSlot?.id !== suggestion.id
                                    }">
                                <i class="fas fa-star text-xs mr-1"></i>
                                <span x-text="formatSlotLabel(suggestion)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Full Calendar View -->
                <div class="border-t border-gray-200 pt-6">
                    @include('customer-portal.components.time-slot-picker')
                </div>
            </div>
        </div>

        <!-- Reschedule Policy Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-900">Umbuchungsrichtlinien</h3>
                    <div class="mt-2 text-sm text-blue-800">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Umbuchungen sind bis zu 24 Stunden vor dem Termin kostenlos möglich</li>
                            <li>Bei späteren Umbuchungen kann eine Gebühr anfallen</li>
                            <li>Sie erhalten eine Bestätigung per E-Mail</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button @click="submitReschedule"
                    :disabled="!selectedSlot || submitting"
                    class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">
                    <i class="fas fa-check mr-2"></i>
                    Umbuchung bestätigen
                </span>
                <span x-show="submitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Wird umgebucht...
                </span>
            </button>

            <a :href="`/meine-termine/${appointmentId}`"
               class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                Abbrechen
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentReschedule', () => ({
            appointmentId: null,
            loading: true,
            loadingAlternatives: false,
            error: null,
            appointment: null,
            alternativeSlots: [],
            selectedSlot: null,
            submitting: false,

            init(appointmentId) {
                this.appointmentId = appointmentId;

                // Check authentication
                if (!this.$root.isAuthenticated()) {
                    window.location.href = '/kundenportal/login';
                    return;
                }

                this.loadData();
            },

            async loadData() {
                this.loading = true;
                this.error = null;

                try {
                    // Load appointment details
                    const appointmentResponse = await axios.get(`/api/customer-portal/appointments/${this.appointmentId}`);

                    if (appointmentResponse.data.success) {
                        this.appointment = appointmentResponse.data.data;

                        // Check if appointment can be rescheduled
                        if (!['confirmed', 'pending'].includes(this.appointment.status)) {
                            this.error = 'Dieser Termin kann nicht umgebucht werden.';
                            this.loading = false;
                            return;
                        }

                        // Load alternative slots
                        await this.loadAlternatives();
                    } else {
                        this.error = appointmentResponse.data.message || 'Termin konnte nicht geladen werden.';
                    }
                } catch (error) {
                    console.error('Error loading data:', error);
                    this.error = 'Daten konnten nicht geladen werden. Bitte versuchen Sie es später erneut.';
                    this.$root.handleApiError(error);
                } finally {
                    this.loading = false;
                }
            },

            async loadAlternatives() {
                this.loadingAlternatives = true;

                try {
                    const response = await axios.get(`/api/customer-portal/appointments/${this.appointmentId}/alternatives`);

                    if (response.data.success) {
                        this.alternativeSlots = response.data.data;
                        // Store in root for time-slot-picker component
                        this.$root.alternativeSlots = this.alternativeSlots;
                    } else {
                        this.$root.showToast(response.data.message || 'Alternative Termine konnten nicht geladen werden.', 'warning');
                    }
                } catch (error) {
                    console.error('Error loading alternatives:', error);
                    this.$root.handleApiError(error);
                } finally {
                    this.loadingAlternatives = false;
                }
            },

            get quickSuggestions() {
                // Return first 3 available slots as quick suggestions
                return this.alternativeSlots.filter(slot => slot.available).slice(0, 3);
            },

            selectSlot(slot) {
                if (!slot.available) return;
                this.selectedSlot = slot;
                this.$dispatch('slot-selected', slot);
            },

            async submitReschedule() {
                if (!this.selectedSlot) {
                    this.$root.showToast('Bitte wählen Sie einen neuen Termin aus.', 'warning');
                    return;
                }

                this.submitting = true;

                try {
                    const response = await axios.put(
                        `/api/customer-portal/appointments/${this.appointmentId}/reschedule`,
                        {
                            new_date: this.selectedSlot.date,
                            new_start_time: this.selectedSlot.time
                        }
                    );

                    if (response.data.success) {
                        this.$root.showToast('Termin erfolgreich umgebucht!', 'success');

                        // Redirect to appointment details
                        setTimeout(() => {
                            window.location.href = `/meine-termine/${this.appointmentId}`;
                        }, 1500);
                    } else {
                        this.$root.showToast(response.data.message || 'Umbuchung fehlgeschlagen.', 'error');
                    }
                } catch (error) {
                    console.error('Error rescheduling:', error);
                    this.$root.handleApiError(error);
                } finally {
                    this.submitting = false;
                }
            },

            formatDate(dateString, format) {
                return this.$root.formatDate(dateString, format);
            },

            formatSlotLabel(slot) {
                const date = new Date(slot.date);
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);

                let dateLabel;
                if (date.toDateString() === today.toDateString()) {
                    dateLabel = 'Heute';
                } else if (date.toDateString() === tomorrow.toDateString()) {
                    dateLabel = 'Morgen';
                } else {
                    dateLabel = date.toLocaleDateString('de-DE', { weekday: 'short', day: 'numeric', month: 'short' });
                }

                return `${dateLabel}, ${slot.time}`;
            }
        }));
    });
</script>
@endsection
