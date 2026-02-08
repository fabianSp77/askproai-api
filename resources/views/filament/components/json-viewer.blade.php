{{-- JSON Viewer Component - For displaying formatted JSON data --}}
@php
    $data = $getState();

    // Handle null/empty states
    if (empty($data)) {
        $json = null;
    } elseif (is_array($data) || is_object($data)) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // Try to decode if it's a JSON string
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $json = $data; // Just display raw string
        }
    }
@endphp

@if($json)
    <div
        class="bg-gray-900 dark:bg-gray-950 rounded-lg overflow-auto max-h-96 border border-gray-700"
        x-data="{
            collapsed: true,
            copySuccess: false,
            toggleCollapse() { this.collapsed = !this.collapsed },
            async copyToClipboard() {
                try {
                    await navigator.clipboard.writeText({{ Js::from($json) }});
                    this.copySuccess = true;
                    setTimeout(() => this.copySuccess = false, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            }
        }"
    >
        {{-- Header with actions --}}
        <div class="flex items-center justify-between px-4 py-2 bg-gray-800 dark:bg-gray-900 border-b border-gray-700">
            <span class="text-xs font-medium text-gray-400">JSON</span>
            <div class="flex items-center gap-2">
                {{-- Copy button --}}
                <button
                    @click="copyToClipboard()"
                    class="p-1 rounded hover:bg-gray-700 transition-colors"
                    title="JSON kopieren"
                    aria-label="JSON kopieren"
                    :aria-label="copySuccess ? 'Kopiert!' : 'JSON kopieren'"
                >
                    <template x-if="!copySuccess">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </template>
                    <template x-if="copySuccess">
                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                </button>
                {{-- Expand/Collapse button --}}
                <button
                    @click="toggleCollapse()"
                    class="p-1 rounded hover:bg-gray-700 transition-colors"
                    :title="collapsed ? 'Expandieren' : 'Kollabieren'"
                    :aria-label="collapsed ? 'JSON expandieren' : 'JSON kollabieren'"
                    :aria-expanded="!collapsed"
                >
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': !collapsed }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- JSON content --}}
        <div class="p-4" :class="{ 'max-h-48 overflow-hidden': collapsed }">
            <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap break-words">{{ $json }}</pre>
        </div>

        {{-- Gradient overlay when collapsed --}}
        <div
            x-show="collapsed"
            class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-gray-900 to-transparent pointer-events-none"
            x-cloak
        ></div>
    </div>
@else
    <div class="text-gray-500 dark:text-gray-400 italic py-4 text-center">
        Keine JSON-Daten vorhanden
    </div>
@endif
