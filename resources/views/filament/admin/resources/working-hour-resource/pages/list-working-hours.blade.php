<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'staff_name', 'label' => 'Staff Member', 'sortable' => true],
            ['key' => 'day', 'label' => 'Day', 'sortable' => true],
            ['key' => 'hours', 'label' => 'Working Hours', 'sortable' => true],
            ['key' => 'break_time', 'label' => 'Break Time', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'staff_name' => $record->staff ? '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-teal-500 to-cyan-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">' . 
                    strtoupper(substr($record->staff->name, 0, 2)) . 
                    '</div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . $record->staff->name . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->staff->email ?? 'No email') . '</p>
                    </div>
                </div>' : '<span class="text-gray-500">No staff assigned</span>',
                'day' => $record->day_of_week ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . 
                    ucfirst($record->day_of_week) . 
                    '</span>' : 
                    '<span class="text-gray-500">Not set</span>',
                'hours' => ($record->start_time && $record->end_time) ? '<div>
                    <p class="font-medium">' . $record->start_time . ' - ' . $record->end_time . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Working hours</p>
                </div>' : '<span class="text-gray-500">Not set</span>',
                'break_time' => ($record->break_start && $record->break_end) ? '<div>
                    <p class="font-medium">' . $record->break_start . ' - ' . $record->break_end . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Break time</p>
                </div>' : '<span class="text-gray-500">No break</span>',
                'status' => $record->is_working_day ?? true ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Working Day</span>' :
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Day Off</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/working-hours/{id}'],
            ['type' => 'edit', 'url' => '/admin/working-hours/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/working-hours/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Working Hour',
            'url' => '/admin/working-hours/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Working Hours"
        description="Manage business hours and schedules"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>