

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'Unknown',
    'verified' => null,
    'verificationSource' => null,
    'additionalInfo' => null,
    'phone' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'name' => 'Unknown',
    'verified' => null,
    'verificationSource' => null,
    'additionalInfo' => null,
    'phone' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
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
?>

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
    <span class="font-medium"><?php echo e($name); ?></span>

    
    <button
        type="button"
        class="inline-flex items-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 rounded-full"
        @click.stop="toggle()"
        aria-label="Verification Status"
    >
        <!--[if BLOCK]><![endif]--><?php if($verified === true): ?>
            
            <svg class="<?php echo e($iconClass); ?> <?php echo e($iconColor); ?>" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        <?php else: ?>
            
            <svg class="<?php echo e($iconClass); ?> <?php echo e($iconColor); ?>" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </button>

    
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
            
            <div class="absolute -top-1 left-6 w-2 h-2 bg-gray-900 dark:bg-gray-800 border-l border-t border-gray-700 transform rotate-45"></div>

            
            <div class="relative">
                <div class="font-semibold mb-1"><?php echo e($tooltipTitle); ?></div>
                <div class="text-gray-300 dark:text-gray-400"><?php echo e($tooltipDetails); ?></div>
            </div>
        </div>
    </div>
</div>


<?php if (! $__env->hasRenderedOnce('9c9fb537-c09c-478b-b382-3b0684271b8c')): $__env->markAsRenderedOnce('9c9fb537-c09c-478b-b382-3b0684271b8c'); ?>
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
<?php endif; ?>
<?php /**PATH /var/www/api-gateway/resources/views/components/mobile-verification-badge.blade.php ENDPATH**/ ?>