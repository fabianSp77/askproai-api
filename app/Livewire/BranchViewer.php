<?php

namespace App\Livewire;

use App\Models\Branch;
use Livewire\Component;

class BranchViewer extends Component
{
    public $branchId;
    public Branch $branch;
    public $activeTab = 'overview';
    
    public function mount($branchId)
    {
        $this->branchId = $branchId;
        $this->branch = Branch::with(['company'])->findOrFail($branchId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.branch-viewer', [
            'branch' => $this->branch,
            'activeTab' => $this->activeTab,
        ]);
    }
}
