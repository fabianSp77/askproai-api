<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Suche
                    </label>
                    <input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="searchTerm"
                        placeholder="Berechtigung suchen..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                {{-- Module Filter --}}
                <div>
                    <label for="module" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Modul
                    </label>
                    <select
                        id="module"
                        wire:model.live="selectedModule"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Alle Module</option>
                        @foreach($this->modules as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Show Only Assigned --}}
                <div class="flex items-end">
                    <label class="inline-flex items-center">
                        <input
                            type="checkbox"
                            wire:model.live="showOnlyAssigned"
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                        />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Nur zugewiesene
                        </span>
                    </label>
                </div>

                {{-- Clear Filters --}}
                <div class="flex items-end">
                    <button
                        wire:click="clearFilters"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Filter zurücksetzen
                    </button>
                </div>
            </div>
        </div>

        {{-- Matrix Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap sticky left-0 bg-gray-50 dark:bg-gray-700">
                                Berechtigung
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                Modul
                            </th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 text-center font-medium whitespace-nowrap">
                                    <div class="flex flex-col items-center space-y-2">
                                        <span class="text-gray-900 dark:text-gray-100">
                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                        <button
                                            wire:click="toggleAllForRole({{ $role->id }})"
                                            class="text-xs px-2 py-1 rounded bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-900/40 transition"
                                            title="Alle umschalten"
                                        >
                                            Alle
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-center font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $currentModule = null;
                        @endphp

                        @forelse($matrix as $index => $row)
                            @if($currentModule !== $row['module'])
                                @php $currentModule = $row['module']; @endphp
                                <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                                    <td colspan="{{ count($roles) + 3 }}" class="px-4 py-2 font-semibold text-gray-700 dark:text-gray-300">
                                        📁 {{ $currentModule }}
                                    </td>
                                </tr>
                            @endif

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800">
                                    <div>
                                        <div class="font-medium">{{ $row['permission_name'] }}</div>
                                        @if($row['description'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ Str::limit($row['description'], 50) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ring-gray-500/10 bg-gray-50 dark:bg-gray-700">
                                        {{ $row['module'] }}
                                    </span>
                                </td>
                                @foreach($roles as $role)
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            wire:click="togglePermission({{ $row['permission_id'] }}, {{ $role->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all
                                                {{ $row['roles'][$role->id]
                                                    ? 'bg-success-500 hover:bg-success-600 text-white'
                                                    : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-400' }}"
                                            title="{{ $row['roles'][$role->id] ? 'Entfernen' : 'Hinzufügen' }}"
                                        >
                                            @if($row['roles'][$role->id])
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center">
                                    <button
                                        wire:click="toggleAllForPermission({{ $row['permission_id'] }})"
                                        class="text-xs px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                        title="Alle Rollen für diese Berechtigung umschalten"
                                    >
                                        Alle Rollen
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($roles) + 3 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-lg font-medium">Keine Berechtigungen gefunden</p>
                                        <p class="text-sm mt-1">Versuchen Sie, Ihre Filterkriterien anzupassen</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Berechtigungen</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ count($matrix) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rollen</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ count($roles) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Module</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ count($this->modules) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>