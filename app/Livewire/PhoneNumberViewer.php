<?php

namespace App\Livewire;

use App\Models\PhoneNumber;
use Livewire\Component;

class PhoneNumberViewer extends Component
{
    public $phoneNumberId;
    public PhoneNumber $phoneNumber;
    public $activeTab = 'overview';
    
    public function mount($phoneNumberId)
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->phoneNumber = PhoneNumber::with([])->findOrFail($phoneNumberId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.phoneNumber-viewer', [
            'phoneNumber' => $this->phoneNumber,
            'activeTab' => $this->activeTab,
        ]);
    }
}
