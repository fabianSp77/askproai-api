@extends('portal.layouts.app')

@section('content')
<div class="py-12" x-data="callsTable()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- Header mit Statistiken --}}
        <div class="mb-8">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Anrufe</h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Übersicht aller eingegangenen Anrufe
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    {{-- Column Settings Button --}}
                    <button 
                        @click="showColumnSettings = true"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Spalten anpassen
                    </button>
                    
                    {{-- Bulk Export Button --}}
                    <div x-show="selectedCalls.length > 0" x-cloak>
                        <button 
                            @click="showBulkExportModal = true"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span x-text="`${selectedCalls.length} exportieren`"></span>
                        </button>
                    </div>
                    
                    {{-- Standard Export Button --}}
                    <a href="{{ route('business.calls.export') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Alle exportieren
                    </a>
                </div>
            </div>
            
            {{-- View Templates --}}
            <div class="mt-4 flex space-x-2">
                <span class="text-sm text-gray-500">Schnellansichten:</span>
                @foreach($viewTemplates as $key => $template)
                <button 
                    @click="applyViewTemplate('{{ $key }}')"
                    class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline"
                    title="{{ $template['description'] }}"
                >
                    {{ $template['name'] }}
                </button>
                @endforeach
            </div>
            
            {{-- Statistik-Karten --}}
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Anrufe heute --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Anrufe heute
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['total_today'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Neue Anrufe --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Neue Anrufe
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['new'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aktion erforderlich --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Aktion erforderlich
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['requires_action'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kosten heute (nur für Management) --}}
                @if($canViewCosts)
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Kosten heute
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ number_format($stats['costs_today'] ?? 0, 2, ',', '.') }} €
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Filter (kompakter) --}}
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-3">
                <form method="GET" action="{{ route('business.calls.index') }}" class="flex flex-wrap gap-3 items-end">
                    {{-- Status Filter --}}
                    <div>
                        <label for="status" class="sr-only">Status</label>
                        <select id="status" name="status" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md">
                            <option value="">Alle Status</option>
                            <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Neu</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                            <option value="requires_action" {{ request('status') == 'requires_action' ? 'selected' : '' }}>Aktion erforderlich</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                            <option value="callback_scheduled" {{ request('status') == 'callback_scheduled' ? 'selected' : '' }}>Rückruf geplant</option>
                        </select>
                    </div>

                    {{-- Age Filter --}}
                    <div>
                        <label for="age" class="sr-only">Alter</label>
                        <select id="age" name="age" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md">
                            <option value="">Alle Zeiten</option>
                            <option value="1h" {{ request('age') == '1h' ? 'selected' : '' }}>Letzte Stunde</option>
                            <option value="24h" {{ request('age') == '24h' ? 'selected' : '' }}>Letzte 24 Stunden</option>
                            <option value="7d" {{ request('age') == '7d' ? 'selected' : '' }}>Letzte 7 Tage</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div class="flex-1">
                        <label for="search" class="sr-only">Suche</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               placeholder="Suche nach Name, Telefon oder Anliegen..."
                               value="{{ request('search') }}"
                               class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filter
                        </button>
                        @if(request()->hasAny(['status', 'age', 'search']))
                        <a href="{{ route('business.calls.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zurücksetzen
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabelle --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-3 text-left">
                                <input type="checkbox" 
                                       @change="toggleAllCalls($event)" 
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </th>
                            
                            @foreach($columnPrefs as $key => $column)
                                @if($column['visible'])
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider {{ $column['width'] ?? '' }}">
                                    {{ $column['label'] }}
                                </th>
                                @endif
                            @endforeach
                            
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($calls as $call)
                            <tr class="hover:bg-gray-50 cursor-pointer" 
                                @click="if(!$event.target.closest('input') && !$event.target.closest('button') && !$event.target.closest('a')) { window.location.href = '{{ route('business.calls.show', $call->id) }}' }">
                                
                                <td class="sticky left-0 bg-white px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           value="{{ $call->id }}" 
                                           @change="toggleCall({{ $call->id }})"
                                           :checked="selectedCalls.includes({{ $call->id }})"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </td>
                                
                                {{-- Render columns in the same order as headers --}}
                                @foreach($columnPrefs as $key => $column)
                                    @if($column['visible'])
                                        <x-call-table-cell 
                                            :call="$call" 
                                            :column="$column" 
                                            :key="$key"
                                            :canViewCosts="$canViewCosts" />
                                    @endif
                                @endforeach
                                
                                {{-- Actions --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <x-copy-call-quick :call="$call" />
                                        <x-call-email-actions :call="$call" />
                                        <a href="{{ route('business.calls.show', $call->id) }}" 
                                           class="text-indigo-600 hover:text-indigo-900"
                                           title="Details anzeigen">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="px-6 py-8 text-center text-sm text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <p class="mt-2">Keine Anrufe gefunden</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $calls->withQueryString()->links() }}
        </div>
    </div>

    {{-- Column Settings Modal --}}
    <div x-show="showColumnSettings" x-cloak
         @click.away="showColumnSettings = false"
         class="fixed z-50 inset-0 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showColumnSettings"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showColumnSettings"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Spalten anpassen
                    </h3>
                    
                    <div class="space-y-3">
                        <p class="text-sm text-gray-500 mb-4">
                            Wählen Sie die Spalten aus, die in der Übersicht angezeigt werden sollen. Ziehen Sie die Einträge, um die Reihenfolge zu ändern.
                        </p>
                        
                        <div class="space-y-2" id="sortable-columns">
                            @foreach($columnPrefs as $key => $column)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-move"
                                 data-column="{{ $key }}">
                                <svg class="h-5 w-5 text-gray-400 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <label class="flex items-center flex-1 cursor-pointer">
                                    <input type="checkbox" 
                                           x-model="columns.{{ $key }}.visible"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-3">
                                    <span class="text-sm font-medium text-gray-700">{{ $column['label'] }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="saveColumnSettings()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Speichern
                    </button>
                    <button type="button" 
                            @click="showColumnSettings = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Export Modal (existing) --}}
    <div x-show="showBulkExportModal" x-cloak
         @click.away="showBulkExportModal = false"
         class="fixed z-10 inset-0 overflow-y-auto">
        <!-- Existing bulk export modal content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function callsTable() {
    return {
        selectedCalls: [],
        showBulkExportModal: false,
        showColumnSettings: false,
        exportFormat: 'csv',
        columns: @json($columnPrefs),
        sortable: null,
        
        init() {
            // Initialize sortable for column reordering
            this.sortable = new Sortable(document.getElementById('sortable-columns'), {
                animation: 150,
                ghostClass: 'bg-blue-100',
                onEnd: (evt) => {
                    // Update order based on new positions
                    const items = evt.to.children;
                    for (let i = 0; i < items.length; i++) {
                        const columnKey = items[i].dataset.column;
                        if (this.columns[columnKey]) {
                            this.columns[columnKey].order = i + 1;
                        }
                    }
                }
            });
        },
        
        toggleAllCalls(event) {
            if (event.target.checked) {
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selectedCalls = Array.from(checkboxes).map(cb => parseInt(cb.value));
            } else {
                this.selectedCalls = [];
            }
        },
        
        toggleCall(callId) {
            const index = this.selectedCalls.indexOf(callId);
            if (index > -1) {
                this.selectedCalls.splice(index, 1);
            } else {
                this.selectedCalls.push(callId);
            }
        },
        
        saveColumnSettings() {
            fetch('{{ route("business.calls.column-preferences") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    columns: this.columns
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },
        
        applyViewTemplate(template) {
            if (confirm('Möchten Sie diese Ansicht laden? Ihre aktuellen Spalteneinstellungen werden überschrieben.')) {
                fetch('{{ route("business.calls.apply-view-template") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        template: template
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                });
            }
        },
        
        exportSelected() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("business.calls.export.bulk") }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = this.exportFormat;
            form.appendChild(formatInput);
            
            this.selectedCalls.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'call_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });
            
            document.body.appendChild(form);
            form.submit();
            
            this.showBulkExportModal = false;
        }
    }
}
</script>
@endsection