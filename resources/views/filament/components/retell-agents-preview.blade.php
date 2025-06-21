<div class="space-y-4">
    @forelse($agents as $agent)
        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $agent['agent_name'] ?? 'Unbenannter Agent' }}
                        </h3>
                        @if($agent['branch'] ?? false)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
                                {{ $agent['branch']['name'] }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-4">
                            <span>Agent ID: {{ $agent['agent_id'] }}</span>
                            <span>•</span>
                            <span>Sprache: {{ $agent['language'] ?? 'Nicht gesetzt' }}</span>
                        </div>
                    </div>

                    {{-- Phone Numbers --}}
                    @if(!empty($agent['phone_numbers']))
                        <div class="mt-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefonnummern:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($agent['phone_numbers'] as $phone)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        <x-heroicon-o-phone class="w-3 h-3 mr-1" />
                                        {{ $phone['phone_number'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Validation Results --}}
                    @if(isset($validationResults[$agent['agent_id']]))
                        @php
                            $validation = $validationResults[$agent['agent_id']];
                            $issueCount = count($validation['issues']);
                            $warningCount = count($validation['warnings']);
                            $autoFixable = $validation['auto_fixable'];
                        @endphp
                        
                        <div class="mt-3 space-y-2">
                            @if($validation['valid'])
                                <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                                    <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                                    Konfiguration ist korrekt
                                </div>
                            @else
                                <div class="flex items-center text-sm text-red-600 dark:text-red-400">
                                    <x-heroicon-o-x-circle class="w-4 h-4 mr-1" />
                                    {{ $issueCount }} {{ Str::plural('Problem', $issueCount) }} gefunden
                                    @if($autoFixable > 0)
                                        <span class="ml-2 text-amber-600 dark:text-amber-400">
                                            ({{ $autoFixable }} automatisch behebbar)
                                        </span>
                                    @endif
                                </div>
                            @endif
                            
                            @if($warningCount > 0)
                                <div class="flex items-center text-sm text-amber-600 dark:text-amber-400">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 mr-1" />
                                    {{ $warningCount }} {{ Str::plural('Warnung', $warningCount) }}
                                </div>
                            @endif

                            {{-- Show issues details --}}
                            @if(!$validation['valid'] && !empty($validation['issues']))
                                <div class="mt-2 space-y-1">
                                    @foreach(array_slice($validation['issues'], 0, 3) as $issue)
                                        <div class="text-xs text-gray-600 dark:text-gray-400 pl-5">
                                            • {{ $issue['message'] }}
                                            @if($issue['auto_fixable'] ?? false)
                                                <span class="text-green-600 dark:text-green-400">(auto-fix)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if(count($validation['issues']) > 3)
                                        <div class="text-xs text-gray-500 dark:text-gray-500 pl-5">
                                            ... und {{ count($validation['issues']) - 3 }} weitere
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Selection Toggle --}}
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               wire:model="importSelections.{{ $agent['agent_id'] }}"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-12">
            <x-heroicon-o-phone-x-mark class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine Agents gefunden</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Es wurden keine Retell Agents für dieses Unternehmen gefunden.
            </p>
        </div>
    @endforelse
</div>