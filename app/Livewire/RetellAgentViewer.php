<?php

namespace App\Livewire;

use App\Models\RetellAgent;
use Livewire\Component;

class RetellAgentViewer extends Component
{
    public $agentId;
    public RetellAgent $agent;
    
    public function mount($agentId)
    {
        $this->agentId = $agentId;
        $this->agent = RetellAgent::with(['company'])->findOrFail($agentId);
    }
    
    public function render()
    {
        return view('livewire.retell-agent-viewer');
    }
    
    public function getFormattedConfiguration()
    {
        if (!$this->agent->configuration) {
            return null;
        }
        
        $config = is_string($this->agent->configuration) 
            ? json_decode($this->agent->configuration, true) 
            : $this->agent->configuration;
            
        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    public function getFormattedSettings()
    {
        if (!$this->agent->settings) {
            return null;
        }
        
        $settings = is_string($this->agent->settings) 
            ? json_decode($this->agent->settings, true) 
            : $this->agent->settings;
            
        return json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    public function toggleActive()
    {
        $this->agent->update([
            'is_active' => !$this->agent->is_active
        ]);
        
        $this->agent->refresh();
        
        session()->flash('message', $this->agent->is_active ? 'Agent aktiviert' : 'Agent deaktiviert');
    }
    
    public function syncAgent()
    {
        $this->agent->update([
            'last_synced_at' => now(),
            'sync_status' => 'synced'
        ]);
        
        $this->agent->refresh();
        
        session()->flash('message', 'Agent erfolgreich synchronisiert');
    }
}