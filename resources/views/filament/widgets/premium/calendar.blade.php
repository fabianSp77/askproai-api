{{--
    Premium Calendar Widget
    Interactive mini calendar with appointment indicators
--}}
<x-filament-widgets::widget>
    <div class="premium-card">
        @php
            $calendarData = $this->getCalendarData();
            $kpiData = $this->getKpiData();
        @endphp

        {{-- Calendar Header --}}
        <div class="premium-calendar-header">
            <span class="premium-calendar-month">{{ $calendarData['monthName'] }}</span>
            <div class="premium-calendar-nav">
                <button wire:click="navigateMonth('prev')" class="premium-calendar-nav-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button wire:click="navigateMonth('next')" class="premium-calendar-nav-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="premium-calendar-grid">
            {{-- Weekday headers --}}
            @foreach($calendarData['weekdays'] as $weekday)
                <div class="premium-calendar-weekday">{{ $weekday }}</div>
            @endforeach

            {{-- Days --}}
            @foreach($calendarData['days'] as $day)
                <div wire:click="selectDate('{{ $day['fullDate'] }}')"
                     class="premium-calendar-day {{ !$day['isCurrentMonth'] ? 'premium-calendar-day-outside' : '' }} {{ $day['isToday'] ? 'premium-calendar-day-today' : '' }}">
                    <span>{{ $day['date'] }}</span>
                    @if(count($day['events']) > 0)
                        <div class="premium-calendar-day-events">
                            @foreach(array_slice($day['events'], 0, 3) as $event)
                                <span class="premium-calendar-event-dot" style="background-color: {{ $event['color'] }}"></span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- KPI Footer --}}
        <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-white/5">
            <div class="premium-mini-kpi">
                <div class="premium-mini-kpi-icon premium-mini-kpi-icon-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <div class="premium-mini-kpi-content">
                    <span class="premium-mini-kpi-label">Termine</span>
                    <span class="premium-mini-kpi-value">{{ $kpiData['total'] }}</span>
                </div>
            </div>
            <div class="premium-mini-kpi">
                <div class="premium-mini-kpi-icon premium-mini-kpi-icon-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="premium-mini-kpi-content">
                    <span class="premium-mini-kpi-label">Bestätigt</span>
                    <div class="flex items-center gap-2">
                        <span class="premium-mini-kpi-value">{{ $kpiData['confirmed'] }}</span>
                        @if($kpiData['growth'] != 0)
                            <span class="{{ $kpiData['growth'] >= 0 ? 'premium-change-positive' : 'premium-change-negative' }} text-xs">
                                {{ $kpiData['growth'] >= 0 ? '+' : '' }}{{ $kpiData['growth'] }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
