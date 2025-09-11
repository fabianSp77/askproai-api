@props([
    'title' => 'Table',
    'description' => '',
    'columns' => [],
    'rows' => [],
    'actions' => [],
    'searchable' => true,
    'filters' => [],
    'bulkActions' => [],
    'createAction' => null
])

<div class="w-full">
    {{-- Mobile-First Header Section --}}
    <div class="px-4 sm:px-6 py-4">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">{{ $title }}</h1>
                @if($description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            @if($createAction)
                <div class="mt-4 sm:mt-0 sm:ml-4">
                    <a href="{{ $createAction['url'] }}" 
                       class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden xs:inline">{{ $createAction['label'] ?? 'Create' }}</span>
                        <span class="xs:hidden">Add</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Mobile-Optimized Filters --}}
    <div class="px-4 sm:px-6 pb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            @if($searchable)
                <div class="relative flex-1 sm:max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="search" 
                           wire:model.live="search"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="Search...">
                </div>
            @endif

            {{-- Mobile Filter Dropdown --}}
            @if(!empty($filters) || !empty($bulkActions))
                <div class="flex gap-2">
                    @if(!empty($filters))
                        <button type="button"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            <span class="hidden sm:inline">Filters</span>
                        </button>
                    @endif

                    @if(!empty($bulkActions))
                        <button type="button"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Actions</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Responsive Table Container --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        {{-- Desktop Table View --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        @foreach($columns as $column)
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                        @if(!empty($actions))
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                            @foreach($columns as $column)
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $row[$column['key']] ?? '-' }}
                                </td>
                            @endforeach
                            @if(!empty($actions))
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        @foreach($actions as $action)
                                            <a href="{{ str_replace('{id}', $row['id'] ?? '', $action['url']) }}" 
                                               class="text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-900 dark:text-{{ $action['color'] ?? 'blue' }}-400 dark:hover:text-{{ $action['color'] ?? 'blue' }}-300">
                                                {{ $action['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + (!empty($actions) ? 1 : 0) }}" 
                                class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div class="sm:hidden">
            @forelse($rows as $row)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                    <div class="space-y-3">
                        @foreach($columns as $index => $column)
                            @if($index < 3)
                                <div class="flex justify-between items-start">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        {{ $column['label'] }}
                                    </span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100 text-right ml-2">
                                        {{ $row[$column['key']] ?? '-' }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                        
                        @if(count($columns) > 3)
                            <details class="group">
                                <summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    Show more
                                </summary>
                                <div class="mt-2 space-y-2">
                                    @foreach($columns as $index => $column)
                                        @if($index >= 3)
                                            <div class="flex justify-between items-start">
                                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    {{ $column['label'] }}
                                                </span>
                                                <span class="text-sm text-gray-900 dark:text-gray-100 text-right ml-2">
                                                    {{ $row[$column['key']] ?? '-' }}
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endif
                        
                        @if(!empty($actions))
                            <div class="pt-3 flex gap-3 justify-end border-t border-gray-200 dark:border-gray-700">
                                @foreach($actions as $action)
                                    <a href="{{ str_replace('{id}', $row['id'] ?? '', $action['url']) }}" 
                                       class="text-sm font-medium text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-900 dark:text-{{ $action['color'] ?? 'blue' }}-400 dark:hover:text-{{ $action['color'] ?? 'blue' }}-300">
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No records found
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if(isset($pagination))
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Previous
                    </button>
                    <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of{' '}
                            <span class="font-medium">97</span> results
                        </p>
                    </div>
                    <div>
                        {{ $pagination ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>