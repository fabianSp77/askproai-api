

<div x-data="{
    selectedSlot: <?php echo \Illuminate\Support\Js::from($preselectedSlot ?? null)->toHtml() ?>,
    selectedServiceId: <?php echo \Illuminate\Support\Js::from($preselectedServiceId ?? null)->toHtml() ?>,
    selectedBranchId: null,
    selectedCustomerId: null,
}"
x-on:branch-selected.window="
    // Capture branch selection
    selectedBranchId = $event.detail.branchId;
    console.log('[BookingFlowWrapper] Branch selected:', $event.detail.branchId);

    // Find parent form
    const form = $el.closest('form');
    if (!form) {
        console.error('[BookingFlowWrapper] Form not found');
        return;
    }

    // Update branch_id field (SELECT element in Filament)
    const branchSelect = form.querySelector('select[name=branch_id]');
    if (branchSelect) {
        branchSelect.value = $event.detail.branchId;
        branchSelect.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('[BookingFlowWrapper] branch_id updated:', $event.detail.branchId);
    } else {
        console.warn('[BookingFlowWrapper] branch_id select not found');
    }
"
x-on:customer-selected.window="
    // Capture customer selection
    selectedCustomerId = $event.detail.customerId;
    console.log('[BookingFlowWrapper] Customer selected:', $event.detail.customerId);

    // Find parent form
    const form = $el.closest('form');
    if (!form) {
        console.error('[BookingFlowWrapper] Form not found');
        return;
    }

    // Update customer_id field (SELECT element in Filament)
    const customerSelect = form.querySelector('select[name=customer_id]');
    if (customerSelect) {
        customerSelect.value = $event.detail.customerId;
        customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('[BookingFlowWrapper] customer_id updated:', $event.detail.customerId);
    } else {
        console.warn('[BookingFlowWrapper] customer_id select not found');
    }
"
x-on:service-selected.window="
    // Capture service selection
    selectedServiceId = $event.detail.serviceId;
    console.log('[BookingFlowWrapper] Service selected:', $event.detail.serviceId);

    // Find ALL Livewire components (we need both the form and the booking flow)
    const livewireComponents = document.querySelectorAll('[wire\\:id]');

    let formUpdated = false;

    for (const component of livewireComponents) {
        const livewireId = component.getAttribute('wire:id');
        const livewire = window.Livewire.find(livewireId);

        if (!livewire) continue;

        // Try to update Filament form data (if this is the form component)
        if (livewire.__instance && livewire.__instance.fingerprint &&
            livewire.__instance.fingerprint.name.includes('appointment-resource')) {
            try {
                livewire.set('data.service_id', $event.detail.serviceId);
                console.log('[BookingFlowWrapper] ✅ Filament form service_id updated');
                formUpdated = true;
            } catch (e) {
                // Not the form component, continue
            }
        }
    }

    // Note: Form component not found is OK - the booking flow handles service selection internally
    // The form fields will be populated when user selects a time slot
    console.log('[BookingFlowWrapper] Service selection handled by booking flow component');
"
x-on:employee-selected.window="
    // Capture employee/staff selection
    console.log('[BookingFlowWrapper] Employee selected:', $event.detail.employeeId);

    // Find parent form
    const form = $el.closest('form');
    if (!form) {
        console.error('[BookingFlowWrapper] Form not found');
        return;
    }

    // Update staff_id field (optional - user selected specific employee)
    const staffSelect = form.querySelector('select[name=staff_id]') || form.querySelector('input[name=staff_id]');
    if (staffSelect) {
        staffSelect.value = $event.detail.employeeId;
        staffSelect.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('[BookingFlowWrapper] staff_id updated:', $event.detail.employeeId);
    } else {
        console.warn('[BookingFlowWrapper] staff_id field not found');
    }
"
x-on:slot-selected.window="
    // Capture event data
    selectedSlot = $event.detail.datetime;
    selectedServiceId = $event.detail.serviceId || selectedServiceId;

    console.log('[BookingFlowWrapper] Slot selected:', $event.detail);

    // Find parent form
    const form = $el.closest('form');
    if (!form) {
        console.error('[BookingFlowWrapper] Form not found');
        return;
    }

    // Update starts_at hidden field
    const startsAtInput = form.querySelector('input[name=starts_at]');
    if (startsAtInput) {
        startsAtInput.value = $event.detail.datetime;
        startsAtInput.dispatchEvent(new Event('input', { bubbles: true }));
        startsAtInput.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('[BookingFlowWrapper] starts_at updated:', $event.detail.datetime);
    } else {
        console.warn('[BookingFlowWrapper] starts_at input not found');
    }

    // Update service_id if provided
    if ($event.detail.serviceId) {
        const serviceInput = form.querySelector('input[name=service_id]') || form.querySelector('select[name=service_id]');
        if (serviceInput) {
            serviceInput.value = $event.detail.serviceId;
            serviceInput.dispatchEvent(new Event('input', { bubbles: true }));
            serviceInput.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('[BookingFlowWrapper] service_id updated:', $event.detail.serviceId);
        }
    }

    // Update ends_at based on duration
    if ($event.detail.serviceDuration) {
        const endsAtInput = form.querySelector('input[name=ends_at]');
        if (endsAtInput) {
            const startsAt = new Date($event.detail.datetime);
            const endsAt = new Date(startsAt.getTime() + ($event.detail.serviceDuration * 60000));
            endsAtInput.value = endsAt.toISOString();
            endsAtInput.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('[BookingFlowWrapper] ends_at calculated:', endsAt.toISOString());
        }
    }
">
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('appointment-booking-flow', [
        'companyId' => $companyId,
        'preselectedServiceId' => $preselectedServiceId,
        'preselectedSlot' => $preselectedSlot,
    ]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3420277767-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
</div>
<?php /**PATH /var/www/api-gateway/resources/views/livewire/appointment-booking-flow-wrapper.blade.php ENDPATH**/ ?>