{{--
    Exchange Log Export Buttons Component

    Provides copy-to-clipboard and download functionality for webhook debugging.
    Used in:
    - DeliveryLogsRelationManager detail modal
    - ServiceGatewayExchangeLogResource view page

    @param \App\Models\ServiceGatewayExchangeLog $log
--}}

@php
    $curlCommand = $log->toCurlCommand();
    $jsonExport = json_encode($log->toExportJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $downloadFilename = "webhook-{$log->short_event_id}-" . now()->format('Y-m-d-His') . '.json';
@endphp

<div x-data="{
    copied: null,
    copyToClipboard(text, type) {
        navigator.clipboard.writeText(text).then(() => {
            this.copied = type;
            setTimeout(() => this.copied = null, 1500);
        }).catch(err => {
            console.error('Clipboard copy failed:', err);
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this.copied = type;
            setTimeout(() => this.copied = null, 1500);
        });
    },
    downloadJson() {
        const content = this.$refs.jsonContent.textContent;
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '{{ $downloadFilename }}';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}" class="flex flex-wrap gap-2">

    {{-- Copy cURL Button --}}
    <button
        type="button"
        x-on:click.stop="copyToClipboard($refs.curlContent.textContent, 'curl')"
        :class="copied === 'curl'
            ? 'bg-success-500 text-white border-success-600'
            : 'bg-gray-600 hover:bg-gray-500 text-white border-gray-500'"
        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium border rounded-lg transition-all duration-200"
        title="cURL-Kommando in Zwischenablage kopieren"
    >
        <span x-show="copied !== 'curl'" class="flex items-center gap-2">
            <x-heroicon-o-command-line class="w-4 h-4" />
            <span>Copy cURL</span>
        </span>
        <span x-show="copied === 'curl'" x-cloak class="flex items-center gap-2">
            <x-heroicon-o-check class="w-4 h-4" />
            <span>Kopiert!</span>
        </span>
    </button>

    {{-- Copy JSON Button --}}
    <button
        type="button"
        x-on:click.stop="copyToClipboard($refs.jsonContent.textContent, 'json')"
        :class="copied === 'json'
            ? 'bg-success-500 text-white border-success-600'
            : 'bg-gray-600 hover:bg-gray-500 text-white border-gray-500'"
        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium border rounded-lg transition-all duration-200"
        title="JSON-Export in Zwischenablage kopieren"
    >
        <span x-show="copied !== 'json'" class="flex items-center gap-2">
            <x-heroicon-o-document-text class="w-4 h-4" />
            <span>Copy JSON</span>
        </span>
        <span x-show="copied === 'json'" x-cloak class="flex items-center gap-2">
            <x-heroicon-o-check class="w-4 h-4" />
            <span>Kopiert!</span>
        </span>
    </button>

    {{-- Download JSON Button --}}
    <button
        type="button"
        x-on:click.stop="downloadJson()"
        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-all duration-200"
        title="JSON als Datei herunterladen"
    >
        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
        <span>Download</span>
    </button>

    {{-- Hidden content for clipboard operations --}}
    <pre x-ref="curlContent" class="hidden">{{ $curlCommand }}</pre>
    <pre x-ref="jsonContent" class="hidden">{{ $jsonExport }}</pre>
</div>
