{{-- Week Picker Wrapper for Filament Integration --}}

@if($serviceId)
    <div x-data="{
        selectedSlot: @js($preselectedSlot ?? null)
    }"
         x-on:slot-selected.window="
             // This handler is now deprecated - slot selection handled in component
             // Kept for backwards compatibility
             selectedSlot = $event.detail.datetime;
         "
         class="week-picker-wrapper">

        @livewire('appointment-week-picker', [
            'serviceId' => $serviceId,
            'preselectedSlot' => $preselectedSlot ?? null,
        ])
    </div>
@else
    <div class="p-4 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg text-center">
        <p class="text-sm text-warning-700 dark:text-warning-300">
            ⚠️ Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen.
        </p>
    </div>
@endif
