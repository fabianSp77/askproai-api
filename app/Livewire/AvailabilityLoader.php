<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Appointments\CalcomAvailabilityService;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AvailabilityLoader Component
 *
 * Loads real-time availability from Cal.com API
 * Acts as a bridge between HourlyCalendar and CalcomAvailabilityService
 *
 * Listens to:
 * - 'service-selected': When user selects a service
 * - 'staff-selected': When user selects a staff member
 *
 * Props:
 * - $companyId: Company ID
 * - $serviceId: Selected service ID (required)
 * - $staffId: Selected staff ID (optional, for "any" = null)
 *
 * Emits:
 * - 'availability-loaded': When availability is loaded
 *
 * Usage:
 * <livewire:availability-loader
 *     :companyId="$companyId"
 *     :serviceId="$selectedServiceId"
 *     :staffId="$employeePreference"
 * />
 */
class AvailabilityLoader extends Component
{
    /**
     * Company ID
     */
    public int $companyId;

    /**
     * Selected service ID
     */
    public ?string $serviceId = null;

    /**
     * Selected staff ID (null = "any")
     */
    public ?string $staffId = null;

    /**
     * Current week offset
     */
    public int $weekOffset = 0;

    /**
     * Loaded availability data
     */
    public array $weekData = [];

    /**
     * Week metadata (dates, etc)
     */
    public array $weekMetadata = [];

    /**
     * Loading state
     */
    public bool $loading = false;

    /**
     * Error message (if any)
     */
    public ?string $error = null;

    /**
     * Service duration (for slot blocking)
     */
    public int $serviceDuration = 45;

    /**
     * Listeners for events
     */
    protected $listeners = [
        'service-selected' => 'onServiceSelected',
        'staff-selected' => 'onStaffSelected',
    ];

    /**
     * Component mount
     */
    public function mount(int $companyId, ?string $serviceId = null, ?string $staffId = null): void
    {
        $this->companyId = $companyId;
        $this->serviceId = $serviceId;
        $this->staffId = $staffId;

        if ($this->serviceId) {
            $this->loadAvailability();
        }

        Log::debug('[AvailabilityLoader] Component mounted', [
            'company_id' => $this->companyId,
            'service_id' => $this->serviceId,
            'staff_id' => $this->staffId,
        ]);
    }

    /**
     * Handle service selection event
     */
    public function onServiceSelected(string $serviceId): void
    {
        $this->serviceId = $serviceId;
        $this->weekOffset = 0;
        $this->loadAvailability();

        Log::debug('[AvailabilityLoader] Service selected', [
            'service_id' => $serviceId,
        ]);
    }

    /**
     * Handle staff selection event
     */
    public function onStaffSelected(string $staffId): void
    {
        $this->staffId = $staffId === 'any' ? null : $staffId;
        $this->weekOffset = 0;
        $this->loadAvailability();

        Log::debug('[AvailabilityLoader] Staff selected', [
            'staff_id' => $this->staffId,
        ]);
    }

    /**
     * Load availability from Cal.com API
     */
    public function loadAvailability(): void
    {
        if (!$this->serviceId) {
            Log::warning('[AvailabilityLoader] Cannot load without serviceId');
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            // Get service to determine duration
            $service = Service::findOrFail($this->serviceId);
            $this->serviceDuration = $service->duration_minutes;

            // Calculate week start
            $weekStart = now()->addWeeks($this->weekOffset)->startOfWeek(Carbon::MONDAY);

            // Fetch availability from Cal.com
            $availabilityService = app(CalcomAvailabilityService::class);
            $this->weekData = $availabilityService->getAvailabilityForWeek(
                $this->serviceId,
                $weekStart,
                $this->serviceDuration,
                $this->staffId  // null = "any" staff
            );

            // Build week metadata
            $this->weekMetadata = $this->buildWeekMetadata($weekStart);

            Log::info('[AvailabilityLoader] Availability loaded', [
                'service_id' => $this->serviceId,
                'staff_id' => $this->staffId,
                'week_start' => $weekStart->format('Y-m-d'),
                'total_slots' => collect($this->weekData)->flatten(1)->count(),
            ]);

            // Dispatch event for other components
            $this->dispatch('availability-loaded', [
                'weekData' => $this->weekData,
                'weekMetadata' => $this->weekMetadata,
            ]);

        } catch (\Exception $e) {
            $this->error = "Fehler beim Laden der Verfügbarkeiten: " . $e->getMessage();

            Log::error('[AvailabilityLoader] Error loading availability', [
                'service_id' => $this->serviceId,
                'error' => $e->getMessage(),
            ]);

            $this->weekData = $this->getEmptyWeekStructure();
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Navigate to previous week
     */
    public function previousWeek(): void
    {
        $this->weekOffset--;
        $this->loadAvailability();
    }

    /**
     * Navigate to next week
     */
    public function nextWeek(): void
    {
        $this->weekOffset++;
        $this->loadAvailability();
    }

    /**
     * Go to current week
     */
    public function goToCurrentWeek(): void
    {
        $this->weekOffset = 0;
        $this->loadAvailability();
    }

    /**
     * Build week metadata
     */
    protected function buildWeekMetadata(Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dayName = strtolower($date->format('l'));
            $days[$dayName] = $date->format('d.m');
        }

        return [
            'start_date' => $weekStart->format('d.m.Y'),
            'end_date' => $weekEnd->format('d.m.Y'),
            'days' => $days,
            'week_offset' => $this->weekOffset,
        ];
    }

    /**
     * Get empty week structure
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
     * Render the component
     */
    public function render()
    {
        return view('livewire.availability-loader');
    }
}
