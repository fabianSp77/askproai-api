<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
            ['key' => 'phone', 'label' => 'Phone', 'sortable' => true],
            ['key' => 'appointments_count', 'label' => 'Appointments', 'sortable' => true],
            ['key' => 'last_activity', 'label' => 'Last Activity', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            // Get appointment count for this customer
            $appointmentCount = $record->appointments()->count();
            $upcomingCount = $record->appointments()->where('starts_at', '>=', now())->count();
            
            // Get last activity
            $lastActivity = $record->updated_at;
            
            $rows[] = [
                'id' => $record->id,
                'customer_name' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->name ?? 'UN', 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . ($record->name ?? 'Unknown') . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->email ?? 'No email') . '</p>
                    </div>
                </div>',
                'phone' => $record->phone ? 
                    '<div class="font-mono text-sm">' . $record->phone . '</div>' : 
                    '<span class="text-gray-500">No phone</span>',
                'appointments_count' => '<div>
                    <p class="font-medium">' . $appointmentCount . ' total</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $upcomingCount . ' upcoming</p>
                </div>',
                'last_activity' => $lastActivity ? '<div>
                    <p class="font-medium">' . $lastActivity->format('M j, Y') . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $lastActivity->diffForHumans() . '</p>
                </div>' : '<span class="text-gray-500">No activity</span>',
                'status' => $record->created_at && $record->created_at->gt(now()->subMonths(3)) ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">Inactive</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/customers/{id}'],
            ['type' => 'edit', 'url' => '/admin/customers/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/customers/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Customer',
            'url' => '/admin/customers/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Customers"
        description="Manage customer information and profiles"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>