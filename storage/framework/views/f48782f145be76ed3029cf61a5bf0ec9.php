<?php
    $record = $getRecord();
    $nonNamePhrases = ['mir nicht', 'guten tag', 'guten morgen', 'hallo', 'ja', 'nein', 'gleich fertig', 'ja bitte', 'danke'];

    // 🔧 FIX 2025-11-12: Neue Logik für Namen-Anzeige
    // MIT Appointment: Namen vom Termin (appointment.customer.name)
    // OHNE Appointment: Namen aus dem Telefonat (call.customer_name aus Transcript)

    $displayName = null;
    $customerLink = null;
    $isAnonymous = false;

    // PRIORITY 1: Hat der Call ein Appointment? → Nutze Appointment-Customer
    if ($record->appointments && $record->appointments->isNotEmpty()) {
        $appointment = $record->appointments->first();
        if ($appointment->customer) {
            $displayName = $appointment->customer->name;
            $customerLink = route('filament.admin.resources.customers.view', $appointment->customer_id);
        }
    }

    // PRIORITY 2: Kein Appointment → Nutze Call-Customer oder extrahierten Namen aus Transcript
    if (!$displayName) {
        if ($record->customer_id && $record->customer) {
            $displayName = $record->customer->name;
            $customerLink = route('filament.admin.resources.customers.view', $record->customer_id);
        } else {
            // 🔧 FIX: Use actual DB column, not accessor (accessor checks metadata/customer relationship)
            $dbCustomerName = $record->getAttributes()['customer_name'] ?? null;

            if ($dbCustomerName) {
                $customerNameLower = strtolower(trim($dbCustomerName));
                $isTranscriptFragment = in_array($customerNameLower, $nonNamePhrases);

                if (!$isTranscriptFragment) {
                    $displayName = $dbCustomerName;
                }
            }
        }
    }

    // FALLBACK: Anonymer Anrufer
    if (!$displayName) {
        $displayName = "Anonymer Anrufer";
        $isAnonymous = true;
    }

    // Phone number
    $phoneNumber = $record->from_number ?? $record->to_number;
    if ($phoneNumber === 'anonymous' || !$phoneNumber) {
        $phoneDisplay = "Nicht übertragen";
        $showCopy = false;
    } else {
        $phoneDisplay = $phoneNumber;
        $showCopy = true;
    }

    // Email from customer
    $customerEmail = $record->customer?->email ?? null;

    // Tooltip
    $tooltipText = $displayName;
    if ($showCopy) {
        $tooltipText .= "\n" . $phoneNumber;
    } else {
        $tooltipText .= "\nRufnummer nicht übertragen";
    }
    if ($customerEmail) {
        $tooltipText .= "\n" . $customerEmail;
    }
?>

<div class="space-y-1" title="<?php echo e($tooltipText); ?>">
    <!-- Zeile 1: Name (klickbar wenn Customer) -->
    <div class="text-sm font-medium text-gray-800">
        <!--[if BLOCK]><![endif]--><?php if($customerLink): ?>
            <a href="<?php echo e($customerLink); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                <?php echo e($displayName); ?>

            </a>
        <?php else: ?>
            <?php echo e($displayName); ?>

        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!-- Zeile 2: Telefonnummer (klickbar zum kopieren) -->
    <div class="flex items-center gap-2 group">
        <!--[if BLOCK]><![endif]--><?php if($showCopy): ?>
            <button
                type="button"
                onclick="copyToClipboard('<?php echo e(addslashes($phoneNumber)); ?>', this)"
                class="text-xs text-gray-600 font-mono hover:bg-gray-100 px-1 py-0.5 rounded transition-colors flex items-center gap-1"
                title="Klicken zum Kopieren"
            >
                <?php echo e($phoneDisplay); ?>

                <span class="text-gray-400 text-xs opacity-0 group-hover:opacity-100 transition-opacity">copy</span>
            </button>
        <?php else: ?>
            <span class="text-xs text-gray-600 font-mono">
                <?php echo e($phoneDisplay); ?>

            </span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!-- Zeile 3: Email (falls vorhanden, auch klickbar zum kopieren) -->
    <!--[if BLOCK]><![endif]--><?php if($customerEmail): ?>
        <div class="flex items-center gap-2 group">
            <button
                type="button"
                onclick="copyToClipboard('<?php echo e(addslashes($customerEmail)); ?>', this)"
                class="text-xs text-gray-600 font-mono hover:bg-gray-100 px-1 py-0.5 rounded transition-colors flex items-center gap-1"
                title="Klicken zum Kopieren"
            >
                <?php echo e($customerEmail); ?>

                <span class="text-gray-400 text-xs opacity-0 group-hover:opacity-100 transition-opacity">copy</span>
            </button>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>

<script>
if (typeof copyToClipboard === 'undefined') {
    function copyToClipboard(text, element) {
        navigator.clipboard.writeText(text).then(() => {
            // Visual feedback: briefly show success state
            const originalText = element.textContent;
            const originalBg = element.classList.contains('hover:bg-gray-100');

            element.classList.add('bg-green-100');
            element.textContent = 'Kopiert!';
            element.style.color = '#059669';

            setTimeout(() => {
                element.classList.remove('bg-green-100');
                element.textContent = originalText;
                element.style.color = '';
            }, 1500);
        }).catch(() => {
            alert('Fehler beim Kopieren');
        });
    }
}
</script>
<?php /**PATH /var/www/api-gateway/resources/views/filament/columns/anrufer-3lines.blade.php ENDPATH**/ ?>