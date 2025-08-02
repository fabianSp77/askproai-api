@extends('portal.layouts.unified')

@section('page-title', 'Anrufe')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Anrufe
                </h2>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Heute</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-today">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Neu</dt>
                                <dd class="text-lg font-medium text-gray-900" id="new-count">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">In Bearbeitung</dt>
                                <dd class="text-lg font-medium text-gray-900" id="in-progress">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Aktion erforderlich</dt>
                                <dd class="text-lg font-medium text-gray-900" id="requires-action">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Rückrufe heute</dt>
                                <dd class="text-lg font-medium text-gray-900" id="callbacks-today">-</dd>
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
                        <label for="status-filter" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Alle Status</option>
                            <option value="new">Neu</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="requires_action">Aktion erforderlich</option>
                            <option value="callback_scheduled">Rückruf geplant</option>
                            <option value="completed">Abgeschlossen</option>
                        </select>
                    </div>
                    <div>
                        <label for="date-from" class="block text-sm font-medium text-gray-700">Von Datum</label>
                        <input type="date" id="date-from" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="date-to" class="block text-sm font-medium text-gray-700">Bis Datum</label>
                        <input type="date" id="date-to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="search-filter" class="block text-sm font-medium text-gray-700">Suche</label>
                        <input type="text" id="search-filter" placeholder="Telefon, Name..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Calls List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Anrufliste
                </h3>
            </div>
            <div id="calls-container">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Anrufe werden geladen...</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 hidden">
            <!-- Pagination will be inserted here -->
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let filters = {
        status: '',
        date_from: '',
        date_to: '',
        search: ''
    };

    // Load initial data
    loadCalls();
    loadStats();

    // Setup filter listeners
    document.getElementById('status-filter').addEventListener('change', function() {
        filters.status = this.value;
        currentPage = 1;
        loadCalls();
    });

    document.getElementById('date-from').addEventListener('change', function() {
        filters.date_from = this.value;
        currentPage = 1;
        loadCalls();
    });

    document.getElementById('date-to').addEventListener('change', function() {
        filters.date_to = this.value;
        currentPage = 1;
        loadCalls();
    });

    document.getElementById('search-filter').addEventListener('input', debounce(function() {
        filters.search = this.value;
        currentPage = 1;
        loadCalls();
    }, 300));

    function loadStats() {
        // For now, we'll calculate stats from the loaded calls
        // In a real app, this would be a separate API endpoint
    }

    function loadCalls(page = 1) {
        currentPage = page;
        
        const params = new URLSearchParams({
            page: currentPage,
            ...filters
        });

        // TEMPORARY: Use public endpoint for testing
        fetch('/business/api/public/calls?' + new URLSearchParams({
            page: currentPage,
            ...filters
        }), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            renderCalls(data.data || []);
            renderPagination(data);
            updateStats(data.data || []);
        })
        .catch(error => {
            console.error('Error loading calls:', error);
            document.getElementById('calls-container').innerHTML = `
                <div class="text-center py-12">
                    <p class="text-red-600">Fehler beim Laden der Anrufe: ${error.message}</p>
                </div>
            `;
        });
    }

    function updateStats(calls) {
        // Simple client-side calculation for now
        const today = new Date().toISOString().split('T')[0];
        const todayCalls = calls.filter(call => call.created_at.startsWith(today));
        const newCalls = calls.filter(call => call.status === 'new');
        const inProgressCalls = calls.filter(call => call.status === 'in_progress');
        const requiresActionCalls = calls.filter(call => call.status === 'requires_action');
        const callbacksToday = calls.filter(call => 
            call.status === 'callback_scheduled' && 
            call.callback_scheduled_at && 
            call.callback_scheduled_at.startsWith(today)
        );

        document.getElementById('total-today').textContent = todayCalls.length;
        document.getElementById('new-count').textContent = newCalls.length;
        document.getElementById('in-progress').textContent = inProgressCalls.length;
        document.getElementById('requires-action').textContent = requiresActionCalls.length;
        document.getElementById('callbacks-today').textContent = callbacksToday.length;
    }

    function renderCalls(calls) {
        const container = document.getElementById('calls-container');
        
        if (calls.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Keine Anrufe gefunden</p>
                </div>
            `;
            return;
        }

        const callsList = calls.map(call => {
            const date = new Date(call.created_at);
            const dateStr = date.toLocaleDateString('de-DE');
            const timeStr = date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
            
            const statusColors = {
                'new': 'bg-blue-100 text-blue-800',
                'in_progress': 'bg-yellow-100 text-yellow-800',
                'requires_action': 'bg-red-100 text-red-800',
                'callback_scheduled': 'bg-green-100 text-green-800',
                'completed': 'bg-gray-100 text-gray-800'
            };
            
            const statusLabels = {
                'new': 'Neu',
                'in_progress': 'In Bearbeitung',
                'requires_action': 'Aktion erforderlich',
                'callback_scheduled': 'Rückruf geplant',
                'completed': 'Abgeschlossen'
            };

            const duration = call.duration_sec ? 
                `${Math.floor(call.duration_sec / 60)}:${(call.duration_sec % 60).toString().padStart(2, '0')}` : 
                '-';

            return `
                <li class="border-b border-gray-200">
                    <a href="/business/calls/${call.id}" class="block hover:bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <div class="flex-shrink-0">
                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                ${call.phone_number || 'Unbekannte Nummer'}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ${call.customer?.name || 'Unbekannter Kunde'} • ${duration}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ${dateStr} um ${timeStr}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[call.status] || 'bg-gray-100 text-gray-800'}">
                                                ${statusLabels[call.status] || call.status}
                                            </span>
                                        </div>
                                    </div>
                                    ${call.assigned_to ? `
                                        <div class="mt-1 text-sm text-gray-500">
                                            Zugewiesen an: ${call.assigned_to.name}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        }).join('');

        container.innerHTML = `<ul>${callsList}</ul>`;
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
                return `<a href="#" onclick="loadCalls(${data.current_page - 1}); return false;" class="${link.url ? '' : 'opacity-50 cursor-not-allowed'} relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Zurück</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>`;
            }
            
            if (link.label === 'Next &raquo;') {
                return `<a href="#" onclick="loadCalls(${data.current_page + 1}); return false;" class="${link.url ? '' : 'opacity-50 cursor-not-allowed'} relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Weiter</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>`;
            }
            
            return `<a href="#" onclick="loadCalls(${link.label}); return false;" class="${link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                ${link.label}
            </a>`;
        }).join('');
        
        container.innerHTML = `
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="#" onclick="loadCalls(${data.current_page - 1}); return false;" class="${data.current_page === 1 ? 'opacity-50 cursor-not-allowed' : ''} relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Zurück
                </a>
                <a href="#" onclick="loadCalls(${data.current_page + 1}); return false;" class="${data.current_page === data.last_page ? 'opacity-50 cursor-not-allowed' : ''} ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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

    // Make loadCalls global for pagination
    window.loadCalls = loadCalls;
});
</script>
@endpush
@endsection