<?php

namespace App\Livewire;

use App\Models\Company;
use Livewire\Component;

class CompanyViewer extends Component
{
    public $companyId;
    public Company $company;
    public $activeTab = 'overview';
    
    public function mount($companyId)
    {
        $this->companyId = $companyId;
        $this->company = Company::with(['staff', 'services', 'phoneNumbers'])->findOrFail($companyId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.company-viewer', [
            'company' => $this->company,
            'activeTab' => $this->activeTab,
        ]);
    }
}
