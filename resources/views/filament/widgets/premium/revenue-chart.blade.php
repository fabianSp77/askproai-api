{{--
    Premium Revenue Bar Chart Widget
    Displays monthly/weekly/yearly revenue with tabs
--}}
@php
    use Filament\Support\Facades\FilamentView;
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <div class="premium-card" style="padding: 1.5rem;">
        {{-- Header with value and tabs --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <span class="premium-widget-title">TOTAL REVENUE</span>
                <div class="premium-widget-value mt-1">
                    {{ $this->getTotalRevenue() }}
                    @php $change = $this->getRevenueChange(); @endphp
                    <span class="{{ $change >= 0 ? 'premium-change premium-change-positive' : 'premium-change premium-change-negative' }}">
                        @if($change >= 0)
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        @endif
                        {{ number_format(abs($change), 1) }}%
                    </span>
                </div>
                <span class="premium-text-muted text-xs mt-1">vs. Vormonat</span>
            </div>

            {{-- Tab navigation --}}
            <div class="premium-tabs">
                <button
                    wire:click="setTab('weekly')"
                    class="premium-tab {{ $this->activeTab === 'weekly' ? 'premium-tab-active' : '' }}"
                >
                    Wöchentlich
                </button>
                <button
                    wire:click="setTab('monthly')"
                    class="premium-tab {{ $this->activeTab === 'monthly' ? 'premium-tab-active' : '' }}"
                >
                    Monatlich
                </button>
                <button
                    wire:click="setTab('yearly')"
                    class="premium-tab {{ $this->activeTab === 'yearly' ? 'premium-tab-active' : '' }}"
                >
                    Jährlich
                </button>
            </div>
        </div>

        {{-- Chart Canvas --}}
        <div class="premium-chart-container" style="height: 250px;">
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
                <canvas x-ref="canvas" style="max-height: 250px"></canvas>
                <span x-ref="backgroundColorElement" class="text-blue-50 dark:text-blue-400/10"></span>
                <span x-ref="borderColorElement" class="text-blue-500 dark:text-blue-400"></span>
                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
