<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'location', 'label' => 'Location', 'sortable' => true],
            ['key' => 'phone_number', 'label' => 'Phone', 'sortable' => true],
            ['key' => 'staff_count', 'label' => 'Staff', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->name, 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->slug ?? 'No slug') . '</p>
                    </div>
                </div>',
                'location' => $record->city ? 
                    '<div>
                        <p class="font-medium">' . $record->city . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">City</p>
                    </div>' : 
                    '<span class="text-gray-500">No location</span>',
                'phone_number' => $record->phone_number ? 
                    '<div class="font-mono text-sm">' . $record->phone_number . '</div>' : 
                    '<span class="text-gray-500">No phone</span>',
                'staff_count' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . 
                    (isset($record->staff) ? $record->staff->count() : 0) . ' members</span>',
                'status' => $record->active ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/branches/{id}'],
            ['type' => 'edit', 'url' => '/admin/branches/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/branches/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Branch',
            'url' => '/admin/branches/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Branches"
        description="Manage company branches and locations"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>