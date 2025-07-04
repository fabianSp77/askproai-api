<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Phone Number → Agent Connections</span>
                <button wire:click="$refresh" 
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </x-slot>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="text-left py-2 px-3">Phone Number</th>
                        <th class="text-left py-2 px-3">Branch</th>
                        <th class="text-left py-2 px-3">Agent</th>
                        <th class="text-left py-2 px-3">Status</th>
                        <th class="text-left py-2 px-3">Last Sync</th>
                        <th class="text-center py-2 px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getPhoneAgentData() as $phone)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            wire:key="phone-{{ $phone['id'] }}">
                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono">{{ $phone['formatted_number'] }}</span>
                                    @if($phone['type'] === 'main')
                                        <span class="text-xs bg-primary-100 text-primary-700 dark:bg-primary-800 dark:text-primary-300 px-2 py-0.5 rounded">
                                            Main
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2 px-3 text-gray-600 dark:text-gray-400">
                                {{ $phone['branch'] }}
                            </td>
                            <td class="py-2 px-3">
                                @if($phone['agent_id'])
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm">{{ $phone['agent_name'] }}</span>
                                        <span class="text-xs text-gray-500 font-mono">({{ substr($phone['agent_id'], -8) }})</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 italic">Not configured</span>
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2">
                                    @if($phone['is_online'])
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                            <span class="text-xs text-green-600 dark:text-green-400">Online</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                            <span class="text-xs text-red-600 dark:text-red-400">Offline</span>
                                        </span>
                                    @endif
                                    
                                    @if($phone['sync_status'] === 'synced')
                                        <span class="text-xs text-green-600 dark:text-green-400">✓ Synced</span>
                                    @elseif($phone['sync_status'] === 'stale')
                                        <span class="text-xs text-yellow-600 dark:text-yellow-400">⚠ Stale</span>
                                    @elseif($phone['sync_status'] === 'outdated')
                                        <span class="text-xs text-red-600 dark:text-red-400">⚠ Outdated</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2 px-3 text-xs text-gray-500">
                                @if($phone['last_sync'])
                                    {{ \Carbon\Carbon::parse($phone['last_sync'])->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                <div class="flex items-center justify-center gap-2">
                                    @if($phone['agent_id'])
                                        <button wire:click="syncPhoneAgent('{{ $phone['id'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50"
                                                class="text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 px-2 py-1 rounded">
                                            <svg wire:loading.remove wire:target="syncPhoneAgent('{{ $phone['id'] }}')" 
                                                 class="w-3 h-3 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <svg wire:loading wire:target="syncPhoneAgent('{{ $phone['id'] }}')" 
                                                 class="w-3 h-3 inline-block animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Sync
                                        </button>
                                        
                                        <button wire:click="testCall('{{ $phone['id'] }}')"
                                                class="text-xs bg-green-100 hover:bg-green-200 dark:bg-green-800 dark:hover:bg-green-700 text-green-700 dark:text-green-300 px-2 py-1 rounded">
                                            <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            Test
                                        </button>
                                    @endif
                                    
                                    <a href="/admin/phone-numbers/{{ $phone['id'] }}/edit"
                                       class="text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-800 dark:hover:bg-blue-700 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">
                                No phone numbers configured
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(count($this->getPhoneAgentData()) > 0)
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Agent reachable
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Agent unreachable
                    </span>
                    <span>✓ Recently synced</span>
                    <span>⚠ Needs sync</span>
                </div>
            </div>
        @endif
    </x-filament::section>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('phoneAgentSynced', (event) => {
                // Show success notification using Filament's notification system
                window.$wireui ? 
                    window.$wireui.notify({
                        title: 'Phone agent synced successfully',
                        icon: 'success'
                    }) : 
                    console.log('Phone agent synced', event);
            });
            
            Livewire.on('syncFailed', (event) => {
                // Show error notification
                window.$wireui ? 
                    window.$wireui.notify({
                        title: 'Sync failed',
                        description: event.message || 'An error occurred',
                        icon: 'error'
                    }) : 
                    console.error('Sync failed', event);
            });
            
            Livewire.on('initiateTestCall', (event) => {
                // Log test call
                console.log('Initiating test call to:', event.phoneNumber);
                window.$wireui ? 
                    window.$wireui.notify({
                        title: 'Test call initiated',
                        description: 'Calling ' + event.phoneNumber,
                        icon: 'success'
                    }) : 
                    console.log('Test call initiated');
            });
        });
    </script>
</x-filament-widgets::widget>