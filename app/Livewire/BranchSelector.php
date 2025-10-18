<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

/**
 * BranchSelector Component
 *
 * Reusable component for selecting a branch in the booking flow
 *
 * Features:
 * - Display all available branches
 * - Single or multi-branch support
 * - Auto-select if only one branch
 * - Dispatch events on selection
 * - Accessible radio group
 *
 * Emits:
 * - 'branch-selected': When user selects a branch
 *
 * Usage:
 * <livewire:branch-selector :companyId="$companyId" />
 */
class BranchSelector extends Component
{
    /**
     * Company ID for filtering branches
     */
    public int $companyId;

    /**
     * Selected branch ID
     */
    public ?string $selectedBranchId = null;

    /**
     * Available branches
     */
    public array $availableBranches = [];

    /**
     * Component mount
     */
    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;
        $this->loadAvailableBranches();

        Log::debug('[BranchSelector] Component mounted', [
            'company_id' => $this->companyId,
            'branch_count' => count($this->availableBranches),
        ]);
    }

    /**
     * Load available branches for company
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

        Log::debug('[BranchSelector] Branches loaded', [
            'count' => count($this->availableBranches),
            'auto_selected' => $this->selectedBranchId ? true : false,
        ]);
    }

    /**
     * Handle branch selection
     *
     * Called when user selects a branch
     */
    public function selectBranch(string $branchId): void
    {
        $this->selectedBranchId = $branchId;

        $branch = collect($this->availableBranches)
            ->firstWhere('id', $branchId);

        Log::info('[BranchSelector] Branch selected', [
            'branch_id' => $branchId,
            'branch_name' => $branch['name'] ?? 'Unknown',
        ]);

        // Emit event for parent component
        $this->dispatch('branch-selected', branchId: $branchId);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.branch-selector');
    }
}
