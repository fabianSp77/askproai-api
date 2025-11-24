@extends('customer-portal.layouts.app')

@section('title', 'Meine Termine')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="appointmentsList" x-init="init()">
    <!-- Header -->
    <div class="px-4 sm:px-0 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Meine Termine</h1>
        <p class="mt-2 text-sm text-gray-600">
            Verwalten Sie Ihre Termine und buchen Sie neue Dienstleistungen.
        </p>
    </div>

    <!-- Tabs -->
    <div class="px-4 sm:px-0 mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'upcoming'"
                        :class="{
                            'border-primary text-primary': activeTab === 'upcoming',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'upcoming'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        :aria-current="activeTab === 'upcoming' ? 'page' : undefined">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Anstehend
                    <span x-show="upcomingCount > 0"
                          class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium"
                          :class="activeTab === 'upcoming' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'"
                          x-text="upcomingCount"></span>
                </button>

                <button @click="activeTab = 'past'"
                        :class="{
                            'border-primary text-primary': activeTab === 'past',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'past'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        :aria-current="activeTab === 'past' ? 'page' : undefined">
                    <i class="fas fa-history mr-2"></i>
                    Vergangene
                    <span x-show="pastCount > 0"
                          class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium"
                          :class="activeTab === 'past' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'"
                          x-text="pastCount"></span>
                </button>

                <button @click="activeTab = 'cancelled'"
                        :class="{
                            'border-primary text-primary': activeTab === 'cancelled',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'cancelled'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        :aria-current="activeTab === 'cancelled' ? 'page' : undefined">
                    <i class="fas fa-times-circle mr-2"></i>
                    Storniert
                    <span x-show="cancelledCount > 0"
                          class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium"
                          :class="activeTab === 'cancelled' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'"
                          x-text="cancelledCount"></span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="px-4 sm:px-0">
        <div class="bg-white rounded-lg shadow-sm p-8">
            @include('customer-portal.components.loading-spinner')
            <p class="mt-4 text-center text-gray-600">Termine werden geladen...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="!loading && error" class="px-4 sm:px-0">
        <x-customer-portal.error-message :message="error" type="error">
            <button @click="loadAppointments()"
                    class="mt-3 text-sm font-medium text-red-700 hover:text-red-600">
                <i class="fas fa-redo mr-1"></i>
                Erneut versuchen
            </button>
        </x-customer-portal.error-message>
    </div>

    <!-- Appointments Grid -->
    <div x-show="!loading && !error" class="px-4 sm:px-0">
        <!-- Upcoming Appointments -->
        <div x-show="activeTab === 'upcoming'" x-cloak>
            <template x-if="upcomingAppointments.length === 0">
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <i class="fas fa-calendar-plus text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Keine anstehenden Termine</h3>
                    <p class="text-gray-600 mb-6">Sie haben derzeit keine geplanten Termine.</p>
                    <a href="tel:{{ config('app.phone', '') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-phone mr-2"></i>
                        Termin vereinbaren
                    </a>
                </div>
            </template>

            <div x-show="upcomingAppointments.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <template x-for="appointment in upcomingAppointments" :key="appointment.id">
                    <div x-data="{ appointment: appointment }">
                        @include('customer-portal.components.appointment-card', ['showActions' => true])
                    </div>
                </template>
            </div>
        </div>

        <!-- Past Appointments -->
        <div x-show="activeTab === 'past'" x-cloak>
            <template x-if="pastAppointments.length === 0">
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <i class="fas fa-calendar-times text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Keine vergangenen Termine</h3>
                    <p class="text-gray-600">Sie haben noch keine abgeschlossenen Termine.</p>
                </div>
            </template>

            <div x-show="pastAppointments.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <template x-for="appointment in pastAppointments" :key="appointment.id">
                    <div x-data="{ appointment: appointment }">
                        @include('customer-portal.components.appointment-card', ['showActions' => false])
                    </div>
                </template>
            </div>
        </div>

        <!-- Cancelled Appointments -->
        <div x-show="activeTab === 'cancelled'" x-cloak>
            <template x-if="cancelledAppointments.length === 0">
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <i class="fas fa-check-circle text-green-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Keine stornierten Termine</h3>
                    <p class="text-gray-600">Sie haben keine stornierten Termine.</p>
                </div>
            </template>

            <div x-show="cancelledAppointments.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <template x-for="appointment in cancelledAppointments" :key="appointment.id">
                    <div x-data="{ appointment: appointment }">
                        @include('customer-portal.components.appointment-card', ['showActions' => false])
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentsList', () => ({
            loading: true,
            error: null,
            activeTab: 'upcoming',
            appointments: [],

            init() {
                // Check authentication
                if (!this.$root.isAuthenticated()) {
                    window.location.href = '/kundenportal/login';
                    return;
                }

                this.loadAppointments();
            },

            get upcomingAppointments() {
                return this.appointments.filter(apt => {
                    return ['confirmed', 'pending'].includes(apt.status) &&
                           new Date(apt.date) >= new Date();
                }).sort((a, b) => new Date(a.date) - new Date(b.date));
            },

            get pastAppointments() {
                return this.appointments.filter(apt => {
                    return apt.status === 'completed' ||
                           (apt.status === 'confirmed' && new Date(apt.date) < new Date());
                }).sort((a, b) => new Date(b.date) - new Date(a.date));
            },

            get cancelledAppointments() {
                return this.appointments.filter(apt => apt.status === 'cancelled')
                    .sort((a, b) => new Date(b.date) - new Date(a.date));
            },

            get upcomingCount() {
                return this.upcomingAppointments.length;
            },

            get pastCount() {
                return this.pastAppointments.length;
            },

            get cancelledCount() {
                return this.cancelledAppointments.length;
            },

            async loadAppointments() {
                this.loading = true;
                this.error = null;

                try {
                    const response = await axios.get('/api/customer-portal/appointments');

                    if (response.data.success) {
                        this.appointments = response.data.data;
                    } else {
                        this.error = response.data.message || 'Fehler beim Laden der Termine.';
                    }
                } catch (error) {
                    console.error('Error loading appointments:', error);
                    this.error = 'Termine konnten nicht geladen werden. Bitte versuchen Sie es später erneut.';
                    this.$root.handleApiError(error);
                } finally {
                    this.loading = false;
                }
            },

            formatDate(dateString, format) {
                return this.$root.formatDate(dateString, format);
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
