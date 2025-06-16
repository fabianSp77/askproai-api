<x-filament::widget>
    <div class="rounded-2xl shadow-xl border bg-gradient-to-br from-gray-50 to-white p-6 mb-8">
        <div class="flex items-center mb-3">
            <svg width="22" height="22" class="text-gray-400 mr-2" viewBox="0 0 24 24" fill="none">
                <rect x="2" y="5" width="20" height="14" rx="2" stroke="#6b7280" stroke-width="2"/>
                <rect x="6" y="9" width="6" height="2" fill="#6b7280"/>
                <rect x="6" y="13" width="12" height="2" fill="#6b7280"/>
            </svg>
            <h2 class="text-lg font-extrabold tracking-wide text-gray-800 flex-1">System-Logs <span class="ml-2 text-xs text-gray-400 font-normal">(LIVE)</span></h2>
        </div>
        @php $logs = $this->getLogStatus(); @endphp
        <div class="overflow-auto max-h-56 border rounded-lg bg-gradient-to-tl from-gray-100 via-white to-gray-50 p-3 text-xs font-mono text-gray-700 shadow-inner">
            @forelse($logs['last'] as $log)
                <div class="mb-1 whitespace-pre-line leading-tight">{{ $log }}</div>
            @empty
                <div class="italic text-gray-300">Keine Log-Einträge gefunden.</div>
            @endforelse
        </div>
    </div>
</x-filament::widget>
