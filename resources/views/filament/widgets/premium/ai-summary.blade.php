{{--
    Premium AI Summary Widget
    AI-powered performance summary with natural language insights
--}}
<x-filament-widgets::widget>
    <div class="premium-card premium-glass">
        @php
            $miniKpis = $this->getMiniKpis();
        @endphp

        <div class="flex flex-col lg:flex-row gap-6">
            {{-- AI Summary Section --}}
            <div class="flex-1">
                {{-- AI Header --}}
                <div class="premium-ai-header">
                    <div class="premium-ai-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
                            <path d="M5 19l.5 1.5L7 21l-1.5.5L5 23l-.5-1.5L3 21l1.5-.5L5 19z"/>
                            <path d="M18 14l.5 1.5 1.5.5-1.5.5-.5 1.5-.5-1.5-1.5-.5 1.5-.5.5-1.5z"/>
                        </svg>
                    </div>
                    <span class="premium-ai-label">AI Performance Summary</span>
                </div>

                {{-- AI Summary Text --}}
                <div class="premium-ai-summary">
                    {!! $this->generateAISummary() !!}
                </div>
            </div>

            {{-- Mini KPIs --}}
            @if(count($miniKpis) > 0)
                <div class="flex flex-wrap lg:flex-nowrap gap-3 lg:border-l lg:border-white/5 lg:pl-6">
                    @foreach($miniKpis as $kpi)
                        <div class="premium-mini-kpi flex-1 min-w-[120px]">
                            <div class="premium-mini-kpi-icon premium-mini-kpi-icon-{{ $kpi['iconColor'] }}">
                                <x-dynamic-component :component="$kpi['icon']" class="w-5 h-5" />
                            </div>
                            <div class="premium-mini-kpi-content">
                                <span class="premium-mini-kpi-label">{{ $kpi['label'] }}</span>
                                <span class="premium-mini-kpi-value">{{ $kpi['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
