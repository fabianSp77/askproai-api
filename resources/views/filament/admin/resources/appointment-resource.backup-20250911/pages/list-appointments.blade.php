<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
            ['key' => 'service_name', 'label' => 'Service', 'sortable' => true],
            ['key' => 'staff_name', 'label' => 'Staff', 'sortable' => true],
            ['key' => 'starts_at', 'label' => 'Date & Time', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'customer_name' => $record->customer ? 
                    '<div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                        strtoupper(substr($record->customer->name, 0, 2)) . 
                        '</div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">' . $record->customer->name . '</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->customer->email . '</p>
                        </div>
                    </div>' : 
                    '<span class="text-gray-500">No customer</span>',
                'service_name' => $record->service ? 
                    '<div>
                        <p class="font-medium">' . $record->service->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->service->duration_minutes . ' min - €' . number_format($record->service->price_cents / 100, 2) . '</p>
                    </div>' : 
                    '<span class="text-gray-500">No service</span>',
                'staff_name' => $record->staff ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">' . 
                    $record->staff->name . 
                    '</span>' : 
                    '<span class="text-gray-500">Unassigned</span>',
                'starts_at' => '<div>
                    <p class="font-medium">' . $record->starts_at->format('M j, Y') . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->starts_at->format('g:i A') . '</p>
                </div>',
                'status' => match($record->status) {
                    'scheduled' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Scheduled</span>',
                    'completed' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Completed</span>',
                    'cancelled' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Cancelled</span>',
                    'no_show' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">No Show</span>',
                    default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">' . ucfirst($record->status) . '</span>',
                },
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/appointments/{id}'],
            ['type' => 'edit', 'url' => '/admin/appointments/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/appointments/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Appointment',
            'url' => '/admin/appointments/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Appointments"
        description="Manage customer appointments and bookings"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>