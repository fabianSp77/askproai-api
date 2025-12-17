@extends('customer-portal.layouts.app')

@section('title', 'Termin stornieren')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8" x-data="appointmentCancel" x-init="init('{{ $appointmentId }}')">
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
        <h1 class="text-3xl font-bold text-gray-900">Termin stornieren</h1>
        <p class="mt-2 text-sm text-gray-600">
            Bitte bestätigen Sie die Stornierung Ihres Termins.
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

    <!-- Cancellation Form -->
    <div x-show="!loading && !error && appointment" x-cloak class="px-4 sm:px-0 space-y-6">
        <!-- Warning Banner -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-lg font-medium text-red-900">Achtung: Termin wird storniert</h3>
                    <p class="mt-2 text-sm text-red-800">
                        Diese Aktion kann nicht rückgängig gemacht werden. Ihr Termin wird endgültig storniert.
                    </p>
                </div>
            </div>
        </div>

        <!-- Appointment Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-calendar text-gray-400 text-xl mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Zu stornieren</h2>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-red-500">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Datum & Uhrzeit</p>
                        <p class="text-base font-semibold text-gray-900">
                            <span x-text="formatDate(appointment.date, 'long')"></span>
                        </p>
                        <p class="text-sm text-gray-700 mt-1">
                            <span x-text="appointment.start_time"></span> - <span x-text="appointment.end_time"></span> Uhr
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Dienstleistung</p>
                        <p class="text-base font-semibold text-gray-900" x-text="appointment.service_name"></p>
                        <p class="text-sm text-gray-700 mt-1" x-show="appointment.staff_name">
                            mit <span x-text="appointment.staff_name"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Policy -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-900">Stornierungsrichtlinien</h3>
                    <div class="mt-2 text-sm text-yellow-800" x-html="cancellationPolicyHtml"></div>
                </div>
            </div>
        </div>

        <!-- Cancellation Reason -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                Grund der Stornierung (optional)
            </label>
            <textarea id="cancellation_reason"
                      name="cancellation_reason"
                      x-model="cancellationReason"
                      rows="4"
                      class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md"
                      placeholder="Bitte teilen Sie uns mit, warum Sie den Termin stornieren möchten..."></textarea>
            <p class="mt-2 text-xs text-gray-500">
                Ihr Feedback hilft uns, unseren Service zu verbessern.
            </p>
        </div>

        <!-- Alternative: Reschedule Suggestion -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-blue-500 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-900">Termin lieber umbuchen?</h3>
                    <p class="mt-2 text-sm text-blue-800">
                        Wenn der Zeitpunkt nicht passt, können Sie den Termin auch umbuchen statt zu stornieren.
                    </p>
                    <a :href="`/meine-termine/${appointmentId}/umbuchen`"
                       class="mt-3 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-600">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Termin umbuchen
                    </a>
                </div>
            </div>
        </div>

        <!-- Confirmation Checkbox -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox"
                           id="confirm_cancellation"
                           x-model="confirmCancellation"
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                </div>
                <div class="ml-3">
                    <label for="confirm_cancellation" class="font-medium text-gray-700">
                        Ich bestätige die Stornierung dieses Termins
                    </label>
                    <p class="text-sm text-gray-500 mt-1">
                        Ich habe die Stornierungsrichtlinien gelesen und verstanden.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button @click="submitCancellation"
                    :disabled="!confirmCancellation || submitting"
                    class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">
                    <i class="fas fa-times-circle mr-2"></i>
                    Termin endgültig stornieren
                </span>
                <span x-show="submitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Wird storniert...
                </span>
            </button>

            <a :href="`/meine-termine/${appointmentId}`"
               class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-arrow-left mr-2"></i>
                Zurück
            </a>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmModal"
         x-cloak
         class="fixed z-50 inset-0 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showConfirmModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showConfirmModal = false"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div x-show="showConfirmModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Stornierung bestätigen
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Sind Sie sicher, dass Sie diesen Termin stornieren möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                    <button @click="confirmCancel"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Ja, stornieren
                    </button>
                    <button @click="showConfirmModal = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentCancel', () => ({
            appointmentId: null,
            loading: true,
            error: null,
            appointment: null,
            cancellationReason: '',
            confirmCancellation: false,
            showConfirmModal: false,
            submitting: false,

            init(appointmentId) {
                this.appointmentId = appointmentId;

                // Check authentication
                if (!this.$root.isAuthenticated()) {
                    window.location.href = '/kundenportal/login';
                    return;
                }

                this.loadAppointment();
            },

            async loadAppointment() {
                this.loading = true;
                this.error = null;

                try {
                    const response = await axios.get(`/api/customer-portal/appointments/${this.appointmentId}`);

                    if (response.data.success) {
                        this.appointment = response.data.data;

                        // Check if appointment can be cancelled
                        if (!['confirmed', 'pending'].includes(this.appointment.status)) {
                            this.error = 'Dieser Termin kann nicht storniert werden.';
                        }
                    } else {
                        this.error = response.data.message || 'Termin konnte nicht geladen werden.';
                    }
                } catch (error) {
                    console.error('Error loading appointment:', error);
                    this.error = 'Termin konnte nicht geladen werden. Bitte versuchen Sie es später erneut.';
                    this.$root.handleApiError(error);
                } finally {
                    this.loading = false;
                }
            },

            get cancellationPolicyHtml() {
                if (!this.appointment) return '';

                const appointmentDate = new Date(this.appointment.date);
                const now = new Date();
                const hoursUntilAppointment = (appointmentDate - now) / (1000 * 60 * 60);

                if (hoursUntilAppointment > 24) {
                    return `
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Kostenlose Stornierung:</strong> Mehr als 24 Stunden vor dem Termin</li>
                            <li>Sie erhalten eine Bestätigungsmail</li>
                            <li>Keine Stornogebühren</li>
                        </ul>
                    `;
                } else if (hoursUntilAppointment > 0) {
                    return `
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Kurzfristige Stornierung:</strong> Weniger als 24 Stunden vor dem Termin</li>
                            <li class="text-red-700 font-medium">Es kann eine Stornogebühr anfallen</li>
                            <li>Bitte kontaktieren Sie uns für weitere Informationen</li>
                        </ul>
                    `;
                } else {
                    return `
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Termin bereits verstrichen</strong></li>
                            <li>Dieser Termin kann nicht mehr storniert werden</li>
                            <li>Bitte kontaktieren Sie uns bei Fragen</li>
                        </ul>
                    `;
                }
            },

            submitCancellation() {
                if (!this.confirmCancellation) {
                    this.$root.showToast('Bitte bestätigen Sie die Stornierung.', 'warning');
                    return;
                }

                this.showConfirmModal = true;
            },

            async confirmCancel() {
                this.showConfirmModal = false;
                this.submitting = true;

                try {
                    const response = await axios.delete(
                        `/api/customer-portal/appointments/${this.appointmentId}`,
                        {
                            data: {
                                reason: this.cancellationReason || null
                            }
                        }
                    );

                    if (response.data.success) {
                        this.$root.showToast('Termin erfolgreich storniert.', 'success');

                        // Redirect to appointments list
                        setTimeout(() => {
                            window.location.href = '/meine-termine';
                        }, 1500);
                    } else {
                        this.$root.showToast(response.data.message || 'Stornierung fehlgeschlagen.', 'error');
                    }
                } catch (error) {
                    console.error('Error cancelling appointment:', error);
                    this.$root.handleApiError(error);
                } finally {
                    this.submitting = false;
                }
            },

            formatDate(dateString, format) {
                return this.$root.formatDate(dateString, format);
            }
        }));
    });
</script>
@endsection
