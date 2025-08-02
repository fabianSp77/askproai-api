@extends('portal.layouts.unified')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Termine
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('business.appointments.create') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Neuer Termin
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Heute</dt>
                                <dd class="text-lg font-medium text-gray-900" id="today-count">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Diese Woche</dt>
                                <dd class="text-lg font-medium text-gray-900" id="week-count">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Bestätigt</dt>
                                <dd class="text-lg font-medium text-gray-900" id="confirmed-count">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Gesamt</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-count">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label for="date-filter" class="block text-sm font-medium text-gray-700">Datum</label>
                        <input type="date" id="date-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="status-filter" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Alle</option>
                            <option value="scheduled">Geplant</option>
                            <option value="confirmed">Bestätigt</option>
                            <option value="completed">Abgeschlossen</option>
                            <option value="cancelled">Abgesagt</option>
                        </select>
                    </div>
                    <div>
                        <label for="branch-filter" class="block text-sm font-medium text-gray-700">Filiale</label>
                        <select id="branch-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Alle Filialen</option>
                        </select>
                    </div>
                    <div>
                        <label for="search-filter" class="block text-sm font-medium text-gray-700">Suche</label>
                        <input type="text" id="search-filter" placeholder="Name oder Telefon..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Termine Liste
                </h3>
            </div>
            <div id="appointments-container">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Termine werden geladen...</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 hidden">
            <!-- Pagination will be inserted here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let filters = {
        date: '',
        status: '',
        branch: '',
        search: ''
    };

    // Load initial data
    loadAppointments();
    loadStats();

    // Setup filter listeners
    document.getElementById('date-filter').addEventListener('change', function() {
        filters.date = this.value;
        currentPage = 1;
        loadAppointments();
    });

    document.getElementById('status-filter').addEventListener('change', function() {
        filters.status = this.value;
        currentPage = 1;
        loadAppointments();
    });

    document.getElementById('branch-filter').addEventListener('change', function() {
        filters.branch = this.value;
        currentPage = 1;
        loadAppointments();
    });

    document.getElementById('search-filter').addEventListener('input', debounce(function() {
        filters.search = this.value;
        currentPage = 1;
        loadAppointments();
    }, 300));

    function loadStats() {
        fetch('/business/api/appointments/stats', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('today-count').textContent = data.today || 0;
            document.getElementById('week-count').textContent = data.week || 0;
            document.getElementById('confirmed-count').textContent = data.confirmed || 0;
            document.getElementById('total-count').textContent = data.total || 0;
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
    }

    function loadAppointments(page = 1) {
        currentPage = page;
        
        const params = new URLSearchParams({
            page: currentPage,
            ...filters
        });

        fetch(`/business/api/appointments?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            renderAppointments(data.data || []);
            renderPagination(data);
        })
        .catch(error => {
            console.error('Error loading appointments:', error);
            document.getElementById('appointments-container').innerHTML = `
                <div class="text-center py-12">
                    <p class="text-red-600">Fehler beim Laden der Termine</p>
                </div>
            `;
        });
    }

    function renderAppointments(appointments) {
        const container = document.getElementById('appointments-container');
        
        if (appointments.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Keine Termine gefunden</p>
                </div>
            `;
            return;
        }

        const appointmentsList = appointments.map(appointment => {
            const date = new Date(appointment.starts_at);
            const dateStr = date.toLocaleDateString('de-DE');
            const timeStr = date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
            
            const statusColors = {
                'scheduled': 'bg-gray-100 text-gray-800',
                'confirmed': 'bg-green-100 text-green-800',
                'completed': 'bg-blue-100 text-blue-800',
                'cancelled': 'bg-red-100 text-red-800'
            };
            
            const statusLabels = {
                'scheduled': 'Geplant',
                'confirmed': 'Bestätigt',
                'completed': 'Abgeschlossen',
                'cancelled': 'Abgesagt'
            };

            return `
                <li class="border-b border-gray-200">
                    <a href="/business/appointments/${appointment.id}" class="block hover:bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        ${appointment.customer?.name || 'Unbekannter Kunde'}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ${appointment.service?.name || 'Keine Dienstleistung'} • ${appointment.staff?.name || 'Kein Mitarbeiter'}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ${dateStr} um ${timeStr}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[appointment.status] || 'bg-gray-100 text-gray-800'}">
                                    ${statusLabels[appointment.status] || appointment.status}
                                </span>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        }).join('');

        container.innerHTML = `<ul>${appointmentsList}</ul>`;
    }

    function renderPagination(data) {
        const container = document.getElementById('pagination-container');
        
        if (!data.links || data.links.length <= 3) {
            container.classList.add('hidden');
            return;
        }
        
        container.classList.remove('hidden');
        
        const links = data.links.map(link => {
            if (link.label === '&laquo; Previous') {
                return `<a href="#" onclick="loadAppointments(${data.current_page - 1}); return false;" class="${link.url ? '' : 'opacity-50 cursor-not-allowed'} relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Zurück</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>`;
            }
            
            if (link.label === 'Next &raquo;') {
                return `<a href="#" onclick="loadAppointments(${data.current_page + 1}); return false;" class="${link.url ? '' : 'opacity-50 cursor-not-allowed'} relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Weiter</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>`;
            }
            
            return `<a href="#" onclick="loadAppointments(${link.label}); return false;" class="${link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                ${link.label}
            </a>`;
        }).join('');
        
        container.innerHTML = `
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="#" onclick="loadAppointments(${data.current_page - 1}); return false;" class="${data.current_page === 1 ? 'opacity-50 cursor-not-allowed' : ''} relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Zurück
                </a>
                <a href="#" onclick="loadAppointments(${data.current_page + 1}); return false;" class="${data.current_page === data.last_page ? 'opacity-50 cursor-not-allowed' : ''} ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Weiter
                </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Zeige <span class="font-medium">${data.from || 0}</span> bis <span class="font-medium">${data.to || 0}</span> von <span class="font-medium">${data.total || 0}</span> Ergebnissen
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        ${links}
                    </nav>
                </div>
            </div>
        `;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Make loadAppointments global for pagination
    window.loadAppointments = loadAppointments;
});
</script>
@endsection