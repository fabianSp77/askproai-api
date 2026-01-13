<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <x-slot name="description">
            {{ $this->getDescription() }}
        </x-slot>

        @php
            $performers = $this->getPerformers();
        @endphp

        @if($performers->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p>Keine Daten im ausgewählten Zeitraum</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">#</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Unternehmen</th>
                            <th class="text-right py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($performers as $index => $performer)
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-gray-500 dark:text-gray-400">
                                    @if($index === 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 font-semibold text-xs">
                                            1
                                        </span>
                                    @elseif($index === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-semibold text-xs">
                                            2
                                        </span>
                                    @elseif($index === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 font-semibold text-xs">
                                            3
                                        </span>
                                    @else
                                        <span class="text-gray-400 pl-2">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $performer['name'] }}
                                        </span>
                                        @if($performer['type'] === 'reseller')
                                            <x-filament::badge color="info" size="sm">
                                                Reseller
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <span class="font-semibold text-success-600 dark:text-success-400">
                                        {{ number_format($performer['profit'] / 100, 2, ',', '.') }} €
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Summary Footer --}}
            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ $performers->count() }} Unternehmen
                    </span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        Gesamt:
                        <span class="text-success-600 dark:text-success-400">
                            {{ number_format($performers->sum('profit') / 100, 2, ',', '.') }} €
                        </span>
                    </span>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
