<?php

namespace App\Livewire\Admin;

use App\Services\HorizonHealth;
use Livewire\Component;

class HorizonBadge extends Component
{
    public function render()
    {
        return view('livewire.admin.horizon-badge', [
            'ok' => HorizonHealth::ok(),
        ]);
    }
}
