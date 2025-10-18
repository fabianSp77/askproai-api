<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Staff;
use App\Models\ServiceStaffAssignment;
use Illuminate\Support\Facades\Log;

/**
 * StaffSelector Component
 *
 * Reusable component for selecting staff/employee preference in booking flow
 *
 * Features:
 * - "Any available" option (default, best availability)
 * - Specific staff selection
 * - Service-aware filtering (only qualified staff)
 * - Cal.com mapping required
 * - Branch-aware filtering
 * - Dispatch events on selection
 *
 * Props:
 * - companyId: Company ID
 * - serviceId: Selected service ID (for filtering qualified staff)
 * - branchId: Selected branch ID (optional, for filtering)
 *
 * Emits:
 * - 'staff-selected': When user selects staff preference
 *
 * Usage:
 * <livewire:staff-selector :companyId="$companyId" :serviceId="$selectedServiceId" :branchId="$selectedBranchId" />
 */
class StaffSelector extends Component
{
    /**
     * Company ID
     */
    public int $companyId;

    /**
     * Selected service ID (for filtering qualified staff)
     */
    public ?string $serviceId = null;

    /**
     * Selected branch ID (optional)
     */
    public ?string $branchId = null;

    /**
     * Employee preference: 'any' or staff ID
     */
    public string $employeePreference = 'any';

    /**
     * Available employees
     */
    public array $availableEmployees = [];

    /**
     * Listeners
     */
    protected $listeners = [
        'service-selected' => 'onServiceSelected',
        'branch-selected' => 'onBranchSelected',
    ];

    /**
     * Component mount
     */
    public function mount(int $companyId, ?string $serviceId = null, ?string $branchId = null): void
    {
        $this->companyId = $companyId;
        $this->serviceId = $serviceId;
        $this->branchId = $branchId;

        if ($this->serviceId) {
            $this->loadEmployeesForService();
        }

        Log::debug('[StaffSelector] Component mounted', [
            'company_id' => $this->companyId,
            'service_id' => $this->serviceId,
            'branch_id' => $this->branchId,
            'employee_count' => count($this->availableEmployees),
        ]);
    }

    /**
     * Handle service selection event
     */
    public function onServiceSelected(string $serviceId): void
    {
        $this->serviceId = $serviceId;
        $this->employeePreference = 'any';
        $this->loadEmployeesForService();

        Log::debug('[StaffSelector] Service changed', [
            'new_service_id' => $serviceId,
        ]);
    }

    /**
     * Handle branch selection event
     */
    public function onBranchSelected(string $branchId): void
    {
        $this->branchId = $branchId;
        $this->employeePreference = 'any';

        if ($this->serviceId) {
            $this->loadEmployeesForService();
        }

        Log::debug('[StaffSelector] Branch changed', [
            'new_branch_id' => $branchId,
        ]);
    }

    /**
     * Load employees qualified for service
     */
    protected function loadEmployeesForService(): void
    {
        if (!$this->serviceId) {
            $this->availableEmployees = [];
            return;
        }

        try {
            // Get qualified staff for this service
            $qualifiedStaff = ServiceStaffAssignment::where('service_id', $this->serviceId)
                ->where('company_id', $this->companyId)
                ->where('is_active', true)
                ->temporallyValid()
                ->with('staff')
                ->orderBy('priority_order', 'asc')
                ->get();

            $staffIds = $qualifiedStaff->pluck('staff.id')->filter()->unique()->toArray();

            if (empty($staffIds)) {
                Log::warning('[StaffSelector] No staff qualified for service', [
                    'service_id' => $this->serviceId,
                ]);
                $this->availableEmployees = [];
                return;
            }

            // Query staff with Cal.com mapping
            $query = Staff::whereIn('id', $staffIds)
                ->where('company_id', $this->companyId)
                ->where('is_active', true)
                ->whereNotNull('calcom_user_id'); // Required: Must have Cal.com mapping

            // Filter by branch if selected
            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $this->availableEmployees = $query
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'email', 'calcom_user_id'])
                ->map(fn($s) => array_merge($s->toArray(), ['_source' => 'service_qualified']))
                ->toArray();

            Log::debug('[StaffSelector] Loaded qualified employees for service', [
                'service_id' => $this->serviceId,
                'qualified_count' => count($qualifiedStaff),
                'with_calcom_mapping' => count($this->availableEmployees),
                'branch_filtered' => $this->branchId ? true : false,
            ]);

        } catch (\Exception $e) {
            Log::error('[StaffSelector] Error loading service-qualified employees', [
                'service_id' => $this->serviceId,
                'error' => $e->getMessage(),
            ]);
            $this->availableEmployees = [];
        }
    }

    /**
     * Handle employee preference selection
     */
    public function selectEmployee(string $preference): void
    {
        $this->employeePreference = $preference;

        $label = $preference === 'any'
            ? 'Nächster verfügbarer'
            : collect($this->availableEmployees)->firstWhere('id', $preference)['name'] ?? 'Unbekannt';

        Log::info('[StaffSelector] Employee preference selected', [
            'preference' => $preference,
            'label' => $label,
        ]);

        $this->dispatch('staff-selected', employeeId: $preference);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.staff-selector');
    }
}
