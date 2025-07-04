<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Global Alerts Toggle --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-bell" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    Global Alerts
                </div>
            </x-slot>
            
            <x-slot name="description">
                Master switch for all billing alerts
            </x-slot>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            @if($globalAlertsEnabled)
                                <div class="h-12 w-12 rounded-full bg-success-100 dark:bg-success-900/20 flex items-center justify-center">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-6 w-6 text-success-600 dark:text-success-400" />
                                </div>
                            @else
                                <div class="h-12 w-12 rounded-full bg-danger-100 dark:bg-danger-900/20 flex items-center justify-center">
                                    <x-filament::icon icon="heroicon-m-x-circle" class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="text-base font-medium text-gray-900 dark:text-white">
                                Alerts are currently 
                                @if($globalAlertsEnabled)
                                    <span class="text-success-600 dark:text-success-400 font-semibold">ENABLED</span>
                                @else
                                    <span class="text-danger-600 dark:text-danger-400 font-semibold">DISABLED</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                @if($globalAlertsEnabled)
                                    You will receive notifications for billing events
                                @else
                                    No billing notifications will be sent
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <x-filament::button
                        wire:click="toggleGlobalAlerts"
                        :color="$globalAlertsEnabled ? 'danger' : 'success'"
                        size="lg"
                        wire:loading.attr="disabled"
                    >
                        <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="toggleGlobalAlerts" />
                        <span wire:loading.remove wire:target="toggleGlobalAlerts">
                            @if($globalAlertsEnabled)
                                <x-filament::icon icon="heroicon-m-power" class="h-5 w-5 mr-2" />
                                Turn Off All Alerts
                            @else
                                <x-filament::icon icon="heroicon-m-power" class="h-5 w-5 mr-2" />
                                Turn On All Alerts
                            @endif
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Alert Configurations --}}
        @if($showConfigurations)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    Alert Configurations
                </div>
            </x-slot>
            
            <x-slot name="description">
                Configure individual alert types and their notification settings
            </x-slot>
            
            <div class="space-y-4">
                @foreach($alertConfigs as $config)
                    <div class="relative overflow-hidden rounded-xl border {{ $config['is_enabled'] ? 'border-success-200 dark:border-success-800/50 bg-success-50/50 dark:bg-success-900/10' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30' }} transition-all duration-200">
                        {{-- Status Indicator Bar --}}
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $config['is_enabled'] ? 'bg-success-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                        
                        <div class="p-6">
                            <div class="space-y-4">
                                {{-- Header with Title and Main Toggle --}}
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0">
                                                @php
                                                    $icon = match($config['alert_type']) {
                                                        'usage_limit' => 'heroicon-o-chart-bar',
                                                        'payment_reminder' => 'heroicon-o-credit-card',
                                                        'subscription_renewal' => 'heroicon-o-arrow-path',
                                                        'overage_warning' => 'heroicon-o-exclamation-triangle',
                                                        'payment_failed' => 'heroicon-o-x-circle',
                                                        'budget_exceeded' => 'heroicon-o-banknotes',
                                                        default => 'heroicon-o-bell'
                                                    };
                                                @endphp
                                                <x-filament::icon :icon="$icon" class="h-6 w-6 {{ $config['is_enabled'] ? 'text-success-600 dark:text-success-400' : 'text-gray-400 dark:text-gray-500' }}" />
                                            </div>
                                            <div>
                                                <h4 class="text-lg font-semibold {{ $config['is_enabled'] ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $this->getAlertTypeLabel($config['alert_type']) }}
                                                </h4>
                                                <p class="mt-1 text-sm {{ $config['is_enabled'] ? 'text-gray-600 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $this->getAlertTypeDescription($config['alert_type']) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Main Toggle Switch --}}
                                    <div class="flex-shrink-0">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                wire:model.live="alertConfigs.{{ $config['alert_type'] }}.is_enabled"
                                                wire:change="updateAlertConfig('{{ $config['alert_type'] }}', { is_enabled: $event.target.checked })"
                                                class="sr-only peer"
                                                @if($config['is_enabled']) checked @endif
                                            >
                                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-success-600"></div>
                                            <span class="ml-3 text-sm font-medium {{ $config['is_enabled'] ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $config['is_enabled'] ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            
                                {{-- Configuration Details (Only show if alert is enabled) --}}
                                @if($config['is_enabled'])
                                <div class="mt-6 pt-6 border-t {{ $config['is_enabled'] ? 'border-success-200 dark:border-success-800/30' : 'border-gray-200 dark:border-gray-700' }}">
                                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                        {{-- Notification Channels --}}
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <x-filament::icon icon="heroicon-o-megaphone" class="h-4 w-4 text-gray-500" />
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Notification Channels
                                                </label>
                                            </div>
                                            <div class="space-y-2 pl-6">
                                                @foreach(['email' => ['Email', 'heroicon-o-envelope'], 'sms' => ['SMS', 'heroicon-o-device-phone-mobile'], 'push' => ['Push', 'heroicon-o-bell-alert']] as $channel => $channelData)
                                                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                                        <input type="checkbox"
                                                            wire:model.live="alertConfigs.{{ $config['alert_type'] }}.notification_channels"
                                                            value="{{ $channel }}"
                                                            @if(in_array($channel, $config['notification_channels'] ?? [])) checked @endif
                                                            wire:change="updateAlertConfig('{{ $config['alert_type'] }}', { notification_channels: getSelectedChannels('{{ $config['alert_type'] }}') })"
                                                            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                        >
                                                        <div class="flex items-center gap-2">
                                                            <x-filament::icon :icon="$channelData[1]" class="h-4 w-4 {{ in_array($channel, $config['notification_channels'] ?? []) ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}" />
                                                            <span class="text-sm {{ in_array($channel, $config['notification_channels'] ?? []) ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                                                {{ $channelData[0] }}
                                                            </span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                        {{-- Recipients --}}
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4 text-gray-500" />
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Recipients
                                                </label>
                                            </div>
                                            <div class="space-y-2 pl-6">
                                                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                                    <input type="checkbox"
                                                        wire:model.live="alertConfigs.{{ $config['alert_type'] }}.notify_primary_contact"
                                                        wire:change="updateAlertConfig('{{ $config['alert_type'] }}', { notify_primary_contact: $event.target.checked })"
                                                        @if($config['notify_primary_contact']) checked @endif
                                                        class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                    >
                                                    <div class="flex items-center gap-2">
                                                        <x-filament::icon icon="heroicon-o-user" class="h-4 w-4 {{ $config['notify_primary_contact'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}" />
                                                        <span class="text-sm {{ $config['notify_primary_contact'] ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                                            Primary Contact
                                                        </span>
                                                    </div>
                                                </label>
                                                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                                    <input type="checkbox"
                                                        wire:model.live="alertConfigs.{{ $config['alert_type'] }}.notify_billing_contact"
                                                        wire:change="updateAlertConfig('{{ $config['alert_type'] }}', { notify_billing_contact: $event.target.checked })"
                                                        @if($config['notify_billing_contact']) checked @endif
                                                        class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                    >
                                                    <div class="flex items-center gap-2">
                                                        <x-filament::icon icon="heroicon-o-credit-card" class="h-4 w-4 {{ $config['notify_billing_contact'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}" />
                                                        <span class="text-sm {{ $config['notify_billing_contact'] ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                                            Billing Contact
                                                        </span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                
                                        {{-- Alert-specific Settings --}}
                                        <div class="space-y-3">
                                            @if(in_array($config['alert_type'], ['usage_limit', 'budget_exceeded']))
                                                <div class="flex items-center gap-2">
                                                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-4 w-4 text-gray-500" />
                                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Alert Thresholds
                                                    </label>
                                                </div>
                                                <div class="pl-6">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input
                                                            type="text"
                                                            wire:model.blur="alertConfigs.{{ $config['alert_type'] }}.thresholds"
                                                            placeholder="75, 90, 100"
                                                            class="text-sm"
                                                        />
                                                    </x-filament::input.wrapper>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        Comma-separated percentages
                                                    </p>
                                                </div>
                                            @elseif(in_array($config['alert_type'], ['payment_reminder', 'subscription_renewal']))
                                                <div class="flex items-center gap-2">
                                                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4 text-gray-500" />
                                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Advance Notice
                                                    </label>
                                                </div>
                                                <div class="pl-6">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input
                                                            type="number"
                                                            wire:model.blur="alertConfigs.{{ $config['alert_type'] }}.advance_days"
                                                            min="1"
                                                            max="30"
                                                            class="text-sm"
                                                        />
                                                    </x-filament::input.wrapper>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        Days before event
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- Test Alert Button --}}
                                    <div class="mt-6 flex justify-end">
                                        <x-filament::button
                                            wire:click="testAlert('{{ $config['alert_type'] }}')"
                                            color="gray"
                                            size="sm"
                                            outlined
                                            wire:loading.attr="disabled"
                                        >
                                            <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="testAlert('{{ $config['alert_type'] }}')" />
                                            <span wire:loading.remove wire:target="testAlert('{{ $config['alert_type'] }}')">
                                                <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4 mr-2" />
                                                Send Test Alert
                                            </span>
                                        </x-filament::button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif

        {{-- Alert History Table --}}
        @if($showHistory)
        <x-filament::section>
            <x-slot name="heading">
                Recent Alerts
            </x-slot>
            
            {{ $this->table }}
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>