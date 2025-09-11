<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'user_name', 'label' => 'User', 'sortable' => true],
            ['key' => 'role', 'label' => 'Role', 'sortable' => true],
            ['key' => 'last_login', 'label' => 'Last Login', 'sortable' => true],
            ['key' => 'verified', 'label' => 'Verified', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'user_name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->name, 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->email . '</p>
                    </div>
                </div>',
                'role' => $record->roles->first() ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">' . 
                    ucfirst($record->roles->first()->name) . 
                    '</span>' : 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">No Role</span>',
                'last_login' => isset($record->last_login_at) ? '<div>
                    <p class="font-medium">' . $record->last_login_at->format('M j, Y') . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->last_login_at->format('g:i A') . '</p>
                </div>' : '<span class="text-gray-500">Never</span>',
                'verified' => $record->email_verified_at ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Verified</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>',
                'status' => $record->active ?? true ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/users/{id}'],
            ['type' => 'edit', 'url' => '/admin/users/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/users/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New User',
            'url' => '/admin/users/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Users"
        description="Manage system users and accounts"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>