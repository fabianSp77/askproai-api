<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($insights as $insight)
            <div class="fi-wi-stats-overview-card relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg p-2 {{ match($insight['color']) {
                        'success' => 'bg-success-50 dark:bg-success-500/10',
                        'warning' => 'bg-warning-50 dark:bg-warning-500/10', 
                        'danger' => 'bg-danger-50 dark:bg-danger-500/10',
                        'info' => 'bg-info-50 dark:bg-info-500/10',
                        default => 'bg-gray-50 dark:bg-gray-500/10'
                    } }}">
                        <x-filament::icon 
                            :icon="$insight['icon']"
                            class="h-5 w-5 {{ match($insight['color']) {
                                'success' => 'text-success-600 dark:text-success-400',
                                'warning' => 'text-warning-600 dark:text-warning-400',
                                'danger' => 'text-danger-600 dark:text-danger-400',
                                'info' => 'text-info-600 dark:text-info-400',
                                default => 'text-gray-600 dark:text-gray-400'
                            } }}"
                        />
                    </div>
                    
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $insight['title'] }}
                        </p>
                        
                        <p class="text-2xl font-semibold {{ match($insight['color']) {
                            'success' => 'text-success-600 dark:text-success-400',
                            'warning' => 'text-warning-600 dark:text-warning-400',
                            'danger' => 'text-danger-600 dark:text-danger-400',
                            'info' => 'text-info-600 dark:text-info-400',
                            default => 'text-gray-900 dark:text-gray-100'
                        } }}">
                            {{ $insight['value'] }}
                        </p>
                        
                        @if($insight['description'])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $insight['description'] }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>