<?php

namespace App\Livewire;

use App\Models\Staff;
use Livewire\Component;

class StaffViewer extends Component
{
    public $staffId;
    public Staff $staff;
    public $activeTab = 'overview';
    
    public function mount($staffId)
    {
        $this->staffId = $staffId;
        $this->staff = Staff::with(['company', 'branch', 'services'])->findOrFail($staffId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.staff-viewer', [
            'staff' => $this->staff,
            'activeTab' => $this->activeTab,
        ]);
    }
}
