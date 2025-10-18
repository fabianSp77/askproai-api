<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Appointments\WeeklyAvailabilityService;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ServiceStaffAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\CalcomApiException;

/**
 * AppointmentBookingFlow
 *
 * Modern appointment booking component with service-first approach
 *
 * Key Features:
 * - Service selection FIRST (with duration awareness)
 * - Employee preference (optional, defaults to "any")
 * - Duration-aware slot calculation
 * - Single-page vertical layout
 * - Professional Filament-consistent UI
 *
 * Flow:
 * 1. User selects service (default: Damenhaarschnitt 45min)
 * 2. User optionally selects employee (default: "any available")
 * 3. Calendar shows slots where service duration fits
 * 4. User selects slot and confirms
 *
 * @property int $companyId Company context
 * @property string $selectedServiceId Currently selected service UUID
 * @property int $serviceDuration Duration of selected service in minutes
 * @property string $employeePreference "any" or employee UUID
 * @property int $weekOffset Week navigation offset
 * @property array $weekData Available slots for current week
 * @property array $weekMetadata Week display information
 * @property string|null $selectedSlot Selected slot datetime
 */
class AppointmentBookingFlow extends Component
{
    // Required: Company context
    public int $companyId;

    // NEW: Branch selection
    public ?string $selectedBranchId = null;
    public array $availableBranches = [];

    // NEW: Customer selection
    public ?int $selectedCustomerId = null;
    public string $customerSearchQuery = '';
    public array $searchResults = [];
    public ?string $selectedCustomerName = null;

    // NEW: Create customer inline
    public bool $showNewCustomerForm = false;
    public string $newCustomerName = '';
    public string $newCustomerPhone = '';
    public string $newCustomerEmail = '';

    // Service selection
    public ?string $selectedServiceId = null;
    public int $serviceDuration = 45; // Default: Damenhaarschnitt
    public ?string $serviceName = null;

    // Employee preference
    public string $employeePreference = 'any'; // 'any' or employee UUID

    // Week navigation
    public int $weekOffset = 0;

    // Week data
    public array $weekData = [];
    public array $weekMetadata = [];

    // Selected slot
    public ?string $selectedSlot = null;
    public ?string $selectedSlotLabel = null;

    // UI state
    public bool $loading = false;
    public ?string $error = null;

    // Available services and employees
    public array $availableServices = [];
    public array $availableEmployees = [];

    /**
     * Component initialization
     *
     * @param int $companyId Company context
     * @param string|null $preselectedServiceId Pre-selected service (optional)
     * @param string|null $preselectedSlot Pre-selected slot (optional, for edit mode)
     */
    public function mount(
        int $companyId,
        ?string $preselectedServiceId = null,
        ?string $preselectedSlot = null
    ): void {
        $this->companyId = $companyId;
        $this->selectedSlot = $preselectedSlot;

        // Load branches, services, and employees
        $this->loadAvailableBranches();
        $this->loadAvailableServices();
        $this->loadAvailableEmployees();

        // Set default service (Damenhaarschnitt) or preselected
        if ($preselectedServiceId) {
            $this->selectedServiceId = $preselectedServiceId;
            $this->loadServiceInfo();
        } else {
            $this->setDefaultService();
        }

        // Load initial week data
        $this->loadWeekData();

        Log::debug('[AppointmentBookingFlow] Component mounted', [
            'company_id' => $this->companyId,
            'selected_service' => $this->selectedServiceId,
            'service_duration' => $this->serviceDuration,
            'employee_preference' => $this->employeePreference,
        ]);
    }

    /**
     * Load available services for company and selected branch
     *
     * NEW (2025-10-17): Cal.com-aware loading
     * - Only shows services with Cal.com Event Type IDs configured
     * - If branch is selected: filters to services available at that branch
     * - Services must be active and have Cal.com integration
     */
    protected function loadAvailableServices(): void
    {
        $query = Service::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id'); // REQUIRED: Must have Cal.com event type

        // If branch is selected, filter services available at that branch
        if ($this->selectedBranchId) {
            // Check if this branch has service overrides in settings
            $branch = Branch::find($this->selectedBranchId);

            if ($branch && $branch->services_override) {
                // Use branch-specific service list
                $serviceIds = collect($branch->services_override)->pluck('id')->toArray();
                $query->whereIn('id', $serviceIds);
            }
            // Otherwise: use all company services (branch has access to all services)
        }

        $this->availableServices = $query
            ->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'duration_minutes', 'calcom_event_type_id'])
            ->toArray();

        Log::debug('[AppointmentBookingFlow] Loaded services', [
            'count' => count($this->availableServices),
            'branch_id' => $this->selectedBranchId,
            'has_override' => isset($branch) && $branch->services_override ? true : false,
        ]);
    }

    /**
     * Load available employees for company
     *
     * NEW (2025-10-17): Cal.com-aware loading
     * - Primary: Fetch from Cal.com team members API
     * - Fallback: Load staff with calcom_user_id from local DB
     * - Last Resort: Load all active staff (backward compatibility)
     */
    protected function loadAvailableEmployees(): void
    {
        try {
            // Try to load from Cal.com first
            $company = \App\Models\Company::find($this->companyId);

            if ($company?->calcom_team_id) {
                $this->loadFromCalcomTeam($company);
            } else {
                $this->loadFromLocalDatabase();
            }
        } catch (\Exception $e) {
            Log::warning('[AppointmentBookingFlow] Cal.com unavailable, using local staff', [
                'error' => $e->getMessage(),
            ]);
            $this->loadFromLocalDatabase();
        }

        Log::debug('[AppointmentBookingFlow] Loaded employees', [
            'count' => count($this->availableEmployees),
            'source' => $this->availableEmployees[0]['_source'] ?? 'unknown',
        ]);
    }

    /**
     * Load employees from Cal.com team members
     * Only shows staff that are linked to Cal.com users
     */
    protected function loadFromCalcomTeam(\App\Models\Company $company): void
    {
        try {
            $calcomService = app(\App\Services\CalcomV2Service::class);
            $response = $calcomService->fetchTeamMembers($company->calcom_team_id);

            if (!$response->successful()) {
                throw new \Exception("Cal.com API error: {$response->status()}");
            }

            $teamMembers = $response->json()['members'] ?? [];
            $calcomUserIds = collect($teamMembers)->pluck('userId')->toArray();

            if (empty($calcomUserIds)) {
                Log::warning('[AppointmentBookingFlow] No team members found in Cal.com', [
                    'company_id' => $this->companyId,
                    'team_id' => $company->calcom_team_id,
                ]);
                $this->loadFromLocalDatabase();
                return;
            }

            // Load only staff that are linked to Cal.com
            $this->availableEmployees = \App\Models\Staff::where('company_id', $this->companyId)
                ->whereIn('calcom_user_id', $calcomUserIds)
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'email', 'calcom_user_id'])
                ->map(fn($s) => array_merge($s->toArray(), ['_source' => 'calcom']))
                ->toArray();

            Log::debug('[AppointmentBookingFlow] Loaded employees from Cal.com', [
                'count' => count($this->availableEmployees),
                'team_id' => $company->calcom_team_id,
            ]);

        } catch (\Exception $e) {
            throw $e; // Re-throw to be caught by parent method
        }
    }

    /**
     * Load employees from local database
     * Priority: Staff with calcom_user_id > All active staff
     */
    protected function loadFromLocalDatabase(): void
    {
        // First, try to load staff with calcom_user_id
        $staffWithCalcom = \App\Models\Staff::where('company_id', $this->companyId)
            ->whereNotNull('calcom_user_id')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'email', 'calcom_user_id'])
            ->map(fn($s) => array_merge($s->toArray(), ['_source' => 'local_calcom']))
            ->toArray();

        if (!empty($staffWithCalcom)) {
            $this->availableEmployees = $staffWithCalcom;
            return;
        }

        // Fallback: All active staff (for backward compatibility)
        Log::warning('[AppointmentBookingFlow] No staff with calcom_user_id found, showing all staff', [
            'company_id' => $this->companyId,
        ]);

        $this->availableEmployees = \App\Models\Staff::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'email'])
            ->map(fn($s) => array_merge($s->toArray(), ['_source' => 'local_all']))
            ->toArray();
    }

    /**
     * NEW: Load available branches for company
     */
    protected function loadAvailableBranches(): void
    {
        $this->availableBranches = Branch::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'address'])
            ->toArray();

        // Auto-select if only one branch
        if (count($this->availableBranches) === 1) {
            $this->selectedBranchId = $this->availableBranches[0]['id'];
        }

        Log::debug('[AppointmentBookingFlow] Loaded branches', [
            'count' => count($this->availableBranches),
            'auto_selected' => $this->selectedBranchId,
        ]);
    }

    /**
     * NEW: Select branch
     * Triggers: Reload services for this branch and reset selections
     */
    public function selectBranch(string $branchId): void
    {
        $this->selectedBranchId = $branchId;
        $this->selectedServiceId = null;
        $this->selectedSlot = null;
        $this->selectedSlotLabel = null;
        $this->employeePreference = 'any';

        // Reload services for this branch (may have overrides)
        $this->loadAvailableServices();

        // Dispatch event for form integration
        $this->dispatch('branch-selected', branchId: $branchId);

        Log::info('[AppointmentBookingFlow] Branch selected', [
            'branch_id' => $branchId,
            'available_services' => count($this->availableServices),
        ]);

        $this->dispatch('notify', [
            'message' => "Filiale gewählt",
            'type' => 'info',
        ]);
    }

    /**
     * NEW: Search customers (live search with debounce)
     */
    public function updatedCustomerSearchQuery(): void
    {
        if (strlen($this->customerSearchQuery) < 3) {
            $this->searchResults = [];
            return;
        }

        $query = $this->customerSearchQuery;

        $this->searchResults = Customer::where('company_id', $this->companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone'])
            ->toArray();

        Log::debug('[AppointmentBookingFlow] Customer search', [
            'query' => $query,
            'results' => count($this->searchResults),
        ]);
    }

    /**
     * NEW: Select customer
     */
    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $this->selectedCustomerId = $customerId;
            $this->selectedCustomerName = $customer->name;
            $this->customerSearchQuery = $customer->name;
            $this->searchResults = [];

            // Dispatch event for form integration
            $this->dispatch('customer-selected', customerId: $customerId);

            Log::info('[AppointmentBookingFlow] Customer selected', [
                'customer_id' => $customerId,
                'name' => $customer->name,
            ]);
        }
    }

    /**
     * NEW: Show create customer form
     */
    public function showCreateCustomerForm(): void
    {
        $this->showNewCustomerForm = true;
        $this->newCustomerName = $this->customerSearchQuery; // Pre-fill with search query
        $this->newCustomerPhone = '';
        $this->newCustomerEmail = '';
    }

    /**
     * NEW: Create new customer
     */
    public function createNewCustomer(): void
    {
        // Validation
        $this->validate([
            'newCustomerName' => 'required|min:2',
            'newCustomerPhone' => 'nullable|string',
            'newCustomerEmail' => 'nullable|email',
        ]);

        // Create customer
        $customer = Customer::create([
            'company_id' => $this->companyId,
            'name' => $this->newCustomerName,
            'phone' => $this->newCustomerPhone ?: null,
            'email' => $this->newCustomerEmail ?: null,
        ]);

        // Auto-select the new customer
        $this->selectedCustomerId = $customer->id;
        $this->selectedCustomerName = $customer->name;
        $this->customerSearchQuery = $customer->name;
        $this->showNewCustomerForm = false;
        $this->searchResults = [];

        // Dispatch event for form integration
        $this->dispatch('customer-selected', customerId: $customer->id);

        Log::info('[AppointmentBookingFlow] New customer created', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
        ]);
    }

    /**
     * NEW: Cancel create customer
     */
    public function cancelCreateCustomer(): void
    {
        $this->showNewCustomerForm = false;
        $this->newCustomerName = '';
        $this->newCustomerPhone = '';
        $this->newCustomerEmail = '';
    }

    /**
     * Set default service (prioritize service with most availability)
     */
    protected function setDefaultService(): void
    {
        // Priority 1: Service with name containing "30 Minuten" (typically has availability)
        $default = collect($this->availableServices)->first(function ($service) {
            return str_contains(strtolower($service['name']), '30 minuten');
        });

        // Priority 2: Try to find "Damenhaarschnitt"
        if (!$default) {
            $default = collect($this->availableServices)->first(function ($service) {
                return str_contains(strtolower($service['name']), 'damenhaarschnitt');
            });
        }

        // Fallback: First service
        if (!$default && count($this->availableServices) > 0) {
            $default = $this->availableServices[0];
        }

        if ($default) {
            $this->selectedServiceId = $default['id'];
            $this->serviceName = $default['name'];
            $this->serviceDuration = $default['duration_minutes'];

            Log::info('[AppointmentBookingFlow] Default service set', [
                'service_id' => $this->selectedServiceId,
                'name' => $this->serviceName,
                'duration' => $this->serviceDuration,
            ]);
        }
    }

    /**
     * Load service information
     */
    protected function loadServiceInfo(): void
    {
        try {
            $service = Service::findOrFail($this->selectedServiceId);
            $this->serviceName = $service->name;
            $this->serviceDuration = $service->duration_minutes;

        } catch (\Exception $e) {
            $this->error = "Service nicht gefunden.";
            Log::error('[AppointmentBookingFlow] Service not found', [
                'service_id' => $this->selectedServiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * User selects a service
     * Triggers: Reload employees for this service, reload calendar with new duration
     *
     * NEW (2025-10-17): Filters employees to those qualified for this service
     * - Only shows staff with Cal.com mapping
     * - Only shows staff qualified for this service (ServiceStaffAssignment)
     * - Only shows staff at selected branch (if branch is selected)
     *
     * @param string $serviceId Service UUID
     */
    public function selectService(string $serviceId): void
    {
        $this->selectedServiceId = $serviceId;
        $this->selectedSlot = null; // Reset slot selection
        $this->selectedSlotLabel = null;
        $this->employeePreference = 'any'; // Reset to "any" to show available options

        // Load service info
        $this->loadServiceInfo();

        // Load employees qualified for this service
        $this->loadEmployeesForService($serviceId);

        // Reload week data with new duration
        $this->loadWeekData();

        Log::info('[AppointmentBookingFlow] Service selected', [
            'service_id' => $serviceId,
            'name' => $this->serviceName,
            'duration' => $this->serviceDuration,
            'qualified_employees' => count($this->availableEmployees),
        ]);

        // Dispatch event for form integration
        $this->dispatch('service-selected', serviceId: $serviceId);

        $this->dispatch('notify', [
            'message' => "Service gewählt: {$this->serviceName} ({$this->serviceDuration} Min)",
            'type' => 'info',
        ]);
    }

    /**
     * Load employees qualified for a specific service
     *
     * NEW (2025-10-17): Service-aware employee loading
     * - Gets all staff qualified for this service via ServiceStaffAssignment
     * - Filters to only those with Cal.com mapping
     * - If branch is selected, only shows staff at that branch
     * - Orders by priority (from assignment) then by name
     *
     * @param string $serviceId Service UUID
     */
    protected function loadEmployeesForService(string $serviceId): void
    {
        try {
            // Get all qualified staff for this service
            $qualifiedStaff = ServiceStaffAssignment::where('service_id', $serviceId)
                ->where('company_id', $this->companyId)
                ->where('is_active', true)
                ->temporallyValid() // Check effective_from/until dates
                ->with('staff')
                ->orderBy('priority_order', 'asc')
                ->get();

            // Extract staff IDs
            $staffIds = $qualifiedStaff->pluck('staff.id')->filter()->unique()->toArray();

            if (empty($staffIds)) {
                Log::warning('[AppointmentBookingFlow] No staff qualified for service', [
                    'service_id' => $serviceId,
                    'company_id' => $this->companyId,
                ]);
                $this->availableEmployees = [];
                return;
            }

            // Query staff with Cal.com mapping
            $query = Staff::whereIn('id', $staffIds)
                ->where('company_id', $this->companyId)
                ->where('is_active', true)
                ->whereNotNull('calcom_user_id'); // REQUIRED: Must have Cal.com mapping

            // Filter by branch if selected
            if ($this->selectedBranchId) {
                $query->where('branch_id', $this->selectedBranchId);
            }

            $this->availableEmployees = $query
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'email', 'calcom_user_id'])
                ->map(fn($s) => array_merge($s->toArray(), ['_source' => 'service_qualified']))
                ->toArray();

            Log::debug('[AppointmentBookingFlow] Loaded qualified employees for service', [
                'service_id' => $serviceId,
                'qualified_count' => count($qualifiedStaff),
                'with_calcom_mapping' => count($this->availableEmployees),
                'branch_filtered' => $this->selectedBranchId ? true : false,
            ]);

        } catch (\Exception $e) {
            Log::error('[AppointmentBookingFlow] Error loading service-qualified employees', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);
            $this->availableEmployees = [];
        }
    }

    /**
     * User selects employee preference
     * Triggers: Reload calendar for specific employee
     *
     * @param string $preference "any" or employee UUID
     */
    public function selectEmployee(string $preference): void
    {
        $this->employeePreference = $preference;
        $this->selectedSlot = null; // Reset slot selection
        $this->selectedSlotLabel = null;

        // Reload week data for employee
        $this->loadWeekData();

        $employeeName = $preference === 'any'
            ? 'Nächster verfügbarer'
            : collect($this->availableEmployees)->firstWhere('id', $preference)['name'] ?? 'Unbekannt';

        Log::info('[AppointmentBookingFlow] Employee preference selected', [
            'preference' => $preference,
            'name' => $employeeName,
        ]);

        // Dispatch event for form integration (only if specific employee, not 'any')
        if ($preference !== 'any') {
            $this->dispatch('employee-selected', employeeId: $preference);
        }

        $this->dispatch('notify', [
            'message' => "Mitarbeiter: {$employeeName}",
            'type' => 'info',
        ]);
    }

    /**
     * Load week data from Cal.com API
     * Duration-aware: Only shows slots where service duration fits
     */
    public function loadWeekData(): void
    {
        if (!$this->selectedServiceId) {
            Log::warning('[AppointmentBookingFlow] Cannot load week data without service');
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            // Calculate week start
            $weekStart = now()->addWeeks($this->weekOffset)->startOfWeek(Carbon::MONDAY);

            // Cache key includes service duration and employee
            $cacheKey = sprintf(
                'appointment_flow:%s:%s:%d:%s',
                $this->companyId,
                $this->selectedServiceId,
                $this->weekOffset,
                $this->employeePreference
            );

            // Try cache first (60 seconds)
            $this->weekData = Cache::remember($cacheKey, 60, function () use ($weekStart) {
                $availabilityService = app(WeeklyAvailabilityService::class);

                // Get availability with duration awareness
                // TODO: WeeklyAvailabilityService needs to accept duration and employee params
                // For now, we'll use the existing method and filter client-side
                return $availabilityService->getWeekAvailability(
                    $this->selectedServiceId,
                    $weekStart
                );
            });

            $this->weekMetadata = app(WeeklyAvailabilityService::class)->getWeekMetadata($weekStart);

            $totalSlots = array_sum(array_map('count', $this->weekData));

            Log::info('[AppointmentBookingFlow] Week data loaded', [
                'service_id' => $this->selectedServiceId,
                'duration' => $this->serviceDuration,
                'employee' => $this->employeePreference,
                'week_offset' => $this->weekOffset,
                'total_slots' => $totalSlots,
            ]);

        } catch (CalcomApiException $e) {
            $this->error = "Cal.com API-Fehler: {$e->getMessage()}";
            $this->weekData = $this->getEmptyWeekStructure();

            Log::error('[AppointmentBookingFlow] Cal.com error', [
                'error' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

        } catch (\Exception $e) {
            $this->error = "Fehler beim Laden: {$e->getMessage()}";
            $this->weekData = $this->getEmptyWeekStructure();

            Log::error('[AppointmentBookingFlow] Load error', [
                'error' => $e->getMessage(),
            ]);

        } finally {
            $this->loading = false;
        }
    }

    /**
     * User selects a time slot
     *
     * @param string $datetime ISO 8601 datetime
     * @param string $label Human-readable label
     */
    public function selectSlot(string $datetime, string $label): void
    {
        $this->selectedSlot = $datetime;
        $this->selectedSlotLabel = $label;

        Log::info('[AppointmentBookingFlow] Slot selected', [
            'datetime' => $datetime,
            'label' => $label,
            'service' => $this->serviceName,
            'duration' => $this->serviceDuration,
        ]);

        // Dispatch browser event for parent form
        $this->js("
            window.dispatchEvent(new CustomEvent('slot-selected', {
                detail: {
                    datetime: '{$datetime}',
                    serviceId: '{$this->selectedServiceId}',
                    serviceDuration: {$this->serviceDuration},
                    employee: '{$this->employeePreference}'
                }
            }));
        ");

        $this->dispatch('notify', [
            'message' => "Termin gewählt: {$label}",
            'type' => 'success',
        ]);
    }

    /**
     * Navigate to previous week
     */
    public function previousWeek(): void
    {
        $this->weekOffset--;
        $this->selectedSlot = null;
        $this->loadWeekData();
    }

    /**
     * Navigate to next week
     */
    public function nextWeek(): void
    {
        $this->weekOffset++;
        $this->selectedSlot = null;
        $this->loadWeekData();
    }

    /**
     * Jump to current week
     */
    public function goToCurrentWeek(): void
    {
        $this->weekOffset = 0;
        $this->selectedSlot = null;
        $this->loadWeekData();
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
     * Get day label for display
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
     * Check if slot is selected
     */
    public function isSlotSelected(string $datetime): bool
    {
        return $this->selectedSlot === $datetime;
    }

    /**
     * Get total slots count
     */
    public function getTotalSlotsProperty(): int
    {
        return array_sum(array_map('count', $this->weekData));
    }

    /**
     * Render component
     */
    public function render()
    {
        // DEBUG: Log what data is being rendered
        $totalSlots = collect($this->weekData)->flatten(1)->count();

        Log::info('[AppointmentBookingFlow] Rendering', [
            'service_id' => $this->selectedServiceId,
            'service_name' => $this->serviceName,
            'week_offset' => $this->weekOffset,
            'total_slots' => $totalSlots,
            'loading' => $this->loading,
            'error' => $this->error,
            'weekData_keys' => array_keys($this->weekData),
            'slots_per_day' => array_map('count', $this->weekData),
        ]);

        return view('livewire.appointment-booking-flow');
    }
}
