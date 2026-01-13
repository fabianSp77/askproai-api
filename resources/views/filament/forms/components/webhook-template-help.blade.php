{{-- Webhook Template Help with JSON Example and Interactive Variables --}}
@php
$ticketVars = ['ticket_id', 'subject', 'description', 'case_type', 'priority', 'status'];
$customerVars = ['customer_name', 'customer_phone', 'customer_email', 'customer_location'];
$enrichmentVars = ['transcript', 'ai_summary', 'structured_data', 'audio_url'];
$metaVars = ['category', 'created_at', 'assigned_to', 'timestamp'];

$exampleJson = '{
  "ticket": {
    "id": "{{ticket_id}}",
    "subject": "{{subject}}",
    "description": "{{description}}",
    "type": "{{case_type}}",
    "priority": "{{priority}}",
    "status": "{{status}}"
  },
  "customer": {
    "name": "{{customer_name}}",
    "phone": "{{customer_phone}}",
    "email": "{{customer_email}}"
  },
  "meta": {
    "category": "{{category}}",
    "created_at": "{{created_at}}",
    "transcript": "{{transcript}}",
    "summary": "{{ai_summary}}"
  }
}';
@endphp
<div x-data="{
    copied: null,
    showExample: false,
    exampleJson: @js($exampleJson)
}" class="space-y-4">

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            x-on:click="showExample = !showExample"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-50 dark:bg-primary-900 text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-800 transition-colors"
        >
            <x-heroicon-o-code-bracket class="w-4 h-4" />
            <span x-text="showExample ? 'Beispiel ausblenden' : 'JSON-Beispiel anzeigen'"></span>
        </button>
        <button
            type="button"
            x-on:click="navigator.clipboard.writeText(exampleJson); copied = 'example'; setTimeout(() => copied = null, 2000);"
            x-bind:class="copied === 'example' ? 'bg-success-100 text-success-700' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
        >
            <x-heroicon-o-clipboard-document class="w-4 h-4" />
            <span x-show="copied !== 'example'">Beispiel kopieren</span>
            <span x-show="copied === 'example'" x-cloak>Kopiert!</span>
        </button>
    </div>

    {{-- JSON Example (collapsible) --}}
    <div x-show="showExample" x-collapse x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-800 text-gray-100 p-4 text-xs font-mono overflow-x-auto">
            <pre x-text="exampleJson"></pre>
        </div>
    </div>

    {{-- Variable Categories --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        {{-- Ticket Variables --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-100 dark:border-blue-800">
            <div class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-2">
                Ticket
            </div>
            <div class="flex flex-wrap gap-1">
                @foreach($ticketVars as $var)
                @php $varTemplate = '{{' . $var . '}}'; @endphp
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $varTemplate }}'); copied = '{{ $var }}'; setTimeout(() => copied = null, 1500);"
                    x-bind:class="copied === '{{ $var }}' ? 'bg-success-500 text-white' : 'bg-white dark:bg-gray-800 hover:bg-blue-100 dark:hover:bg-blue-800'"
                    class="px-1.5 py-0.5 text-xs font-mono rounded border border-blue-200 dark:border-blue-700 transition-all cursor-pointer"
                    title="{{ $var }}"
                >
                    {{ $var }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Customer Variables --}}
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-100 dark:border-green-800">
            <div class="text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider mb-2">
                Kunde
            </div>
            <div class="flex flex-wrap gap-1">
                @foreach($customerVars as $var)
                @php $varTemplate = '{{' . $var . '}}'; @endphp
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $varTemplate }}'); copied = '{{ $var }}'; setTimeout(() => copied = null, 1500);"
                    x-bind:class="copied === '{{ $var }}' ? 'bg-success-500 text-white' : 'bg-white dark:bg-gray-800 hover:bg-green-100 dark:hover:bg-green-800'"
                    class="px-1.5 py-0.5 text-xs font-mono rounded border border-green-200 dark:border-green-700 transition-all cursor-pointer"
                    title="{{ $var }}"
                >
                    {{ $var }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Enrichment Variables --}}
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border border-purple-100 dark:border-purple-800">
            <div class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-2">
                Enrichment
            </div>
            <div class="flex flex-wrap gap-1">
                @foreach($enrichmentVars as $var)
                @php $varTemplate = '{{' . $var . '}}'; @endphp
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $varTemplate }}'); copied = '{{ $var }}'; setTimeout(() => copied = null, 1500);"
                    x-bind:class="copied === '{{ $var }}' ? 'bg-success-500 text-white' : 'bg-white dark:bg-gray-800 hover:bg-purple-100 dark:hover:bg-purple-800'"
                    class="px-1.5 py-0.5 text-xs font-mono rounded border border-purple-200 dark:border-purple-700 transition-all cursor-pointer"
                    title="{{ $var }}"
                >
                    {{ $var }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Meta Variables --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-100 dark:border-amber-800">
            <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-2">
                Meta
            </div>
            <div class="flex flex-wrap gap-1">
                @foreach($metaVars as $var)
                @php $varTemplate = '{{' . $var . '}}'; @endphp
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $varTemplate }}'); copied = '{{ $var }}'; setTimeout(() => copied = null, 1500);"
                    x-bind:class="copied === '{{ $var }}' ? 'bg-success-500 text-white' : 'bg-white dark:bg-gray-800 hover:bg-amber-100 dark:hover:bg-amber-800'"
                    class="px-1.5 py-0.5 text-xs font-mono rounded border border-amber-200 dark:border-amber-700 transition-all cursor-pointer"
                    title="{{ $var }}"
                >
                    {{ $var }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
        <x-heroicon-o-cursor-arrow-rays class="w-4 h-4" />
        Variablen anklicken zum Kopieren. JSON wird vor dem Senden validiert.
    </p>
</div>
