
<div class="appointment-booking-flow space-y-6">

    
    <div class="fi-section">
        <div class="fi-section-header">🏢 Filiale auswählen</div>

        <!--[if BLOCK]><![endif]--><?php if(count($availableBranches) > 1): ?>
            <div class="fi-radio-group">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $availableBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label
                        class="fi-radio-option <?php echo e($selectedBranchId === $branch['id'] ? 'selected' : ''); ?>"
                        wire:key="branch-<?php echo e($branch['id']); ?>">
                        <input
                            type="radio"
                            name="branch"
                            value="<?php echo e($branch['id']); ?>"
                            wire:model.live="selectedBranchId"
                            wire:click="selectBranch(<?php echo e($branch['id']); ?>)"
                            class="fi-radio-input">
                        <div class="flex-1">
                            <div class="font-medium text-sm"><?php echo e($branch['name']); ?></div>
                            <!--[if BLOCK]><![endif]--><?php if(!empty($branch['address'])): ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($branch['address']); ?></div>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php elseif(count($availableBranches) === 1): ?>
            <div class="text-sm text-gray-600 py-2">
                <strong><?php echo e($availableBranches[0]['name']); ?></strong>
                <!--[if BLOCK]><![endif]--><?php if(!empty($availableBranches[0]['address'])): ?>
                    <span class="text-gray-400">- <?php echo e($availableBranches[0]['address']); ?></span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php else: ?>
            <div class="text-sm text-red-600">Keine Filiale verfügbar</div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <div class="fi-section">
        <div class="fi-section-header">Service auswählen</div>

        <div class="fi-radio-group">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $availableServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label
                    class="fi-radio-option <?php echo e($selectedServiceId === $service['id'] ? 'selected' : ''); ?>"
                    wire:key="service-<?php echo e($service['id']); ?>">
                    <input
                        type="radio"
                        name="service"
                        value="<?php echo e($service['id']); ?>"
                        wire:model.live="selectedServiceId"
                        wire:change="selectService('<?php echo e($service['id']); ?>')"
                        class="fi-radio-input">
                    <div class="flex-1">
                        <div class="font-medium text-sm"><?php echo e($service['name']); ?></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($service['duration_minutes']); ?> Minuten</div>
                    </div>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        <!--[if BLOCK]><![endif]--><?php if(count($availableServices) === 0): ?>
            <div class="text-sm text-gray-400 text-center py-4">
                Keine Services verfügbar
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <div class="fi-section">
        <div class="fi-section-header">Mitarbeiter-Präferenz</div>

        <div class="fi-radio-group">
            
            <label class="fi-radio-option <?php echo e($employeePreference === 'any' ? 'selected' : ''); ?>">
                <input
                    type="radio"
                    name="employee"
                    value="any"
                    wire:model.live="employeePreference"
                    wire:change="selectEmployee('any')"
                    class="fi-radio-input">
                <div class="flex-1">
                    <div class="font-medium text-sm">Nächster verfügbarer Mitarbeiter</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Maximale Auswahl an Terminen</div>
                </div>
            </label>

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $availableEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label
                    class="fi-radio-option <?php echo e($employeePreference === $employee['id'] ? 'selected' : ''); ?>"
                    wire:key="employee-<?php echo e($employee['id']); ?>">
                    <input
                        type="radio"
                        name="employee"
                        value="<?php echo e($employee['id']); ?>"
                        wire:model.live="employeePreference"
                        wire:change="selectEmployee('<?php echo e($employee['id']); ?>')"
                        class="fi-radio-input">
                    <div class="flex-1">
                        <div class="font-medium text-sm"><?php echo e($employee['name']); ?></div>
                        <!--[if BLOCK]><![endif]--><?php if(!empty($employee['email'])): ?>
                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($employee['email']); ?></div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>

    
    <div class="fi-section">
        <div class="fi-section-header">
            Verfügbare Termine
            <!--[if BLOCK]><![endif]--><?php if($serviceName): ?>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    (<?php echo e($serviceName); ?> - <?php echo e($serviceDuration); ?> Min)
                </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        
        <div class="flex items-center justify-between mb-4">
            <button
                wire:click="previousWeek"
                class="fi-button-nav"
                wire:loading.attr="disabled">
                ← Vorherige Woche
            </button>

            <div class="text-sm font-semibold text-gray-200">
                <!--[if BLOCK]><![endif]--><?php if(isset($weekMetadata['start_date']) && isset($weekMetadata['end_date'])): ?>
                    <?php echo e($weekMetadata['start_date']); ?> - <?php echo e($weekMetadata['end_date']); ?>

                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>

            <button
                wire:click="nextWeek"
                class="fi-button-nav"
                wire:loading.attr="disabled">
                Nächste Woche →
            </button>
        </div>

        
        <!--[if BLOCK]><![endif]--><?php if($loading): ?>
            <div class="text-center py-8">
                <div class="fi-loading-spinner"></div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">Lade Verfügbarkeiten...</div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <!--[if BLOCK]><![endif]--><?php if($error): ?>
            <div class="fi-error-alert">
                <strong>⚠️ Fehler:</strong> <?php echo e($error); ?>

            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <!--[if BLOCK]><![endif]--><?php if(!$loading && !$error): ?>
            <div class="fi-calendar-grid">
                
                <div class="fi-calendar-header" style="grid-column: 1;">Zeit</div>
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="fi-calendar-header">
                        <?php echo e($this->getDayLabel($dayKey)); ?>

                        <!--[if BLOCK]><![endif]--><?php if(isset($weekMetadata['days'][$dayKey])): ?>
                            <br><span class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($weekMetadata['days'][$dayKey]); ?></span>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = range(8, 18); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $timeLabel = sprintf('%02d:00', $hour);
                    ?>

                    
                    <div class="fi-time-label fi-calendar-cell"><?php echo e($timeLabel); ?></div>

                    
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="fi-calendar-cell">
                            <?php
                                // Find slots for this hour on this day
                                $daySlots = $weekData[$dayKey] ?? [];
                                $slotForHour = collect($daySlots)->first(function($slot) use ($timeLabel) {
                                    return str_starts_with($slot['time'], $timeLabel);
                                });
                            ?>

                            <!--[if BLOCK]><![endif]--><?php if($slotForHour): ?>
                                <button
                                    wire:click="selectSlot('<?php echo e($slotForHour['full_datetime']); ?>', '<?php echo e($slotForHour['day_name']); ?> um <?php echo e($slotForHour['time']); ?>')"
                                    class="fi-slot-button <?php echo e($this->isSlotSelected($slotForHour['full_datetime']) ? 'selected' : ''); ?>"
                                    wire:loading.attr="disabled">
                                    <?php echo e($slotForHour['time']); ?>

                                </button>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>

            
            <div class="fi-info-banner">
                <strong>Info:</strong>
                Slots basieren auf <?php echo e($serviceDuration); ?> Minuten Dauer.
                <!--[if BLOCK]><![endif]--><?php if($employeePreference === 'any'): ?>
                    Zeigt alle verfügbaren Mitarbeiter.
                <?php else: ?>
                    <?php
                        $selectedEmp = collect($availableEmployees)->firstWhere('id', $employeePreference);
                    ?>
                    <!--[if BLOCK]><![endif]--><?php if($selectedEmp): ?>
                        Zeigt nur Termine von <?php echo e($selectedEmp['name']); ?>.
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <div class="fi-section">
        <div class="fi-section-header">👤 Kunde auswählen</div>

        <div class="mb-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="customerSearchQuery"
                placeholder="Name, E-Mail oder Telefon eingeben..."
                class="fi-search-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>

        <!--[if BLOCK]><![endif]--><?php if($selectedCustomerId && $selectedCustomerName): ?>
            <div class="fi-selected-customer">
                <div class="flex items-center justify-between p-3 bg-success-50 border border-success-500 rounded-lg">
                    <div>
                        <div class="font-medium text-sm text-success-900">✓ <?php echo e($selectedCustomerName); ?></div>
                        <div class="text-xs text-success-700">Kunde ausgewählt</div>
                    </div>
                    <button
                        wire:click="$set('selectedCustomerId', null); $set('selectedCustomerName', null); $set('customerSearchQuery', '');"
                        class="text-success-700 hover:text-success-900 text-sm">
                        Ändern
                    </button>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <!--[if BLOCK]><![endif]--><?php if(strlen($customerSearchQuery) >= 3 && !$selectedCustomerId): ?>
            <div class="fi-search-results-container">
                
                <!--[if BLOCK]><![endif]--><?php if(count($searchResults) > 0): ?>
                    <div class="fi-search-results border border-gray-200 rounded-lg overflow-hidden mb-2">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $searchResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button
                                wire:click="selectCustomer(<?php echo e($customer['id']); ?>)"
                                class="fi-customer-result w-full text-left p-3 hover:bg-gray-50 dark:hover:bg-gray-800 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition">
                                <div class="font-medium text-sm text-gray-900 dark:text-gray-100"><?php echo e($customer['name']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <!--[if BLOCK]><![endif]--><?php if(!empty($customer['email'])): ?>
                                        <?php echo e($customer['email']); ?>

                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    <!--[if BLOCK]><![endif]--><?php if(!empty($customer['phone'])): ?>
                                        <span class="ml-2"><?php echo e($customer['phone']); ?></span>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    
                    <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">
                        oder
                    </div>
                <?php else: ?>
                    
                    <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-3 mb-2">
                        Kein Kunde mit "<?php echo e($customerSearchQuery); ?>" gefunden.
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <button
                    wire:click="showCreateCustomerForm"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-lg bg-success-600 text-white hover:bg-success-700 focus:ring-2 focus:ring-success-500 dark:bg-success-700 dark:hover:bg-success-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>➕ Neuen Kunden "<?php echo e($customerSearchQuery); ?>" anlegen</span>
                </button>
            </div>
        <?php elseif(strlen($customerSearchQuery) > 0 && strlen($customerSearchQuery) < 3 && !$selectedCustomerId): ?>
            <div class="text-xs text-gray-400 dark:text-gray-500 py-2">
                Mindestens 3 Zeichen eingeben...
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <!--[if BLOCK]><![endif]--><?php if($showNewCustomerForm): ?>
            <div class="fi-new-customer-form mt-4 p-4 border-2 border-success-500 rounded-lg bg-success-50 dark:bg-success-950">
                <div class="text-sm font-medium text-success-900 dark:text-success-100 mb-3">
                    ➕ Neuen Kunden anlegen
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Name *
                        </label>
                        <input
                            type="text"
                            wire:model="newCustomerName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="Vollständiger Name">
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['newCustomerName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-xs text-danger-600 mt-1"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Telefon
                        </label>
                        <input
                            type="tel"
                            wire:model="newCustomerPhone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="+49 123 456789">
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['newCustomerPhone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-xs text-danger-600 mt-1"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            E-Mail
                        </label>
                        <input
                            type="email"
                            wire:model="newCustomerEmail"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="kunde@example.com">
                        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['newCustomerEmail'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="text-xs text-danger-600 mt-1"><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            wire:click="createNewCustomer"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-success-600 text-white hover:bg-success-700 focus:ring-2 focus:ring-success-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Speichern
                        </button>
                        <button
                            wire:click="cancelCreateCustomer"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-2 focus:ring-gray-500">
                            Abbrechen
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <!--[if BLOCK]><![endif]--><?php if($selectedSlot): ?>
        <div class="fi-section fi-selected-confirmation">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold text-green-100 text-lg">Zeitslot ausgewählt</div>
                    <div class="text-sm text-green-200 mt-1"><?php echo e($selectedSlotLabel); ?></div>
                    <div class="text-xs text-green-300 mt-1">
                        Service: <?php echo e($serviceName); ?> (<?php echo e($serviceDuration); ?> Min)
                    </div>
                </div>
                <button
                    wire:click="$set('selectedSlot', null)"
                    class="px-4 py-2 bg-green-700 hover:bg-green-600 rounded text-sm text-white transition">
                    Ändern
                </button>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

</div>


<style>
    /* Sections - use Filament's background colors */
    .fi-section {
        background-color: var(--color-gray-50);
        border: 1px solid var(--color-gray-200);
        border-radius: 0.75rem;
        padding: 1.5rem;
    }

    .dark .fi-section {
        background-color: var(--color-gray-800);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-700 */
    }

    .fi-section-header {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--color-gray-900);
        margin-bottom: 1rem;
    }

    .dark .fi-section-header {
        color: var(--color-gray-100);
    }

    .fi-radio-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Radio Options - Filament form style */
    .fi-radio-option {
        flex: 1;
        min-width: 200px;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        background-color: var(--color-white);
        border: 2px solid var(--color-gray-300);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .dark .fi-radio-option {
        background-color: var(--color-gray-700);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-radio-option:hover {
        background-color: var(--color-gray-50);
        border-color: var(--color-primary-500);
    }

    .dark .fi-radio-option:hover {
        background-color: var(--color-gray-600);
        border-color: var(--color-primary-400);
    }

    .fi-radio-option.selected {
        background-color: var(--color-primary-50);
        border-color: var(--color-primary-600);
    }

    .dark .fi-radio-option.selected {
        background-color: var(--color-primary-900);
        border-color: var(--color-primary-500);
    }

    /* NEW: Focus indicators for keyboard navigation (WCAG 2.4.7) */
    .fi-radio-option:focus-within {
        outline: 2px solid var(--color-primary-500);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .dark .fi-radio-option:focus-within {
        outline-color: var(--color-primary-400);
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
    }

    .fi-radio-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.75rem;
        cursor: pointer;
    }

    /* Calendar Grid */
    .fi-calendar-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        gap: 1px;
        background-color: var(--color-gray-300);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark .fi-calendar-grid {
        background-color: var(--color-gray-500); /* FIXED: Improved contrast for grid lines */
    }

    .fi-calendar-cell {
        background-color: var(--color-white);
        padding: 0.75rem;
        min-height: 60px;
    }

    .dark .fi-calendar-cell {
        background-color: var(--color-gray-800);
    }

    .fi-calendar-header {
        background-color: var(--color-gray-100);
        padding: 1rem;
        text-align: center;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--color-gray-700);
    }

    .dark .fi-calendar-header {
        background-color: var(--color-gray-700);
        color: var(--color-gray-200);
    }

    /* Slot Buttons */
    .fi-slot-button {
        width: 100%;
        padding: 0.625rem 0.5rem;
        background-color: var(--color-primary-600);
        color: white;
        border: 2px solid var(--color-primary-700);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .fi-slot-button:hover {
        background-color: var(--color-primary-700);
        border-color: var(--color-primary-800);
        transform: translateY(-1px);
    }

    .fi-slot-button.selected {
        background-color: var(--color-success-600);
        color: white;
        border-color: var(--color-success-700);
    }

    .fi-slot-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* NEW: Focus indicator for slot buttons */
    .fi-slot-button:focus {
        outline: 2px solid var(--color-white);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px var(--color-primary-400);
    }

    .fi-time-label {
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        color: var(--color-gray-500);
        font-weight: 500;
        background-color: var(--color-gray-50);
        position: sticky;
        left: 0;
        z-index: 10;
    }

    .dark .fi-time-label {
        color: var(--color-gray-400);
        background-color: var(--color-gray-800);
    }

    /* Info Banner */
    .fi-info-banner {
        background-color: var(--color-info-50);
        border: 1px solid var(--color-info-200);
        border-radius: 0.5rem;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        color: var(--color-info-700);
        margin-top: 1rem;
    }

    .dark .fi-info-banner {
        background-color: var(--color-info-900);
        border-color: var(--color-info-700);
        color: var(--color-info-200);
    }

    /* Navigation Buttons */
    .fi-button-nav {
        padding: 0.5rem 1rem;
        background-color: var(--color-white);
        color: var(--color-gray-700);
        border: 1px solid var(--color-gray-300);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .dark .fi-button-nav {
        background-color: var(--color-gray-700);
        color: var(--color-gray-200);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-50);
        border-color: var(--color-gray-400);
    }

    .dark .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-600);
        border-color: var(--color-gray-400); /* FIXED: Better hover contrast */
    }

    .fi-button-nav:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* NEW: Focus indicator for navigation buttons */
    .fi-button-nav:focus {
        outline: 2px solid var(--color-primary-500);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .dark .fi-button-nav:focus {
        outline-color: var(--color-primary-400);
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
    }

    /* Selected Confirmation */
    .fi-selected-confirmation {
        background-color: var(--color-success-50);
        border-color: var(--color-success-500);
    }

    .dark .fi-selected-confirmation {
        background-color: var(--color-success-900);
        border-color: var(--color-success-600);
    }

    /* NEW: Search Input */
    .fi-search-input {
        background-color: var(--color-white);
        color: var(--color-gray-900);
        border: 1px solid var(--color-gray-300);
    }

    .dark .fi-search-input {
        background-color: var(--color-gray-700);
        color: var(--color-gray-100);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-search-input:focus {
        outline: none;
        ring: 2px;
        ring-color: var(--color-primary-500);
        border-color: var(--color-primary-500);
    }

    /* NEW: Search Results */
    .fi-search-results {
        background-color: var(--color-white);
        border: 1px solid var(--color-gray-200);
        border-radius: 0.5rem;
        overflow: hidden;
        max-height: 300px;
        overflow-y: auto;
    }

    .dark .fi-search-results {
        background-color: var(--color-gray-700);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-customer-result {
        transition: background-color 0.15s ease;
    }

    .fi-customer-result:hover {
        background-color: var(--color-gray-50);
    }

    .dark .fi-customer-result:hover {
        background-color: var(--color-gray-700);
    }

    .fi-customer-result:last-child {
        border-bottom: none;
    }

    /* NEW: Selected Customer */
    .fi-selected-customer {
        margin-top: 0.5rem;
    }

    /* NEW: Error Alert - Improved visibility */
    .fi-error-alert {
        background-color: var(--color-danger-50);
        border: 2px solid var(--color-danger-500); /* Stronger border */
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.875rem;
        color: var(--color-danger-700);
        margin-bottom: 1rem;
    }

    .dark .fi-error-alert {
        background-color: var(--color-danger-900);
        border-color: var(--color-danger-400); /* Better contrast in dark mode */
        color: var(--color-danger-200);
    }

    /* NEW: Loading Spinner - Better visibility */
    .fi-loading-spinner {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        border: 3px solid var(--color-gray-300);
        border-top-color: var(--color-primary-600);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .dark .fi-loading-spinner {
        border-color: var(--color-gray-600);
        border-top-color: var(--color-primary-400); /* Better visibility in dark mode */
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .fi-radio-option {
            min-width: 100%;
        }

        .fi-calendar-grid {
            overflow-x: auto;
        }
    }
</style>
<?php /**PATH /var/www/api-gateway/resources/views/livewire/appointment-booking-flow.blade.php ENDPATH**/ ?>