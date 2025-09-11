<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Service', 'sortable' => true],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true],
            ['key' => 'price', 'label' => 'Price', 'sortable' => true],
            ['key' => 'bookings_count', 'label' => 'Bookings', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Service</p>
                    </div>
                </div>',
                'duration' => $record->duration_minutes ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . 
                    $record->duration_minutes . ' min</span>' : 
                    '<span class="text-gray-500">Not set</span>',
                'price' => $record->price_cents ? 
                    '<div class="font-semibold text-green-600 dark:text-green-400">€' . number_format($record->price_cents / 100, 2) . '</div>' : 
                    '<span class="text-gray-500">Free</span>',
                'bookings_count' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">' . 
                    (isset($record->appointments) ? $record->appointments->count() : 0) . ' bookings</span>',
                'status' => $record->active ?? true ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/services/{id}'],
            ['type' => 'edit', 'url' => '/admin/services/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/services/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Service',
            'url' => '/admin/services/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Services"
        description="Manage available services and offerings"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>