{{--
  Mobile-Friendly Verification Badge Component

  Usage: Shows customer verification status with:
  - Desktop: Hover tooltip (keeps existing behavior)
  - Mobile/Tablet: Click/tap to toggle tooltip

  Props:
  - $name: Customer name
  - $verified: Verification status (true, false, null)
  - $verificationSource: Source of verification ('customer_linked', 'phone_verified', 'ai_extracted', 'phonetic_match')
  - $additionalInfo: Extra details (e.g., similarity score)
--}}

@props([
    'name' => 'Unknown',
    'verified' => null,
    'verificationSource' => null,
    'additionalInfo' => null,
    'phone' => null,
])

@php
    // Determine verification icon and tooltip message based on status
    $iconClass = '';
    $iconColor = '';
    $tooltipTitle = '';
    $tooltipDetails = '';

    if ($verified === true) {
        // Green checkmark badge (verified)
        $iconClass = 'w-4 h-4';
        $iconColor = 'text-green-600 dark:text-green-400';

        if ($verificationSource === 'customer_linked') {
            $tooltipTitle = '✅ Verifizierter Kunde';
            $tooltipDetails = 'Mit Kundenprofil verknüpft';
            if ($additionalInfo) {
                $tooltipDetails .= " - Übereinstimmung: {$additionalInfo}";
            }
        } elseif ($verificationSource === 'phone_verified') {
            $tooltipTitle = '✅ Verifiziert via Telefon';
            $tooltipDetails = 'Telefonnummer bekannt - 99% Sicherheit';
        } elseif ($verificationSource === 'retell_agent') {
            $tooltipTitle = '✅ Von Retell Agent übermittelt';
            $tooltipDetails = 'Name vom KI-Agent bereitgestellt - Hohe Qualität';
        } elseif ($verificationSource === 'phonetic_match') {
            $tooltipTitle = '✅ Phonetische Übereinstimmung';
            $tooltipDetails = $additionalInfo
                ? "Name phonetisch erkannt - {$additionalInfo}"
                : 'Name phonetisch erkannt - Hohe Wahrscheinlichkeit';
        } else {
            $tooltipTitle = '✅ Verifiziert';
            $tooltipDetails = 'Kunde wurde erfolgreich verifiziert';
        }
    } elseif ($verified === false) {
        // Orange warning badge (unverified)
        $iconClass = 'w-4 h-4';
        $iconColor = 'text-orange-600 dark:text-orange-400';
        $tooltipTitle = '⚠️ Unverifiziert';

        if ($verificationSource === 'ai_extracted') {
            $tooltipDetails = 'Name aus Gespräch extrahiert - Niedrige Sicherheit';
        } elseif ($verificationSource === 'retell_agent') {
            $tooltipDetails = 'Von Retell Agent übermittelt - Mittlere Sicherheit';
        } else {
            $tooltipDetails = 'Name nicht verifiziert - Manuelle Prüfung empfohlen';
        }

        // Add additional context if provided (e.g., "Anonyme Telefonnummer")
        if ($additionalInfo) {
            $tooltipDetails .= " | {$additionalInfo}";
        }
    } else {
        // No verification status - return just the name
        echo '<span>' . e($name) . '</span>';
        return;
    }

    // Add phone to tooltip if available
    if ($phone) {
        $tooltipDetails .= " | Tel: {$phone}";
    }
@endphp

<div
    x-data="{
        showTooltip: false,
        isMobile: window.matchMedia('(max-width: 768px)').matches,
        toggle() {
            if (this.isMobile) {
                this.showTooltip = !this.showTooltip;
            }
        },
        show() {
            if (!this.isMobile) {
                this.showTooltip = true;
            }
        },
        hide() {
            if (!this.isMobile) {
                this.showTooltip = false;
            }
        }
    }"
    class="inline-flex items-center gap-1 relative"
    @mouseenter="show()"
    @mouseleave="hide()"
    @click.stop="toggle()"
>
    <span class="font-medium">{{ $name }}</span>

    {{-- Verification Icon (clickable on mobile) --}}
    <button
        type="button"
        class="inline-flex items-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 rounded-full"
        @click.stop="toggle()"
        aria-label="Verification Status"
    >
        @if ($verified === true)
            {{-- Green Checkmark Badge --}}
            <svg class="{{ $iconClass }} {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        @else
            {{-- Orange Warning Badge --}}
            <svg class="{{ $iconClass }} {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
        @endif
    </button>

    {{-- Tooltip (appears on hover for desktop, click for mobile) --}}
    <div
        x-show="showTooltip"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="showTooltip = false"
        class="absolute z-50 left-0 top-full mt-2 w-max max-w-xs"
        style="display: none;"
    >
        <div class="bg-gray-900 dark:bg-gray-800 text-white text-xs rounded-lg shadow-lg px-3 py-2 border border-gray-700">
            {{-- Tooltip Arrow --}}
            <div class="absolute -top-1 left-6 w-2 h-2 bg-gray-900 dark:bg-gray-800 border-l border-t border-gray-700 transform rotate-45"></div>

            {{-- Tooltip Content --}}
            <div class="relative">
                <div class="font-semibold mb-1">{{ $tooltipTitle }}</div>
                <div class="text-gray-300 dark:text-gray-400">{{ $tooltipDetails }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Mobile-specific hint (only shown on first load) --}}
@once
<script>
    // Add subtle pulse animation to verification badges on mobile to indicate they're tappable
    if (window.matchMedia('(max-width: 768px)').matches) {
        document.addEventListener('DOMContentLoaded', function() {
            const badges = document.querySelectorAll('[x-data*="showTooltip"] button');
            badges.forEach(badge => {
                // Add tap hint on first load (removed after 3 seconds)
                badge.style.animation = 'pulse 2s ease-in-out 3';
                setTimeout(() => {
                    badge.style.animation = '';
                }, 6000);
            });
        });
    }
</script>
@endonce
