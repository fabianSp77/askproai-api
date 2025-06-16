<x-filament::widget>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg width="18" height="18" class="text-cyan-400" viewBox="0 0 24 24" fill="none"><rect x="2" y="7" width="20" height="10" rx="3" stroke="#06b6d4" stroke-width="2"/><rect x="7" y="11" width="10" height="2" fill="#06b6d4"/></svg>
            Middleware/Integrations-Status <span class="ml-2 text-xs text-cyan-400 font-normal">(LIVE)</span>
        </h2>
        @php $mw = $this->getMiddlewareStatus(); @endphp
        <div class="mb-2">
            @if($mw['active'])
                <span class="text-green-600 font-bold">AKTIV</span> – {{ $mw['desc'] }}
            @else
                <span class="text-red-600 font-bold">INAKTIV</span> – {{ $mw['desc'] }}
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
