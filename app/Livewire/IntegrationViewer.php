<?php

namespace App\Livewire;

use App\Models\Integration;
use Livewire\Component;

class IntegrationViewer extends Component
{
    public $integrationId;
    public Integration $integration;
    public $activeTab = 'overview';
    
    public function mount($integrationId)
    {
        $this->integrationId = $integrationId;
        $this->integration = Integration::findOrFail($integrationId);
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.integration-viewer', [
            'integration' => $this->integration,
            'activeTab' => $this->activeTab,
        ]);
    }
}
