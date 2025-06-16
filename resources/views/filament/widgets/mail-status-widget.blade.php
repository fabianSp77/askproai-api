<x-filament::widget>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg width="20" height="20" class="text-blue-400" viewBox="0 0 24 24" fill="none"><path d="M2 4h20v16H2z" stroke="#38bdf8" stroke-width="2" /><path d="M22 4l-10 9L2 4" stroke="#38bdf8" stroke-width="2" /></svg>
            Mail-Systemstatus <span class="ml-2 text-xs text-blue-400 font-normal">(LIVE)</span>
        </h2>
        @php $mail = $this->getMailStatus(); @endphp
        <div class="mb-4">
            <div class="text-sm mb-2"><b>Mailer:</b> {{ $mail['mailer'] }} – {{ $mail['host'] }}:{{ $mail['port'] }}</div>
            <div class="text-sm mb-2"><b>User:</b> {{ $mail['user'] ?? 'n/a' }}</div>
            <div class="text-sm mb-2"><b>From:</b> {{ $mail['from'] ?? 'n/a' }}</div>
            <div class="text-lg font-bold mb-1 flex items-center">
                Status:
                @if($mail['online'] === true)
                    <span class="ml-2 text-green-600 animate-pulse">SMTP Online</span>
                @elseif($mail['online'] === false)
                    <span class="ml-2 text-red-600 animate-pulse">SMTP Fehler</span>
                @else
                    <span class="ml-2 text-gray-400">unbekannt</span>
                @endif
            </div>
            @if($mail['error'])
                <div class="text-red-600 text-xs mt-1">{{ $mail['error'] }}</div>
            @endif
            @if($mail['lastError'])
                <div class="text-red-500 text-xs mt-2 border-t pt-2">Letzter Fehler im Log: <br> {{ $mail['lastError'] }}</div>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
