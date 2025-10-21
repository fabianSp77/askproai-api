{{--
  Inline Expandable Verification Badge Component

  Alternative to mobile-verification-badge.blade.php
  Shows a small badge that expands inline to show details when clicked.

  Props:
  - $name: Customer name
  - $verified: Verification status (true, false, null)
  - $verificationSource: Source of verification
  - $additionalInfo: Extra details
  - $phone: Customer phone
--}}

@props([
    'name' => 'Unknown',
    'verified' => null,
    'verificationSource' => null,
    'additionalInfo' => null,
    'phone' => null,
])

@php
    // Determine badge appearance based on verification status
    $badgeColor = '';
    $badgeText = '';
    $detailsText = '';

    if ($verified === true) {
        $badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        $badgeText = '✓';

        if ($verificationSource === 'customer_linked') {
            $detailsText = 'Mit Kundenprofil verknüpft - 100% Sicherheit';
        } elseif ($verificationSource === 'phone_verified') {
            $detailsText = 'Telefonnummer bekannt - 99% Sicherheit';
        } elseif ($verificationSource === 'phonetic_match') {
            $detailsText = $additionalInfo
                ? "Phonetisch erkannt - {$additionalInfo}"
                : 'Phonetisch erkannt - Hohe Wahrscheinlichkeit';
        } else {
            $detailsText = 'Kunde erfolgreich verifiziert';
        }
    } elseif ($verified === false) {
        $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
        $badgeText = '!';

        if ($verificationSource === 'ai_extracted') {
            $detailsText = 'Name aus Gespräch extrahiert - Niedrige Sicherheit';
        } else {
            $detailsText = 'Nicht verifiziert - Manuelle Prüfung empfohlen';
        }
    } else {
        // No verification status
        echo '<span>' . e($name) . '</span>';
        return;
    }

    if ($phone) {
        $detailsText .= " | Tel: {$phone}";
    }
@endphp

<div
    x-data="{ expanded: false }"
    class="inline-flex flex-col gap-1"
>
    {{-- Name with Badge --}}
    <div class="inline-flex items-center gap-2">
        <span class="font-medium">{{ $name }}</span>

        {{-- Clickable Badge (expands inline) --}}
        <button
            type="button"
            @click.stop="expanded = !expanded"
            class="{{ $badgeColor }} inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold cursor-pointer hover:scale-110 transition-transform focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            aria-label="Toggle verification details"
        >
            {{ $badgeText }}
        </button>
    </div>

    {{-- Expandable Details (slides down when clicked) --}}
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="text-xs {{ $verified ? 'text-green-700 dark:text-green-300' : 'text-orange-700 dark:text-orange-300' }} bg-gray-50 dark:bg-gray-800 rounded px-2 py-1 border {{ $verified ? 'border-green-200 dark:border-green-800' : 'border-orange-200 dark:border-orange-800' }}"
        style="display: none;"
    >
        {{ $detailsText }}
    </div>
</div>
