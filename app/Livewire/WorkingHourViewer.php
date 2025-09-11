<?php

namespace App\Livewire;

use App\Models\WorkingHour;
use Livewire\Component;

class WorkingHourViewer extends Component
{
    public $workingHourId;
    public WorkingHour $workingHour;
    public $activeTab = 'overview';
    
    public function mount($workingHourId)
    {
        $this->workingHourId = $workingHourId;
        $this->workingHour = WorkingHour::with(['staff'])->findOrFail($workingHourId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.workinghour-viewer', [
            'workingHour' => $this->workingHour,
            'activeTab' => $this->activeTab,
        ]);
    }
}
