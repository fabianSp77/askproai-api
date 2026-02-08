{{--
    Premium Spending Donut Chart Widget
    Call distribution by status with center value overlay
--}}
@php
    use Filament\Support\Facades\FilamentView;
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <div class="premium-card">
        @php
            $totalCalls = $this->getTotalCalls();
            $legendData = $this->getLegendData();
        @endphp

        {{-- Header --}}
        <div class="mb-4">
            <span class="premium-widget-title">ANRUF-VERTEILUNG</span>
        </div>

        {{-- Donut Chart with Center Text --}}
        <div class="premium-chart-container relative" style="height: 180px;">
            {{-- Center text overlay --}}
            <div class="premium-donut-center">
                <div class="premium-donut-center-value">{{ number_format($totalCalls, 0, ',', '.') }}</div>
                <div class="premium-donut-center-label">Anrufe</div>
            </div>

            {{-- Chart Canvas --}}
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($this->getType()),
                })"
            >
                <canvas x-ref="canvas" style="max-height: 180px"></canvas>
                <span x-ref="backgroundColorElement" class="text-blue-50 dark:text-blue-400/10"></span>
                <span x-ref="borderColorElement" class="text-blue-500 dark:text-blue-400"></span>
                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>

        {{-- Custom Legend --}}
        @if(count($legendData) > 0)
            <div class="premium-legend mt-4 pt-4 border-t border-white/5">
                @foreach($legendData as $item)
                    <div class="premium-legend-item">
                        <span class="premium-legend-dot" style="background-color: {{ $item['color'] }}"></span>
                        <span class="premium-legend-label">{{ $item['label'] }} ({{ $item['percent'] }}%)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
