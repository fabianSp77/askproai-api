<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-command-line class="h-5 w-5 text-gray-500" />
                MCP Quick Actions
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button
                size="xs"
                wire:click="executeQuickAction('view_dashboard')"
                color="gray"
            >
                View Dashboard
            </x-filament::button>
        </x-slot>

        <div class="space-y-4">
            {{-- Quick Action Buttons --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <x-filament::button
                    wire:click="executeQuickAction('import_calls')"
                    color="success"
                    size="sm"
                    class="w-full"
                >
                    <x-heroicon-m-phone-arrow-down-left class="h-4 w-4 mr-1" />
                    Import Calls
                </x-filament::button>

                <x-filament::button
                    wire:click="executeQuickAction('sync_calcom')"
                    color="info"
                    size="sm"
                    class="w-full"
                >
                    <x-heroicon-m-arrow-path class="h-4 w-4 mr-1" />
                    Sync Cal.com
                </x-filament::button>

                <x-filament::button
                    wire:click="executeQuickAction('check_health')"
                    color="warning"
                    size="sm"
                    class="w-full"
                >
                    <x-heroicon-m-heart class="h-4 w-4 mr-1" />
                    Check Health
                </x-filament::button>

                <x-filament::button
                    wire:click="executeQuickAction('discover_task')"
                    color="primary"
                    size="sm"
                    class="w-full"
                >
                    <x-heroicon-m-magnifying-glass class="h-4 w-4 mr-1" />
                    Discover Task
                </x-filament::button>
            </div>

            {{-- Command Shortcuts --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Command Shortcuts</h4>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-1 text-xs font-mono">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Book appointment:</span>
                        <code class="text-blue-600 dark:text-blue-400">php artisan mcp book</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Find customer:</span>
                        <code class="text-blue-600 dark:text-blue-400">php artisan mcp customer</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Remember note:</span>
                        <code class="text-blue-600 dark:text-blue-400">php artisan mcp remember</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Discover task:</span>
                        <code class="text-blue-600 dark:text-blue-400">php artisan mcp discover</code>
                    </div>
                </div>
            </div>

            {{-- Recent Commands --}}
            @if(count($recentCommands) > 0)
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Commands</h4>
                <div class="space-y-1">
                    @foreach($recentCommands as $cmd)
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            @if($cmd['success'])
                                <x-heroicon-o-check-circle class="h-4 w-4 text-green-500" />
                            @else
                                <x-heroicon-o-x-circle class="h-4 w-4 text-red-500" />
                            @endif
                            <code class="text-gray-600 dark:text-gray-400">{{ $cmd['command'] }}</code>
                        </div>
                        <span class="text-gray-500">{{ \Carbon\Carbon::parse($cmd['timestamp'])->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Command Output Modal --}}
        @if($showCommandOutput)
        <x-filament::modal id="command-output" :open="$showCommandOutput" width="2xl">
            <x-slot name="heading">
                Command Output
            </x-slot>

            <div class="bg-gray-900 text-gray-100 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                <pre>{{ $commandOutput }}</pre>
            </div>

            <x-slot name="footer">
                <x-filament::button wire:click="closeOutput" color="gray">
                    Close
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>