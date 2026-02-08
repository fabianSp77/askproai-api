{{-- Premium Card Component --}}
@props([
    'elevated' => false,
    'glass' => false,
])

<div {{ $attributes->merge([
    'class' => collect([
        'premium-card',
        $elevated ? 'premium-card-elevated' : '',
        $glass ? 'premium-glass' : '',
    ])->filter()->implode(' ')
]) }}>
    {{ $slot }}
</div>
