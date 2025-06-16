<x-filament::widget>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg width="18" height="18" class="text-indigo-400" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="3" stroke="#6366f1" stroke-width="2"/><rect x="6" y="10" width="12" height="4" fill="#6366f1"/></svg>
            Queue-Status <span class="ml-2 text-xs text-indigo-400 font-normal">(LIVE)</span>
        </h2>
        @php $queue = $this->getQueueStatus(); @endphp
        <div class="mb-2"><b>Connection:</b> {{ $queue['connection'] }}</div>
        <div class="mb-2"><b>Jobs in Queue:</b> {{ $queue['jobs'] ?? 'n/a' }}</div>
        <div class="mb-2"><b>Fehlgeschlagene Jobs:</b> <span class="text-red-600 font-bold">{{ $queue['failed'] ?? 'n/a' }}</span></div>
        @if(($queue['failed'] ?? 0) > 0)
            <div class="text-xs text-red-600">Mindestens ein Queue-Job ist fehlgeschlagen!</div>
        @endif
    </x-filament::card>
</x-filament::widget>
