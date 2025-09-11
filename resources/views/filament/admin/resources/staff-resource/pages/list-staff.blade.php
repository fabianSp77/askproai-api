<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'staff_name', 'label' => 'Staff Member', 'sortable' => true],
            ['key' => 'branch', 'label' => 'Branch', 'sortable' => true],
            ['key' => 'appointments_today', 'label' => 'Today\'s Appointments', 'sortable' => true],
            ['key' => 'contact', 'label' => 'Contact', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'staff_name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->name, 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->email ?? 'No email') . '</p>
                    </div>
                </div>',
                'branch' => $record->branch ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">' . 
                    $record->branch->name . 
                    '</span>' : 
                    '<span class="text-gray-500">No branch</span>',
                'appointments_today' => '<div>
                    <p class="font-medium">' . (isset($record->appointments) ? $record->appointments()->whereDate('starts_at', today())->count() : 0) . ' appointments</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Today</p>
                </div>',
                'contact' => $record->phone ? 
                    '<div class="font-mono text-sm">' . $record->phone . '</div>' : 
                    '<span class="text-gray-500">No phone</span>',
                'status' => $record->active ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/staff/{id}'],
            ['type' => 'edit', 'url' => '/admin/staff/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/staff/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Staff Member',
            'url' => '/admin/staff/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Staff"
        description="Manage staff members and employees"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>