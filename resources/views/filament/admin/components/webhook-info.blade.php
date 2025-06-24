<div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
    <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-1">Webhook URL:</p>
    <div class="flex items-center gap-2">
        <code class="text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded flex-1 break-all">
            {{ $url }}
        </code>
        <button 
            type="button"
            onclick="navigator.clipboard.writeText('{{ $url }}')"
            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            title="Copy to clipboard"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
        </button>
    </div>
    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
        Fügen Sie diese URL in Retell.ai unter Webhook Settings hinzu.
    </p>
</div>