<div class="space-y-3 rounded-lg bg-green-50 border border-green-200 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Status</p>
            <p class="text-sm text-green-900 font-bold">
                ✅ Aktiv
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Version</p>
            <p class="text-sm text-green-900 font-bold">
                {{ $prompt->version }}
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Deployed</p>
            <p class="text-sm text-green-900">
                {{ $prompt->deployed_at?->format('d.m.Y H:i') ?? '-' }}
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Retell Version</p>
            <p class="text-sm text-green-900">
                {{ $prompt->retell_version ?? '-' }}
            </p>
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Prompt Preview</p>
        <div class="mt-2 p-3 bg-white rounded border border-green-100 max-h-48 overflow-y-auto">
            <p class="text-xs text-gray-600 font-mono whitespace-pre-wrap">
                {{ substr($prompt->prompt_content, 0, 300) }}...
            </p>
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Funktionen</p>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($prompt->functions_config as $func)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                    {{ $func['name'] }}
                </span>
            @endforeach
        </div>
    </div>
</div>
