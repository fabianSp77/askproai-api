<x-filament-panels::page>
    <div class="fi-page-content">
        @livewire('calendar.appointment-calendar', [
            'branchId' => request()->get('branch_id'),
            'staffId' => request()->get('staff_id')
        ])
    </div>
</x-filament-panels::page>