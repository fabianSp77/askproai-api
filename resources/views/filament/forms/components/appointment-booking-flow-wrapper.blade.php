{{--
    Appointment Booking Flow Wrapper
    Integrates AppointmentBookingFlow Livewire component into Filament form
    Handles browser events and updates hidden form fields
--}}

<div x-data="{
    selectedSlot: @js($preselectedSlot ?? null),
    selectedServiceId: @js($preselectedServiceId ?? null),
}"
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
    @livewire('appointment-booking-flow', [
        'companyId' => $companyId,
        'preselectedServiceId' => $preselectedServiceId,
        'preselectedSlot' => $preselectedSlot,
    ])
</div>
