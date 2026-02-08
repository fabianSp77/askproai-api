{{--
    Cost Sparkline Component
    Mini trend chart showing cost trend over last 7 days
    Uses Chart.js (already available via CDN in Filament)

    Accessibility: WCAG 2.1 AA compliant
    - aria-hidden="true" on decorative SVGs
    - Hidden data table for screen readers
    - role="img" with descriptive aria-label

    Phase 13: ViewCall Page Improvements
--}}
@php
    $record = $getRecord();
    if (!$record || !$record->company_id) return;

    $companyId = $record->company_id;
    $chartId = 'cost-sparkline-' . $record->id . '-' . substr(md5(uniqid()), 0, 6);

    // Fetch last 7 days costs with caching to improve performance
    $cacheKey = "company:{$companyId}:cost_trend_7d:" . now()->format('Y-m-d-H');
    $costs = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(15), function () use ($companyId) {
        return \App\Models\Call::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('cost_cents')
            ->where('cost_cents', '>', 0)
            ->selectRaw('DATE(created_at) as date, SUM(cost_cents) / 100 as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    });

    // Need at least 2 data points for a meaningful chart
    if (count($costs) < 2) return;

    $labels = array_keys($costs);
    $values = array_values($costs);

    // Calculate trend
    $firstValue = reset($values);
    $lastValue = end($values);
    $trendPercent = $firstValue > 0 ? round((($lastValue - $firstValue) / $firstValue) * 100, 1) : 0;
    $trendUp = $trendPercent > 0;
    $total = array_sum($values);

    // Format dates for display
    $formattedDates = array_map(fn($date) => \Carbon\Carbon::parse($date)->format('d.m.'), $labels);
@endphp

<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Kostentrend (7 Tage)</span>
        </div>

        {{-- Trend Badge with text + icon for accessibility --}}
        <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
            {{ $trendUp ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
            @if($trendUp)
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span>+{{ abs($trendPercent) }}%</span>
            @else
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                <span>-{{ abs($trendPercent) }}%</span>
            @endif
        </div>
    </div>

    {{-- Chart Container with Screen Reader Support --}}
    <div
        class="h-16 relative"
        role="img"
        aria-label="Kostentrend: {{ $trendUp ? 'Anstieg' : 'Rückgang' }} um {{ abs($trendPercent) }}% über {{ count($costs) }} Tage. Gesamtkosten: {{ number_format($total, 2, ',', '.') }} EUR"
    >
        {{-- Hidden data table for screen readers --}}
        <table class="sr-only">
            <caption>Tägliche Kosten der letzten {{ count($costs) }} Tage</caption>
            <thead>
                <tr>
                    <th scope="col">Datum</th>
                    <th scope="col">Kosten</th>
                </tr>
            </thead>
            <tbody>
                @foreach($costs as $date => $cost)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</td>
                    <td>{{ number_format($cost, 2, ',', '.') }} EUR</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Visual chart (hidden from screen readers) --}}
        <canvas id="{{ $chartId }}" class="w-full h-full" aria-hidden="true"></canvas>
    </div>

    {{-- Summary --}}
    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span>{{ count($costs) }} Tage</span>
        <span class="font-medium text-gray-700 dark:text-gray-300">
            Gesamt: {{ number_format($total, 2, ',', '.') }} EUR
        </span>
    </div>
</div>

{{-- Chart.js CDN - Load if not already available --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
(function() {
    var chartId = '{{ $chartId }}';
    var labels = @json($formattedDates);
    var values = @json($values);

    function initSparkline() {
        // Wait for Chart.js to load
        if (typeof Chart === 'undefined') {
            setTimeout(initSparkline, 50);
            return;
        }

        var ctx = document.getElementById(chartId);
        if (!ctx) return;

        // Check if already initialized
        if (ctx._chartInstance) return;

        var isDarkMode = document.documentElement.classList.contains('dark') ||
                         document.body.classList.contains('dark');

        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: isDarkMode ? '#60a5fa' : '#3b82f6',
                    backgroundColor: isDarkMode ? 'rgba(96, 165, 250, 0.1)' : 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: isDarkMode ? '#60a5fa' : '#3b82f6',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isDarkMode ? '#374151' : '#1f2937',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 8,
                        displayColors: false,
                        callbacks: {
                            title: function(items) {
                                return items[0].label;
                            },
                            label: function(item) {
                                return item.parsed.y.toFixed(2) + ' EUR';
                            }
                        }
                    }
                },
                scales: {
                    x: { display: false },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Store reference for cleanup
        ctx._chartInstance = chart;
        console.log('[Cost Sparkline] Chart initialized successfully');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSparkline);
    } else {
        initSparkline();
    }
})();
</script>
