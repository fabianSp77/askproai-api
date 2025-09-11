<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Integration', 'sortable' => true],
            ['key' => 'type', 'label' => 'Type', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'last_sync', 'label' => 'Last Sync', 'sortable' => true],
            ['key' => 'actions_count', 'label' => 'Actions', 'sortable' => false],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->description ?? 'No description') . '</p>
                    </div>
                </div>',
                'type' => $record->type ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">' . 
                    ucfirst(str_replace('_', ' ', $record->type)) . 
                    '</span>' : 
                    '<span class="text-gray-500">Unknown</span>',
                'status' => match($record->status ?? 'active') {
                    'active' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>',
                    'inactive' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
                    'error' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Error</span>',
                    'syncing' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Syncing</span>',
                    default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">' . ucfirst($record->status) . '</span>',
                },
                'last_sync' => isset($record->last_sync_at) ? '<div>
                    <p class="font-medium">' . $record->last_sync_at->format('M j, Y') . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->last_sync_at->format('g:i A') . '</p>
                </div>' : '<span class="text-gray-500">Never synced</span>',
                'actions_count' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ' . ($record->actions_count ?? 0) . ' actions
                </span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/integrations/{id}'],
            ['type' => 'edit', 'url' => '/admin/integrations/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/integrations/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Integration',
            'url' => '/admin/integrations/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'sync', 'label' => 'Force Sync'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Integrations"
        description="Manage external service integrations"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>