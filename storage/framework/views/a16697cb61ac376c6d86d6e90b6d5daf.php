<?php
    $record = $getRecord();

    // Company and Branch names - SHOW BOTH
    $branchLink = null;
    $companyLink = null;
    $branchName = null;
    $companyName = null;

    // Get company name
    if ($record->company_id && $record->company) {
        $companyName = $record->company->name ?? 'Unternehmen';
        try {
            $companyLink = route('filament.admin.resources.companies.view', ['record' => $record->company_id]);
        } catch (\Exception $e) {
            $companyLink = null;
        }
    }

    // Get branch name
    if ($record->branch_id && $record->branch) {
        $branchName = $record->branch->name ?? 'Filiale';
        try {
            $branchLink = route('filament.admin.resources.branches.view', ['record' => $record->branch_id]);
        } catch (\Exception $e) {
            $branchLink = null;
        }
    }

    // Phone number from phoneNumber relationship
    $phoneNumber = null;
    if ($record->phoneNumber && $record->phoneNumber->number) {
        $phoneNumber = $record->phoneNumber->number;
    }

    // Tooltip - Show hierarchy
    $tooltipText = "🏢 Unternehmen: " . ($companyName ?? 'Unbekannt');
    if ($branchName) {
        $tooltipText .= "\n🏪 Filiale: " . $branchName;
    }
    if ($phoneNumber) {
        $tooltipText .= "\n📞 " . $phoneNumber;
    }
?>

<div class="space-y-1" title="<?php echo e($tooltipText); ?>">
    <!-- Zeile 1: Unternehmen Name (klickbar wenn Link vorhanden) -->
    <div class="text-xs font-medium text-gray-800">
        <!--[if BLOCK]><![endif]--><?php if($companyLink && $companyName): ?>
            <a href="<?php echo e($companyLink); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                <?php echo e($companyName); ?>

            </a>
        <?php elseif($companyName): ?>
            <?php echo e($companyName); ?>

        <?php else: ?>
            Unbekannt
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!-- Zeile 2: Filiale Name (klickbar wenn Link vorhanden) - nur wenn vorhanden -->
    <!--[if BLOCK]><![endif]--><?php if($branchName): ?>
        <div class="text-xs text-gray-600">
            <!--[if BLOCK]><![endif]--><?php if($branchLink): ?>
                <a href="<?php echo e($branchLink); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                    <?php echo e($branchName); ?>

                </a>
            <?php else: ?>
                <?php echo e($branchName); ?>

            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- Zeile 3: Phone Number (klickbar zum kopieren) -->
    <!--[if BLOCK]><![endif]--><?php if($phoneNumber): ?>
        <div class="flex items-center gap-2 group">
            <button
                type="button"
                onclick="copyToClipboard('<?php echo e(addslashes($phoneNumber)); ?>', this)"
                class="text-xs text-gray-600 font-mono hover:bg-gray-100 px-1 py-0.5 rounded transition-colors flex items-center gap-1"
                title="Klicken zum Kopieren"
            >
                <?php echo e($phoneNumber); ?>

                <span class="text-gray-400 text-xs opacity-0 group-hover:opacity-100 transition-opacity">copy</span>
            </button>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /var/www/api-gateway/resources/views/filament/columns/company-phone.blade.php ENDPATH**/ ?>