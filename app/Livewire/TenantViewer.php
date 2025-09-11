<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Component;

class TenantViewer extends Component
{
    public $tenantId;
    public Tenant $tenant;
    public $activeTab = 'overview';
    
    public function mount($tenantId)
    {
        $this->tenantId = $tenantId;
        $this->tenant = Tenant::with([])->findOrFail($tenantId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.tenant-viewer', [
            'tenant' => $this->tenant,
            'activeTab' => $this->activeTab,
        ]);
    }
}
