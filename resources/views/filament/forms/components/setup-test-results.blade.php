<div class="space-y-4">
    @if(empty($validationResults))
        <div class="text-center py-8">
            <x-heroicon-o-clipboard-document-check class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">Run tests to validate your configuration</p>
        </div>
    @else
        {{-- Cal.com Test Results --}}
        @if(isset($validationResults['calcom']))
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-calendar class="w-5 h-5" />
                        Cal.com Integration
                    </h4>
                    @if($validationResults['calcom']['valid'])
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            Valid
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                            Invalid
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $validationResults['calcom']['message'] }}
                </p>
                @if(isset($validationResults['calcom']['user']['email']))
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        Connected as: {{ $validationResults['calcom']['user']['email'] }}
                    </p>
                @endif
            </div>
        @endif
        
        {{-- Retell.ai Test Results --}}
        @if(isset($validationResults['retell']))
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-phone class="w-5 h-5" />
                        Retell.ai Integration
                    </h4>
                    @if($validationResults['retell']['valid'])
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            Valid
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                            Invalid
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $validationResults['retell']['message'] }}
                </p>
                @if(isset($validationResults['retell']['agents']) && count($validationResults['retell']['agents']) > 0)
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        {{ count($validationResults['retell']['agents']) }} agents available
                    </p>
                @endif
            </div>
        @endif
        
        {{-- Test Call Results --}}
        @if(isset($validationResults['test_call']))
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-phone-arrow-down-left class="w-5 h-5" />
                        Test Call Simulation
                    </h4>
                    @if($validationResults['test_call']['success'])
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            Success
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                            Failed
                        </span>
                    @endif
                </div>
                
                @if(isset($validationResults['test_call']['results']))
                    <div class="space-y-1 mt-3">
                        @foreach($validationResults['test_call']['results'] as $check => $passed)
                            <div class="flex items-center gap-2 text-sm">
                                @if($passed)
                                    <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                @else
                                    <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                @endif
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ str_replace('_', ' ', ucfirst($check)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                    {{ $validationResults['test_call']['message'] }}
                </p>
            </div>
        @endif
    @endif
    
    {{-- Configuration Summary --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mt-6">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Configuration Summary</h4>
        <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Cal.com API</span>
                <span class="font-medium {{ isset($validationResults['calcom']['valid']) && $validationResults['calcom']['valid'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                    {{ isset($validationResults['calcom']['valid']) && $validationResults['calcom']['valid'] ? 'Configured' : 'Not configured' }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Retell.ai API</span>
                <span class="font-medium {{ isset($validationResults['retell']['valid']) && $validationResults['retell']['valid'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                    {{ isset($validationResults['retell']['valid']) && $validationResults['retell']['valid'] ? 'Configured' : 'Not configured' }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Phone Numbers</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ count($this->data['phone_numbers'] ?? []) }} configured
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Knowledge Base</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ !empty($this->data['company_description']) || !empty($this->data['services_overview']) ? 'Configured' : 'Not configured' }}
                </span>
            </div>
        </div>
    </div>
</div>