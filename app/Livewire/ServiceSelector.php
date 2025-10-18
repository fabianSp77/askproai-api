<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

/**
 * ServiceSelector Component
 *
 * Reusable component for selecting a service in the booking flow
 *
 * Features:
 * - Display services for company with Cal.com event type
 * - Branch-aware filtering (respects branch overrides)
 * - Duration display for each service
 * - Default service selection
 * - Dispatch events on selection
 *
 * Props:
 * - companyId: Company ID
 * - branchId: Selected branch ID (optional, for filtering)
 *
 * Emits:
 * - 'service-selected': When user selects a service
 *
 * Usage:
 * <livewire:service-selector :companyId="$companyId" :branchId="$selectedBranchId" />
 */
class ServiceSelector extends Component
{
    /**
     * Company ID
     */
    public int $companyId;

    /**
     * Selected branch ID (optional)
     */
    public ?string $branchId = null;

    /**
     * Selected service ID
     */
    public ?string $selectedServiceId = null;

    /**
     * Selected service name
     */
    public ?string $serviceName = null;

    /**
     * Selected service duration
     */
    public int $serviceDuration = 45;

    /**
     * Available services
     */
    public array $availableServices = [];

    /**
     * Listeners
     */
    protected $listeners = [
        'branch-selected' => 'onBranchSelected',
    ];

    /**
     * Component mount
     */
    public function mount(int $companyId, ?string $branchId = null): void
    {
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        $this->loadAvailableServices();

        Log::debug('[ServiceSelector] Component mounted', [
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'service_count' => count($this->availableServices),
        ]);
    }

    /**
     * Handle branch selection event
     */
    public function onBranchSelected(string $branchId): void
    {
        $this->branchId = $branchId;
        $this->selectedServiceId = null;
        $this->serviceName = null;
        $this->loadAvailableServices();

        Log::debug('[ServiceSelector] Branch changed', [
            'new_branch_id' => $branchId,
        ]);
    }

    /**
     * Load available services for company and branch
     */
    protected function loadAvailableServices(): void
    {
        $query = Service::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id'); // Only Cal.com services

        // Filter by branch if selected
        if ($this->branchId) {
            $branch = Branch::find($this->branchId);

            if ($branch && $branch->services_override) {
                $serviceIds = collect($branch->services_override)->pluck('id')->toArray();
                $query->whereIn('id', $serviceIds);
            }
        }

        $this->availableServices = $query
            ->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'duration_minutes', 'calcom_event_type_id'])
            ->toArray();

        // Auto-select first service if available
        if (count($this->availableServices) > 0 && !$this->selectedServiceId) {
            $this->selectService($this->availableServices[0]['id']);
        }

        Log::debug('[ServiceSelector] Services loaded', [
            'count' => count($this->availableServices),
            'branch_id' => $this->branchId,
        ]);
    }

    /**
     * Handle service selection
     */
    public function selectService(string $serviceId): void
    {
        $service = collect($this->availableServices)
            ->firstWhere('id', $serviceId);

        if (!$service) {
            Log::warning('[ServiceSelector] Service not found', ['service_id' => $serviceId]);
            return;
        }

        $this->selectedServiceId = $serviceId;
        $this->serviceName = $service['name'];
        $this->serviceDuration = $service['duration_minutes'];

        Log::info('[ServiceSelector] Service selected', [
            'service_id' => $serviceId,
            'name' => $service['name'],
            'duration' => $service['duration_minutes'],
        ]);

        // Emit event for parent component
        $this->dispatch('service-selected', serviceId: $serviceId);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.service-selector');
    }
}
