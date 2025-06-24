<x-filament-panels::page>
    @if($company)
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="mt-6 flex justify-end gap-x-3">
                <x-filament::button type="submit">
                    Save Configuration
                </x-filament::button>
            </div>
        </form>
        
        {{-- Simple Status Display --}}
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-4">Current Status</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Cal.com Status --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Cal.com Status
                    </x-slot>
                    
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span>API Key</span>
                            @if($company->calcom_api_key)
                                <span class="text-success-600 dark:text-success-400">Configured</span>
                            @else
                                <span class="text-danger-600 dark:text-danger-400">Not configured</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span>Team Slug</span>
                            @if($company->calcom_team_slug)
                                <span class="text-success-600 dark:text-success-400">{{ $company->calcom_team_slug }}</span>
                            @else
                                <span class="text-gray-500">Not set (optional)</span>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
                
                {{-- Retell.ai Status --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Retell.ai Status
                    </x-slot>
                    
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span>API Key</span>
                            @if($company->retell_api_key)
                                <span class="text-success-600 dark:text-success-400">Configured</span>
                            @else
                                <span class="text-danger-600 dark:text-danger-400">Not configured</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span>Agent ID</span>
                            @if($company->retell_agent_id)
                                <span class="text-success-600 dark:text-success-400">{{ substr($company->retell_agent_id, 0, 15) }}...</span>
                            @else
                                <span class="text-danger-600 dark:text-danger-400">Not configured</span>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            </div>
            
            {{-- Webhook URLs --}}
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Webhook URLs
                </x-slot>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium mb-1">Retell.ai Webhook URL:</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded block">
                            https://api.askproai.de/api/retell/webhook
                        </code>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium mb-1">Cal.com Webhook URL:</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded block">
                            https://api.askproai.de/api/calcom/webhook
                        </code>
                    </div>
                </div>
                
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                    Configure these URLs in the respective services' webhook settings.
                </p>
            </x-filament::section>
        </div>
    @else
        <div class="text-center py-12">
            <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-warning-500 mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No Company Found</h3>
            <p class="text-gray-500 dark:text-gray-400">
                No company is associated with your account. Please contact an administrator.
            </p>
        </div>
    @endif
</x-filament-panels::page>