<x-filament-panels::page>
    @php
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'call_details', 'label' => 'Call Details', 'sortable' => true],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true],
            ['key' => 'analysis', 'label' => 'AI Analysis', 'sortable' => true],
            ['key' => 'outcome', 'label' => 'Outcome', 'sortable' => true],
            ['key' => 'date', 'label' => 'Date', 'sortable' => true],
        ];
        
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'call_details' => '<div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">' . ($record->from_number ?? 'Unknown') . '</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->customer ? $record->customer->name : 'Unknown caller') . '</p>
                    </div>
                </div>',
                'duration' => '<div>
                    <p class="font-medium">' . $record->formatted_duration . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . ($record->duration_sec ? $record->duration_sec . 's total' : '0s') . '</p>
                </div>',
                'analysis' => $record->intent ? '<div>
                    <p class="font-medium">' . ucfirst(str_replace('_', ' ', $record->intent)) . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Intent: ' . ($record->confidence ? round($record->confidence * 100) . '% confidence' : 'No confidence') . '</p>
                </div>' : '<span class="text-gray-500">No analysis</span>',
                'outcome' => $record->hasAppointment() ? 
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Appointment Booked</span>' :
                    match($record->status) {
                        'completed' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Call Completed</span>',
                        'failed' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Call Failed</span>',
                        'transferred' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Transferred</span>',
                        default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">No Outcome</span>',
                    },
                'date' => $record->start_timestamp ? '<div>
                    <p class="font-medium">' . $record->start_timestamp->format('M j, Y') . '</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . $record->start_timestamp->format('g:i A') . '</p>
                </div>' : '<span class="text-gray-500">Unknown</span>',
            ];
        }
        
        $actions = [
            ['type' => 'view', 'url' => '/admin/enhanced-calls/{id}'],
            ['type' => 'edit', 'url' => '/admin/enhanced-calls/{id}/edit'],
            ['type' => 'delete', 'url' => '/admin/enhanced-calls/{id}'],
        ];
        
        // Create action removed - handled by Filament's getHeaderActions()
        $createAction = [
            'label' => 'New Enhanced Call',
            'url' => '/admin/enhanced-calls/create'
        ];
        
        $bulkActions = [
            ['value' => 'delete', 'label' => 'Delete Selected'],
            ['value' => 'analyze', 'label' => 'Re-analyze'],
            ['value' => 'export', 'label' => 'Export to CSV'],
        ];
    @endphp
    
    <x-admin.flowbite-table
        title="Enhanced Calls"
        description="Advanced call analytics and insights"
        
        
        :columns="$columns"
        :rows="$rows"
        :actions="$actions"
        :createAction="$createAction"
        :bulkActions="$bulkActions"
        :searchable="true"
        :showTitle="false"
    />
</x-filament-panels::page>