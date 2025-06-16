<x-filament::widget>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg width="18" height="18" class="text-green-400" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="3" stroke="#22c55e" stroke-width="2"/><rect x="6" y="10" width="12" height="4" fill="#22c55e"/></svg>
            Backup-Status <span class="ml-2 text-xs text-green-400 font-normal">(LIVE)</span>
        </h2>
        @php $backup = $this->getBackupStatus(); @endphp
        <div class="mb-2"><b>Letzte Backups:</b></div>
        @if(count($backup['files']))
            <ul class="list-disc list-inside text-sm">
                @foreach($backup['files'] as $file)
                    <li>{{ $file }}</li>
                @endforeach
            </ul>
        @else
            <div class="text-xs text-gray-500">Keine Backups gefunden</div>
        @endif
    </x-filament::card>
</x-filament::widget>
