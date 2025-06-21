@props([
    'roi' => 0,
    'status' => 'break-even',
    'revenue' => 0,
    'cost' => 0,
    'profit' => 0,
    'appointments' => 0,
    'conversionRate' => 0,
    'dateRange' => '',
])

<div class="w-full">
    {{-- Mobile ROI Card --}}
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br 
        @if($status === 'excellent') from-green-500 to-green-600
        @elseif($status === 'good') from-yellow-500 to-yellow-600
        @elseif($status === 'break-even') from-orange-500 to-orange-600
        @else from-red-500 to-red-600
        @endif
        p-6 text-white shadow-xl">
        
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold">ROI</h3>
                <p class="text-sm text-white/80">{{ $dateRange }}</p>
            </div>
            <div class="text-right">
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold">{{ number_format($roi, 1) }}</span>
                    <span class="text-lg">%</span>
                </div>
                <p class="text-xs text-white/80">
                    @if($status === 'excellent') Exzellent
                    @elseif($status === 'good') Gut
                    @elseif($status === 'break-even') Break-Even
                    @else Negativ
                    @endif
                </p>
            </div>
        </div>
        
        {{-- Swipeable Metrics (for mobile) --}}
        <div class="mt-6 -mx-6 px-6">
            <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 scrollbar-hide">
                {{-- Revenue Card --}}
                <div class="min-w-[140px] snap-center rounded-lg bg-white/10 p-3 backdrop-blur">
                    <p class="text-xs text-white/70">Umsatz</p>
                    <p class="text-lg font-semibold">€{{ number_format($revenue, 0) }}</p>
                </div>
                
                {{-- Cost Card --}}
                <div class="min-w-[140px] snap-center rounded-lg bg-white/10 p-3 backdrop-blur">
                    <p class="text-xs text-white/70">Kosten</p>
                    <p class="text-lg font-semibold">€{{ number_format($cost, 0) }}</p>
                </div>
                
                {{-- Profit Card --}}
                <div class="min-w-[140px] snap-center rounded-lg bg-white/10 p-3 backdrop-blur">
                    <p class="text-xs text-white/70">Gewinn</p>
                    <p class="text-lg font-semibold">€{{ number_format($profit, 0) }}</p>
                </div>
                
                {{-- Appointments Card --}}
                <div class="min-w-[140px] snap-center rounded-lg bg-white/10 p-3 backdrop-blur">
                    <p class="text-xs text-white/70">Termine</p>
                    <p class="text-lg font-semibold">{{ $appointments }}</p>
                </div>
                
                {{-- Conversion Card --}}
                <div class="min-w-[140px] snap-center rounded-lg bg-white/10 p-3 backdrop-blur">
                    <p class="text-xs text-white/70">Konversion</p>
                    <p class="text-lg font-semibold">{{ $conversionRate }}%</p>
                </div>
            </div>
        </div>
        
        {{-- Quick Actions --}}
        <div class="mt-4 flex gap-2">
            <button class="flex-1 rounded-lg bg-white/20 px-3 py-2 text-xs font-medium backdrop-blur transition hover:bg-white/30">
                Details
            </button>
            <button class="flex-1 rounded-lg bg-white/20 px-3 py-2 text-xs font-medium backdrop-blur transition hover:bg-white/30">
                Export
            </button>
        </div>
    </div>
</div>

<style>
/* Hide scrollbar for swipeable metrics */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>