<div class="space-y-6">
    {{-- Overview Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-user-group class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Agents</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['total_agents'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-phone class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefonnummern</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['phone_numbers'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-building-office class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Zugeordnete Filialen</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['branches_mapped'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-heroicon-o-wrench-screwdriver class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Auto-Fixes</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $summary['auto_fixes'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                Import Details
            </h3>
            
            <div class="space-y-4">
                @foreach($summary['details'] ?? [] as $detail)
                    <div class="border-l-4 border-primary-500 pl-4 py-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $detail['agent_name'] }}
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Agent ID: {{ $detail['agent_id'] }}
                                </p>
                            </div>
                            
                            <div class="text-right">
                                @if($detail['branch_id'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        Filiale zugeordnet
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                        Keine Filiale
                                    </span>
                                @endif
                                
                                @if($detail['auto_fix_config'] ?? false)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        Auto-Fix
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        @if(!empty($detail['prompt']))
                            <div class="mt-2">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    Prompt wird aktualisiert ({{ Str::limit($detail['prompt'], 100) }})
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Warnings --}}
            @if(($summary['total_agents'] ?? 0) > ($summary['branches_mapped'] ?? 0))
                <div class="mt-6 rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Nicht alle Agents sind Filialen zugeordnet
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <p>
                                    {{ ($summary['total_agents'] ?? 0) - ($summary['branches_mapped'] ?? 0) }} 
                                    Agent(s) haben keine Filialzuordnung. Diese können später manuell zugeordnet werden.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Success Info --}}
            <div class="mt-6 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                            Bereit für Import
                        </h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                            <p>Die folgenden Aktionen werden durchgeführt:</p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Agents werden den Filialen zugeordnet</li>
                                <li>Telefonnummern werden synchronisiert</li>
                                <li>Webhook-Konfigurationen werden korrigiert</li>
                                <li>Agent-Prompts werden aktualisiert</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>