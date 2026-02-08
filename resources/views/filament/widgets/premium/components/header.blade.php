{{-- Premium Widget Header Component --}}
@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeLabel' => null,
])

<div class="premium-widget-header">
    <div class="premium-widget-header-left">
        <span class="premium-widget-title">{{ $title }}</span>
        <div class="premium-widget-value">
            {{ $value }}
            @if($change !== null)
                <span class="{{ $change >= 0 ? 'premium-change premium-change-positive' : 'premium-change premium-change-negative' }}">
                    @if($change >= 0)
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    @endif
                    {{ number_format(abs($change), 1) }}%
                </span>
            @endif
        </div>
        @if($changeLabel)
            <span class="premium-text-muted text-xs">{{ $changeLabel }}</span>
        @endif
    </div>
    @if(isset($actions))
        <div class="premium-widget-header-right">
            {{ $actions }}
        </div>
    @endif
</div>
