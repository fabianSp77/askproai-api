<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Appointments\WeeklyAvailabilityService;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CalcomApiException;

/**
 * AppointmentWeekPicker
 *
 * Livewire component for week-at-a-glance appointment booking
 * Displays available slots for a service across Monday-Sunday
 *
 * Key Features:
 * - Service-specific availability
 * - Week navigation (previous/next/current)
 * - Real-time slot selection
 * - Cal.com API integration
 * - Smart caching (60s)
 *
 * Usage in Filament:
 * ```php
 * Forms\Components\ViewField::make('week_picker')
 *     ->view('filament.forms.components.week-picker', [
 *         'serviceId' => $record->service_id,
 *     ])
 * ```
 *
 * @property string $serviceId Service UUID
 * @property int $weekOffset Week navigation offset (0 = current, 1 = next, -1 = previous)
 * @property array $weekData Slots organized by day of week
 * @property array $weekMetadata Week display information
 * @property string|null $selectedSlot Selected slot datetime (ISO 8601)
 * @property string|null $error Error message for display
 * @property bool $loading Loading state
 */
class AppointmentWeekPicker extends Component
{
    // Required: Service ID from parent
    public string $serviceId;

    // Week navigation
    public int $weekOffset = 0; // 0 = current week, 1 = next week, -1 = last week

    // Week data
    public array $weekData = [];
    public array $weekMetadata = [];

    // Selected slot
    public ?string $selectedSlot = null;

    // Error handling
    public ?string $error = null;
    public bool $loading = false;

    // Service info for display
    public ?string $serviceName = null;
    public ?int $serviceDuration = null;

    /**
     * Component mount
     *
     * Called when component is initialized
     * Loads initial week data for the service
     *
     * @param string $serviceId Service UUID
     * @param int $initialWeekOffset Initial week offset (default: 0 = current week)
     * @param string|null $preselectedSlot Pre-selected slot datetime (for edit mode)
     * @return void
     */
    public function mount(
        string $serviceId,
        int $initialWeekOffset = 0,
        ?string $preselectedSlot = null
    ): void {
        $this->serviceId = $serviceId;
        $this->weekOffset = $initialWeekOffset;
        $this->selectedSlot = $preselectedSlot;

        // Load service info
        $this->loadServiceInfo();

        // Load week data
        $this->loadWeekData();

        Log::debug('[AppointmentWeekPicker] Component mounted', [
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'week_offset' => $this->weekOffset,
            'preselected_slot' => $preselectedSlot,
        ]);
    }

    /**
     * Load service information for display
     *
     * @return void
     */
    protected function loadServiceInfo(): void
    {
        try {
            $service = Service::findOrFail($this->serviceId);
            $this->serviceName = $service->name;
            $this->serviceDuration = $service->duration_minutes;

        } catch (\Exception $e) {
            $this->error = "Service nicht gefunden.";
            Log::error('[AppointmentWeekPicker] Service not found', [
                'service_id' => $this->serviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load week data from Cal.com API
     *
     * Fetches available slots for current week offset and updates UI
     *
     * @return void
     */
    public function loadWeekData(): void
    {
        $this->loading = true;
        $this->error = null;

        try {
            // Calculate week start (Monday) based on offset
            $weekStart = now()->addWeeks($this->weekOffset)->startOfWeek(Carbon::MONDAY);

            // Get availability service
            $availabilityService = app(WeeklyAvailabilityService::class);

            // Fetch week data
            $this->weekData = $availabilityService->getWeekAvailability($this->serviceId, $weekStart);
            $this->weekMetadata = $availabilityService->getWeekMetadata($weekStart);

            // Count total slots for logging
            $totalSlots = array_sum(array_map('count', $this->weekData));

            Log::info('[AppointmentWeekPicker] Week data loaded', [
                'service_id' => $this->serviceId,
                'week_offset' => $this->weekOffset,
                'week_start' => $weekStart->format('Y-m-d'),
                'total_slots' => $totalSlots,
            ]);

            // Prefetch next week in background (performance optimization)
            if ($this->weekOffset === 0) {
                dispatch(function() use ($availabilityService, $weekStart) {
                    $availabilityService->prefetchNextWeek($this->serviceId, $weekStart);
                })->afterResponse();
            }

        } catch (CalcomApiException $e) {
            $this->error = "Cal.com API-Fehler: " . $e->getMessage() . ". Bitte versuchen Sie es später erneut.";
            $this->weekData = $this->getEmptyWeekStructure();

            Log::error('[AppointmentWeekPicker] Cal.com API error', [
                'service_id' => $this->serviceId,
                'week_offset' => $this->weekOffset,
                'error' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

        } catch (\Exception $e) {
            $this->error = "Fehler beim Laden der Verfügbarkeiten: " . $e->getMessage();
            $this->weekData = $this->getEmptyWeekStructure();

            Log::error('[AppointmentWeekPicker] Unexpected error', [
                'service_id' => $this->serviceId,
                'week_offset' => $this->weekOffset,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

        } finally {
            $this->loading = false;
        }
    }

    /**
     * Navigate to previous week
     *
     * @return void
     */
    public function previousWeek(): void
    {
        $this->weekOffset--;
        $this->loadWeekData();

        Log::debug('[AppointmentWeekPicker] Previous week', [
            'new_offset' => $this->weekOffset,
        ]);
    }

    /**
     * Navigate to next week
     *
     * @return void
     */
    public function nextWeek(): void
    {
        $this->weekOffset++;
        $this->loadWeekData();

        Log::debug('[AppointmentWeekPicker] Next week', [
            'new_offset' => $this->weekOffset,
        ]);
    }

    /**
     * Jump to current week
     *
     * @return void
     */
    public function goToCurrentWeek(): void
    {
        $this->weekOffset = 0;
        $this->loadWeekData();

        Log::debug('[AppointmentWeekPicker] Jump to current week');
    }

    /**
     * Select a slot
     *
     * Updates selected slot and emits event to parent form
     *
     * @param string $datetime ISO 8601 datetime string
     * @return void
     */
    public function selectSlot(string $datetime): void
    {
        $this->selectedSlot = $datetime;

        // Parse datetime for display
        $carbon = Carbon::parse($datetime);
        $displayTime = $carbon->format('d.m.Y H:i') . ' Uhr';

        Log::info('[AppointmentWeekPicker] Slot selected', [
            'service_id' => $this->serviceId,
            'selected_datetime' => $datetime,
            'display_time' => $displayTime,
        ]);

        // Dispatch BROWSER event (not Livewire event) for Alpine.js wrapper
        $this->js("
            window.dispatchEvent(new CustomEvent('slot-selected', {
                detail: {
                    datetime: '{$datetime}',
                    displayTime: '{$displayTime}'
                }
            }));
        ");

        // Success notification
        $this->dispatch('notify', [
            'message' => "Slot ausgewählt: {$displayTime}",
            'type' => 'success',
        ]);
    }

    /**
     * Refresh week data (manual reload)
     *
     * @return void
     */
    public function refreshWeek(): void
    {
        $this->loadWeekData();

        $this->dispatch('notify', [
            'message' => 'Verfügbarkeiten aktualisiert',
            'type' => 'success',
        ]);
    }

    /**
     * Get empty week structure
     *
     * @return array
     */
    protected function getEmptyWeekStructure(): array
    {
        return [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];
    }

    /**
     * Get total slots count for current week
     *
     * @return int
     */
    public function getTotalSlotsProperty(): int
    {
        return array_sum(array_map('count', $this->weekData));
    }

    /**
     * Check if week is empty (no slots)
     *
     * @return bool
     */
    public function getIsEmptyWeekProperty(): bool
    {
        return $this->totalSlots === 0;
    }

    /**
     * Check if a slot is selected
     *
     * @param string $datetime Slot datetime
     * @return bool
     */
    public function isSlotSelected(string $datetime): bool
    {
        return $this->selectedSlot === $datetime;
    }

    /**
     * Get day label for display
     *
     * @param string $dayKey Day key (monday, tuesday, etc.)
     * @return string Short day label (Mo, Di, Mi, etc.)
     */
    public function getDayLabel(string $dayKey): string
    {
        return match($dayKey) {
            'monday' => 'Mo',
            'tuesday' => 'Di',
            'wednesday' => 'Mi',
            'thursday' => 'Do',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'So',
            default => '?',
        };
    }

    /**
     * Get full day name for display
     *
     * @param string $dayKey Day key (monday, tuesday, etc.)
     * @return string Full day name (Montag, Dienstag, etc.)
     */
    public function getFullDayName(string $dayKey): string
    {
        return match($dayKey) {
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
            default => '?',
        };
    }

    /**
     * Render component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.appointment-week-picker');
    }
}
