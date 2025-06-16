<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\HorizonHealth;

class HorizonBadge extends Component
{
    public function render()
    {
        try {
            $ok = HorizonHealth::ok();
        } catch (\Exception $e) {
            // Fallback wenn Horizon nicht verfügbar ist
            $ok = true;
        }
        
        return view('livewire.admin.horizon-badge', [
            'ok' => $ok
        ]);
    }
}
