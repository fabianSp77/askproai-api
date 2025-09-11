@props([
    'title' => 'Table',
    'description' => '',
    'columns' => [],
    'rows' => [],
    'actions' => [],
    'searchable' => true,
    'filters' => [],
    'bulkActions' => [],
    'createAction' => null,
    'showTitle' => true  {{-- Show title by default --}}
])

<div class="p-4">
    {{-- Header Section - Only show if explicitly enabled --}}
    @if($showTitle)
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
            @if($description)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        {{-- Table Header Actions --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                {{-- Left Side Actions --}}
                <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    @if($searchable)
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="search" 
                                   wire:model.live="search"
                                   class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Search...">
                        </div>
                    @endif

                    @if(!empty($filters))
                        <div class="flex items-center space-x-2">
                            <button type="button"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filters
                            </button>
                        </div>
                    @endif

                    @if(!empty($bulkActions))
                        <select class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value="">Bulk Actions</option>
                            @foreach($bulkActions as $action)
                                <option value="{{ $action['value'] }}">{{ $action['label'] }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Right Side Actions --}}
                <div class="flex items-center space-x-2">
                    @if($createAction)
                        <a href="{{ $createAction['url'] }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ $createAction['label'] ?? 'Create New' }}
                        </a>
                    @endif
                    
                    <button type="button"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        @if(!empty($bulkActions))
                            <th scope="col" class="p-4">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                </div>
                            </th>
                        @endif
                        
                        @foreach($columns as $column)
                            <th scope="col" class="px-6 py-3">
                                <div class="flex items-center">
                                    {{ $column['label'] }}
                                    @if($column['sortable'] ?? false)
                                        <button type="button" class="ml-1.5">
                                            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 320 512">
                                                <path d="M137.4 41.4c12.5-12.5 32.8-12.5 45.3 0l128 128c9.2 9.2 11.9 22.9 6.9 34.9s-16.6 19.8-29.6 19.8H32c-12.9 0-24.6-7.8-29.6-19.8s-2.2-25.7 6.9-34.9l128-128zm0 429.3l-128-128c-9.2-9.2-11.9-22.9-6.9-34.9s16.6-19.8 29.6-19.8h256c12.9 0 24.6 7.8 29.6 19.8s2.2 25.7-6.9 34.9l-128 128c-12.5 12.5-32.8 12.5-45.3 0z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                        
                        @if(!empty($actions))
                            <th scope="col" class="px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            @if(!empty($bulkActions))
                                <td class="w-4 p-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               value="{{ $row['id'] ?? $index }}"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    </div>
                                </td>
                            @endif
                            
                            @foreach($columns as $column)
                                <td class="px-6 py-4 {{ $column['class'] ?? '' }}">
                                    @if(isset($column['component']))
                                        <x-dynamic-component :component="$column['component']" :value="$row[$column['key']]" :row="$row" />
                                    @else
                                        {!! $row[$column['key']] ?? '' !!}
                                    @endif
                                </td>
                            @endforeach
                            
                            @if(!empty($actions))
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        @foreach($actions as $action)
                                            @if($action['type'] === 'view')
                                                <a href="{{ str_replace('{id}', $row['id'], $action['url']) }}"
                                                   class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                            @elseif($action['type'] === 'edit')
                                                <a href="{{ str_replace('{id}', $row['id'], $action['url']) }}"
                                                   class="p-2 text-gray-500 hover:text-yellow-600 dark:text-gray-400 dark:hover:text-yellow-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                            @elseif($action['type'] === 'delete')
                                                <button type="button"
                                                        onclick="confirmDelete('{{ $row['id'] }}')"
                                                        class="p-2 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + (!empty($bulkActions) ? 1 : 0) + (!empty($actions) ? 1 : 0) }}" 
                                class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">No data found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($pagination))
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $pagination }}
            </div>
        @endif
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        // Trigger delete action
        console.log('Delete item:', id);
    }
}
</script>