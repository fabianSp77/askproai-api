{{-- Premium Mini KPI Component --}}
@props([
    'icon' => null,
    'iconColor' => 'primary', // primary, success, warning, purple
    'label' => '',
    'value' => '',
    'change' => null,
])

<div class="premium-mini-kpi">
    @if($icon)
        <div class="premium-mini-kpi-icon premium-mini-kpi-icon-{{ $iconColor }}">
            <x-dynamic-component :component="$icon" class="w-5 h-5" />
        </div>
    @endif
    <div class="premium-mini-kpi-content">
        <span class="premium-mini-kpi-label">{{ $label }}</span>
        <div class="flex items-center gap-2">
            <span class="premium-mini-kpi-value">{{ $value }}</span>
            @if($change !== null)
                <span class="{{ $change >= 0 ? 'premium-change-positive' : 'premium-change-negative' }} text-xs">
                    {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1) }}%
                </span>
            @endif
        </div>
    </div>
</div>
