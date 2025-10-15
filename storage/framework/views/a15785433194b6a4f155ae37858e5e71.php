

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

    // Find parent Livewire component (Filament uses Livewire for forms)
    const livewireComponent = $el.closest('[wire\\\\:id]');
    if (!livewireComponent) {
        console.error('[BookingFlowWrapper] Livewire component not found');
        return;
    }

    // Get Livewire instance
    const livewireId = livewireComponent.getAttribute('wire:id');
    const livewire = window.Livewire.find(livewireId);

    if (livewire) {
        // Update Livewire data directly (Filament form data is in data.field_name)
        livewire.set('data.service_id', $event.detail.serviceId);
        console.log('[BookingFlowWrapper] Livewire service_id updated:', $event.detail.serviceId);
    } else {
        console.error('[BookingFlowWrapper] Livewire instance not found');
    }
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