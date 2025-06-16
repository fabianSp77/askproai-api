<div>
    @if($getRecord() && $getRecord()->retell_agent_id)
        <div class="space-y-3">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">
                    Aktueller Agent: {{ $getRecord()->retell_agent_name ?? 'Agent ' . $getRecord()->retell_agent_id }}
                </h4>
                <p class="text-sm text-blue-700 dark:text-blue-400">
                    ID: {{ $getRecord()->retell_agent_id }}
                </p>
                @if($getRecord()->retell_last_sync)
                    <p class="text-xs text-blue-600 dark:text-blue-500 mt-1">
                        Zuletzt synchronisiert: {{ $getRecord()->retell_last_sync->diffForHumans() }}
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <p class="text-gray-600 dark:text-gray-400">
                Noch kein Agent konfiguriert. Geben Sie eine Agent ID ein, um zu beginnen.
            </p>
        </div>
    @endif
</div>
