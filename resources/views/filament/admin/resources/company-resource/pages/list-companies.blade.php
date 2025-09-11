<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Company', 'sortable' => true],
            ['key' => 'industry', 'label' => 'Industry', 'sortable' => true],
            ['key' => 'branches_count', 'label' => 'Branches', 'sortable' => true],
            ['key' => 'staff_count', 'label' => 'Staff', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->name, 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->slug ?? 'No slug') . '</p>
                    </div>
                </div>',
                'industry' => $record->industry ?? '<span class="text-gray-500">Not specified</span>',
                'branches_count' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">' . 
                    (isset($record->branches) ? $record->branches->count() : 0) . ' locations</span>',
                'staff_count' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . 
                    (isset($record->staff) ? $record->staff->count() : 0) . ' members</span>',
                'status' => $record->active ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/companies/{id}'],
            ['type' => 'edit', 'url' => '/admin/companies/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/companies/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Company',
            'url' => '/admin/companies/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Companies"
        description="Manage companies and organizations"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>