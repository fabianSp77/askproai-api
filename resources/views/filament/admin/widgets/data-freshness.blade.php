<div>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                Data Freshness Monitor
            </x-slot>
            
            <x-slot name="description">
                Monitoring data synchronization status across all entities
            </x-slot>
            
            <div class="space-y-4">
            @foreach($this->getDataFreshness() as $key => $data)
                <div class="flex items-center justify-between p-3 rounded-lg
                    @if($data['status'] === 'fresh') bg-green-50 dark:bg-green-900/20
                    @elseif($data['status'] === 'recent') bg-blue-50 dark:bg-blue-900/20
                    @elseif($data['status'] === 'stale') bg-yellow-50 dark:bg-yellow-900/20
                    @else bg-red-50 dark:bg-red-900/20
                    @endif">
                    
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @if($data['status'] === 'fresh')
                                <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                            @elseif($data['status'] === 'recent')
                                <x-heroicon-o-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            @elseif($data['status'] === 'stale')
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            @else
                                <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                            @endif
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium
                                @if($data['status'] === 'fresh') text-green-900 dark:text-green-100
                                @elseif($data['status'] === 'recent') text-blue-900 dark:text-blue-100
                                @elseif($data['status'] === 'stale') text-yellow-900 dark:text-yellow-100
                                @else text-red-900 dark:text-red-100
                                @endif">
                                {{ $data['entity'] }}
                            </h4>
                            <p class="text-sm
                                @if($data['status'] === 'fresh') text-green-700 dark:text-green-300
                                @elseif($data['status'] === 'recent') text-blue-700 dark:text-blue-300
                                @elseif($data['status'] === 'stale') text-yellow-700 dark:text-yellow-300
                                @else text-red-700 dark:text-red-300
                                @endif">
                                {{ $data['message'] }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-xs
                            @if($data['status'] === 'fresh') text-green-600 dark:text-green-400
                            @elseif($data['status'] === 'recent') text-blue-600 dark:text-blue-400
                            @elseif($data['status'] === 'stale') text-yellow-600 dark:text-yellow-400
                            @else text-red-600 dark:text-red-400
                            @endif">
                            Last: {{ $data['last_activity'] }}
                        </p>
                    </div>
                </div>
            @endforeach
            
            @if(empty($this->getDataFreshness()))
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-database class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                    <p>No data available to monitor</p>
                </div>
            @endif
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>