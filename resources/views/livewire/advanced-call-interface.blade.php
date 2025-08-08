<div class="advanced-call-management" x-data="advancedCallData()" x-init="init()">
    {{-- Real-time Status Bar --}}
    <div class="bg-white border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                {{-- Connection Status --}}
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full"
                         :class="realTimeConnected ? 'bg-green-400 animate-pulse' : 'bg-red-400'"></div>
                    <span class="text-sm text-gray-600">
                        <span x-text="realTimeConnected ? 'Live' : 'Offline'"></span>
                        • Last update: {{ $lastUpdate }}
                    </span>
                </div>

                {{-- Queue Stats --}}
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center">
                        <span class="font-medium text-blue-600">{{ $queueStats['in_progress'] ?? 0 }}</span>
                        <span class="text-gray-500 ml-1">active</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-yellow-600">{{ $queueStats['waiting'] ?? 0 }}</span>
                        <span class="text-gray-500 ml-1">waiting</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-red-600">{{ $queueStats['high_priority'] ?? 0 }}</span>
                        <span class="text-gray-500 ml-1">priority</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-2">
                {{-- Voice Note Button --}}
                <button type="button" 
                        @click="toggleVoiceNote()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        :class="voiceNoteActive ? 'border-red-300 bg-red-50 text-red-700' : ''">
                    <span x-text="voiceNoteActive ? '🎤 Recording...' : '🎤 Voice Note'"></span>
                </button>

                {{-- Bulk Actions Toggle --}}
                <button type="button" 
                        wire:click="toggleBulkActionMode"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        :class="@json($bulkActionMode) ? 'border-blue-300 bg-blue-50 text-blue-700' : ''">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 00-2 2m-3 7h3m0 0h3m-3 0v3m0-3V9"></path>
                    </svg>
                    {{ $bulkActionMode ? 'Exit Bulk Mode' : 'Bulk Actions' }}
                </button>

                {{-- Refresh Button --}}
                <button type="button" 
                        wire:click="refreshAll"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    {{-- Filter Presets --}}
    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Quick Filters:</span>
                
                @foreach($filterPresets as $key => $preset)
                    <button type="button"
                            wire:click="applyFilterPreset('{{ $key }}')"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors"
                            :class="activePreset === '{{ $key }}' ? 
                                'bg-blue-100 text-blue-800 ring-1 ring-blue-600' : 
                                'bg-white text-gray-700 hover:bg-gray-100'">
                        <span class="mr-1">{{ $preset['icon'] }}</span>
                        {{ $preset['label'] }}
                    </button>
                @endforeach

                @if(!empty($activeFilters) || $currentView !== 'all')
                    <button type="button"
                            wire:click="clearFilters"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs text-gray-500 hover:text-gray-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Search Bar --}}
            <div class="flex items-center space-x-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           wire:model.debounce.300ms="searchTerm"
                           class="block w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="Search calls, customers, numbers...">
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if($bulkActionMode)
        <div class="bg-blue-50 border-b border-blue-200 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-blue-900">
                        {{ count($selectedCalls) }} call(s) selected
                    </span>
                </div>
                
                <div class="flex items-center space-x-2">
                    {{-- Priority Actions --}}
                    <button type="button"
                            wire:click="bulkUpdatePriority('high')"
                            class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">
                        🔥 High Priority
                    </button>
                    <button type="button"
                            wire:click="bulkUpdatePriority('medium')"
                            class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                        ⚡ Medium Priority
                    </button>
                    <button type="button"
                            wire:click="bulkUpdatePriority('low')"
                            class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                        📋 Normal
                    </button>

                    {{-- Export Action --}}
                    <button type="button"
                            wire:click="exportSelected"
                            class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">
                        📊 Export
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- View Tabs --}}
    <div class="bg-white border-b border-gray-200">
        <nav class="flex px-4" aria-label="Tabs">
            @php
                $tabs = [
                    'all' => ['label' => 'All Calls', 'icon' => '📞'],
                    'active' => ['label' => 'Active', 'icon' => '🟢'],
                    'priority' => ['label' => 'Priority', 'icon' => '🔥'],
                    'completed' => ['label' => 'Completed', 'icon' => '✅'],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                <button type="button"
                        wire:click="switchView('{{ $key }}')"
                        class="py-4 px-6 text-sm font-medium border-b-2 transition-colors {{ $currentView === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <span class="mr-2">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Active Calls Real-time Widget (only show if there are active calls) --}}
    @if(!empty($activeCalls))
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-indigo-200 px-4 py-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-indigo-900">🔴 Live Calls</h3>
                <span class="text-xs text-indigo-600">{{ count($activeCalls) }} active</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($activeCalls as $call)
                    <div class="bg-white rounded-lg p-3 border border-indigo-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $call['customer_name'] }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $call['from_number'] }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $this->getCallPriorityColor($call['priority']) }}">
                                    {{ ucfirst($call['priority']) }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $this->formatDuration($call['duration']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Calls Table --}}
    <div class="bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if($bulkActionMode)
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </th>
                        @endif
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('created_at')">
                            Time
                            @if($sortField === 'created_at')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Phone
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('duration_sec')">
                            Duration
                            @if($sortField === 'duration_sec')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Priority
                        </th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Appointment
                        </th>
                        
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($calls as $call)
                        <tr class="call-card hover:bg-gray-50 transition-colors {{ in_array($call->id, $selectedCalls) ? 'bg-blue-50' : '' }}"
                            data-call-id="{{ $call->id }}"
                            draggable="true"
                            x-data="{ showDetails: false }">
                            
                            @if($bulkActionMode)
                                <td class="px-6 py-4">
                                    <input type="checkbox" 
                                           wire:click="selectCall({{ $call->id }})"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                           {{ in_array($call->id, $selectedCalls) ? 'checked' : '' }}>
                                </td>
                            @endif
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $call->created_at->format('H:i') }}</span>
                                    <span class="text-xs text-gray-500">{{ $call->created_at->format('d.m.Y') }}</span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $call->customer?->name ?? 'Unknown' }}
                                        </div>
                                        @if($call->customer?->email)
                                            <div class="text-sm text-gray-500">{{ $call->customer->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-900">{{ $call->from_number ?? 'Anonymous' }}</span>
                                    @if($call->from_number)
                                        <button type="button"
                                                onclick="navigator.clipboard.writeText('{{ $call->from_number }}')"
                                                class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $this->formatDuration($call->duration_sec) }}
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getCallStatusColor($call->call_status) }}">
                                    {{ ucfirst($call->call_status) }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getCallPriorityColor($call->priority ?? 'normal') }} hover:opacity-80">
                                        {{ ucfirst($call->priority ?? 'normal') }}
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 mt-2 w-32 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                        <div class="py-1">
                                            <button wire:click="updateCallPriority({{ $call->id }}, 'high')"
                                                    class="flex items-center w-full px-3 py-2 text-xs text-gray-700 hover:bg-red-50 hover:text-red-900">
                                                🔥 High Priority
                                            </button>
                                            <button wire:click="updateCallPriority({{ $call->id }}, 'medium')"
                                                    class="flex items-center w-full px-3 py-2 text-xs text-gray-700 hover:bg-yellow-50 hover:text-yellow-900">
                                                ⚡ Medium Priority
                                            </button>
                                            <button wire:click="updateCallPriority({{ $call->id }}, 'low')"
                                                    class="flex items-center w-full px-3 py-2 text-xs text-gray-700 hover:bg-green-50 hover:text-green-900">
                                                📋 Normal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($call->appointment_made)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ Booked
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    {{-- View Details --}}
                                    <a href="/admin/calls/{{ $call->id }}"
                                       class="text-blue-600 hover:text-blue-900 text-xs">
                                        View
                                    </a>
                                    
                                    {{-- Call Back --}}
                                    @if($call->from_number)
                                        <a href="tel:{{ $call->from_number }}"
                                           class="text-green-600 hover:text-green-900 text-xs">
                                            📞 Call
                                        </a>
                                    @endif
                                    
                                    {{-- Quick Actions Dropdown --}}
                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button"
                                                @click="open = !open"
                                                class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                            </svg>
                                        </button>
                                        
                                        <div x-show="open" 
                                             @click.away="open = false"
                                             x-transition
                                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                            <div class="py-1">
                                                <button type="button" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    📝 Add Note
                                                </button>
                                                <button type="button" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    📧 Send Email
                                                </button>
                                                @if($call->customer)
                                                    <a href="/admin/customers/{{ $call->customer->id }}" 
                                                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        👤 View Customer
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $bulkActionMode ? '10' : '9' }}" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">No calls found</p>
                                    <p class="text-sm">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($calls->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200">
                {{ $calls->links() }}
            </div>
        @endif
    </div>

    {{-- Voice Note Modal --}}
    <div x-show="voiceNoteActive" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Voice Note</h3>
                    <button @click="stopVoiceNote()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <div class="flex items-center justify-center w-24 h-24 mx-auto bg-red-100 rounded-full">
                        <svg class="w-12 h-12 text-red-600 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="mb-4">
                    <textarea x-model="voiceNoteText" 
                              class="voice-note-active w-full h-32 p-3 border border-gray-300 rounded-md resize-none"
                              placeholder="Speak now... or type your note here"
                              readonly></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button @click="stopVoiceNote()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="saveVoiceNote()" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Save Note
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function advancedCallData() {
            return {
                realTimeConnected: false,
                voiceNoteActive: false,
                voiceNoteText: '',
                activePreset: null,
                recognition: null,

                init() {
                    // Initialize voice recognition
                    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                        this.recognition = new SpeechRecognition();
                        this.recognition.continuous = true;
                        this.recognition.interimResults = true;
                        this.recognition.lang = 'de-DE';

                        this.recognition.onresult = (event) => {
                            let finalTranscript = '';
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                if (event.results[i].isFinal) {
                                    finalTranscript += event.results[i][0].transcript;
                                }
                            }
                            if (finalTranscript) {
                                this.voiceNoteText += finalTranscript + ' ';
                            }
                        };

                        this.recognition.onerror = (event) => {
                            console.error('Speech recognition error:', event.error);
                        };
                    }

                    // Check real-time connection
                    this.checkRealTimeConnection();
                    setInterval(() => this.checkRealTimeConnection(), 5000);
                },

                checkRealTimeConnection() {
                    if (window.Echo && window.Echo.connector) {
                        this.realTimeConnected = window.Echo.connector.pusher.connection.state === 'connected';
                    }
                },

                toggleVoiceNote() {
                    if (this.voiceNoteActive) {
                        this.stopVoiceNote();
                    } else {
                        this.startVoiceNote();
                    }
                },

                startVoiceNote() {
                    if (this.recognition) {
                        this.voiceNoteActive = true;
                        this.voiceNoteText = '';
                        this.recognition.start();
                    }
                },

                stopVoiceNote() {
                    if (this.recognition) {
                        this.recognition.stop();
                    }
                    this.voiceNoteActive = false;
                },

                saveVoiceNote() {
                    if (this.voiceNoteText.trim()) {
                        // Here you would typically save the note via Livewire
                        @this.call('addVoiceNote', this.voiceNoteText);
                        this.stopVoiceNote();
                    }
                }
            };
        }
    </script>
</div>