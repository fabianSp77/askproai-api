<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Actions --}}
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Feature Flag Control Center</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Manage feature rollouts with granular control and monitoring
                </p>
            </div>
            <div class="flex gap-2">
                <x-filament::button
                    wire:click="refreshFlags"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-arrow-path"
                >
                    Refresh
                </x-filament::button>
                
                <x-filament::button
                    wire:click="emergencyKillSwitch"
                    wire:confirm="Are you absolutely sure? This will disable ALL feature flags immediately!"
                    size="sm"
                    color="danger"
                    icon="heroicon-m-exclamation-triangle"
                >
                    Emergency Kill Switch
                </x-filament::button>
            </div>
        </div>
        
        {{-- Feature Flags Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Feature
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rollout %
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Override
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($flags as $flag)
                            @php
                                $hasOverride = collect($companyOverrides)->contains('feature_key', $flag->key);
                                $override = collect($companyOverrides)->firstWhere('feature_key', $flag->key);
                            @endphp
                            
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $flag->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $flag->key }}
                                        </div>
                                        @if($flag->description)
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $flag->description }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <button
                                        wire:click="toggleFlag('{{ $flag->key }}')"
                                        @class([
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                            'bg-green-500' => $flag->enabled,
                                            'bg-gray-300 dark:bg-gray-700' => !$flag->enabled,
                                        ])
                                    >
                                        <span @class([
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            'translate-x-5' => $flag->enabled,
                                            'translate-x-0' => !$flag->enabled,
                                        ])></span>
                                    </button>
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    @if($editStates[$flag->key] ?? false)
                                        <div class="flex items-center gap-2 justify-center">
                                            <input
                                                type="number"
                                                wire:model="rolloutPercentages.{{ $flag->key }}"
                                                min="0"
                                                max="100"
                                                class="w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-m-check"
                                                wire:click="updateRollout('{{ $flag->key }}')"
                                                color="success"
                                                size="sm"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-m-x-mark"
                                                wire:click="$set('editStates.{{ $flag->key }}', false)"
                                                color="gray"
                                                size="sm"
                                            />
                                        </div>
                                    @else
                                        <button
                                            wire:click="$set('editStates.{{ $flag->key }}', true)"
                                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600"
                                        >
                                            {{ $flag->rollout_percentage }}%
                                        </button>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    @if($hasOverride)
                                        <div class="flex items-center justify-center gap-2">
                                            <span @class([
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' => $override->enabled,
                                                'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' => !$override->enabled,
                                            ])>
                                                {{ $override->enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                            <x-filament::icon-button
                                                icon="heroicon-m-x-mark"
                                                wire:click="removeCompanyOverride('{{ $flag->key }}')"
                                                color="gray"
                                                size="sm"
                                                title="Remove override"
                                            />
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                wire:click="createCompanyOverride('{{ $flag->key }}', true)"
                                                class="text-xs text-green-600 hover:underline"
                                            >
                                                Enable
                                            </button>
                                            <span class="text-gray-400">|</span>
                                            <button
                                                wire:click="createCompanyOverride('{{ $flag->key }}', false)"
                                                class="text-xs text-red-600 hover:underline"
                                            >
                                                Disable
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 text-right">
                                    <x-filament::button
                                        wire:click="showFlagStats('{{ $flag->key }}')"
                                        size="sm"
                                        color="gray"
                                        icon="heroicon-m-chart-bar"
                                    >
                                        Stats
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Usage Statistics Modal --}}
        @if($showStats && $selectedFlag)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Usage Statistics: {{ $selectedFlag }}
                            </h3>
                            <button
                                wire:click="closeStats"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <x-heroicon-m-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($flagStats['total_evaluations'] ?? 0) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Total Evaluations (24h)
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($flagStats['enabled_count'] ?? 0) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Enabled Results
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $flagStats['unique_companies'] ?? 0 }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Unique Companies
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    @if(($flagStats['total_evaluations'] ?? 0) > 0)
                                        {{ round(($flagStats['enabled_count'] ?? 0) / $flagStats['total_evaluations'] * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Enabled Rate
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($flagStats['by_reason']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    Evaluation Reasons
                                </h4>
                                <div class="space-y-2">
                                    @foreach($flagStats['by_reason'] as $reason => $count)
                                        <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-900 rounded">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ str_replace('_', ' ', ucfirst($reason)) }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ number_format($count) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>