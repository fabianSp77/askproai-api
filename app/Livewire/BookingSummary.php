<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * BookingSummary Component
 *
 * Reusable component for displaying and confirming booking summary
 *
 * Features:
 * - Display selected branch, service, staff, time
 * - Price/duration info
 * - Confirmation button
 * - Edit links for each section
 * - Validation of complete selection
 *
 * Props:
 * - branchName: Selected branch name
 * - serviceName: Selected service name
 * - serviceDuration: Service duration
 * - staffName: Selected staff name (or "any")
 * - selectedSlot: Selected time slot
 * - selectedSlotLabel: Human-readable slot label
 * - isComplete: Whether booking is ready to confirm
 *
 * Emits:
 * - 'confirm-booking': When user confirms booking
 * - 'edit-section': When user wants to edit a section
 *
 * Usage:
 * <livewire:booking-summary
 *     :branchName="$selectedBranch"
 *     :serviceName="$selectedService"
 *     :staffName="$selectedStaff"
 *     :selectedSlot="$selectedSlot"
 * />
 */
class BookingSummary extends Component
{
    /**
     * Branch info
     */
    public ?string $branchName = null;

    /**
     * Service info
     */
    public ?string $serviceName = null;
    public int $serviceDuration = 0;

    /**
     * Staff info
     */
    public ?string $staffName = null;
    public string $staffLabel = '';

    /**
     * Booking time
     */
    public ?string $selectedSlot = null;
    public ?string $selectedSlotLabel = null;

    /**
     * Booking completeness
     */
    public bool $isComplete = false;

    /**
     * Component mount
     */
    public function mount(): void
    {
        $this->updateCompleteness();

        Log::debug('[BookingSummary] Component mounted', [
            'is_complete' => $this->isComplete,
        ]);
    }

    /**
     * Update reactive property listener
     */
    public function updated(): void
    {
        $this->updateCompleteness();
    }

    /**
     * Check if booking is complete
     */
    protected function updateCompleteness(): void
    {
        $this->isComplete = !empty($this->branchName)
            && !empty($this->serviceName)
            && !empty($this->staffName)
            && !empty($this->selectedSlot);
    }

    /**
     * Confirm booking
     */
    public function confirmBooking(): void
    {
        if (!$this->isComplete) {
            Log::warning('[BookingSummary] Cannot confirm incomplete booking');
            return;
        }

        Log::info('[BookingSummary] Booking confirmed', [
            'branch' => $this->branchName,
            'service' => $this->serviceName,
            'staff' => $this->staffName,
            'slot' => $this->selectedSlot,
        ]);

        $this->dispatch('confirm-booking', [
            'branch_name' => $this->branchName,
            'service_name' => $this->serviceName,
            'staff_name' => $this->staffName,
            'selected_slot' => $this->selectedSlot,
        ]);
    }

    /**
     * Edit section
     */
    public function editSection(string $section): void
    {
        Log::info('[BookingSummary] User wants to edit section', [
            'section' => $section,
        ]);

        $this->dispatch('edit-section', section: $section);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.booking-summary');
    }
}
