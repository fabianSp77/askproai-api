{{-- Interactive Template Variables with Copy-to-Clipboard and Accessibility --}}
@php
    use App\Services\ServiceGateway\EmailTemplateDataProvider;

    $variableGroups = EmailTemplateDataProvider::AVAILABLE_VARIABLES;

    // German group labels
    $groupLabels = [
        'customer' => 'Kunde',
        'case' => 'Fall',
        'source' => 'Quelle',
        'audio' => 'Audio',
        'sla' => 'SLA',
        'call' => 'Anruf',
        'ai' => 'AI',
        'admin' => 'Admin',
        'transcript' => 'Transcript',
    ];

    // Helper function to format template variable
    $formatVar = fn($name) => '{{' . $name . '}}';
@endphp

<div class="text-sm space-y-4" x-data="{ copied: null }">
    <p class="font-semibold text-gray-700 dark:text-gray-300">
        Verwenden Sie <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">@{{variable_name}}</code> im Betreff oder Body:
    </p>

    @foreach($variableGroups as $groupKey => $variables)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}
                <span class="text-xs text-gray-500 dark:text-gray-400">({{ count($variables) }} Variablen)</span>
            </h4>

            <div class="space-y-1.5">
                @foreach($variables as $varName => $description)
                    @php $templateVar = $formatVar($varName); @endphp
                    <div class="flex items-start justify-between gap-2 group hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded px-2 py-1.5 transition-colors">
                        <div class="flex-1 min-w-0">
                            <code class="text-xs font-mono text-primary-600 dark:text-primary-400 font-semibold">
                                {{ $templateVar }}
                            </code>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                {{ $description }}
                            </p>
                        </div>

                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText('{{ $templateVar }}'); copied = '{{ $varName }}'; setTimeout(() => copied = null, 1500);"
                            aria-label="Variable {{ $templateVar }} in Zwischenablage kopieren"
                            class="flex-shrink-0 p-1.5 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                            title="In Zwischenablage kopieren"
                        >
                            <span x-show="copied !== '{{ $varName }}'" class="w-4 h-4 inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                </svg>
                            </span>
                            <span x-show="copied === '{{ $varName }}'" class="w-4 h-4 inline-block text-green-600 dark:text-green-400" x-cloak>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-3 mt-4">
        <p>
            <strong>Hinweis:</strong> Alle Variablen sind optional und liefern leere Werte, wenn keine Daten vorhanden sind.
            Audio- und Admin-URLs sind zeitlich begrenzt gültig (24h bzw. 72h).
        </p>
    </div>
</div>
