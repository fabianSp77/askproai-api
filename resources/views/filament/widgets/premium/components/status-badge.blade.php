{{-- Premium Status Badge Component --}}
@props([
    'status' => 'pending', // paid, unpaid, pending, overdue
    'label' => null,
])

@php
    $statusConfig = [
        'paid' => ['class' => 'premium-badge-success', 'label' => 'Bezahlt'],
        'unpaid' => ['class' => 'premium-badge-warning', 'label' => 'Offen'],
        'pending' => ['class' => 'premium-badge-purple', 'label' => 'Ausstehend'],
        'overdue' => ['class' => 'premium-badge-error', 'label' => 'Überfällig'],
    ];
    $config = $statusConfig[$status] ?? $statusConfig['pending'];
    $displayLabel = $label ?? $config['label'];
@endphp

<span class="premium-badge {{ $config['class'] }}">
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $displayLabel }}
</span>
