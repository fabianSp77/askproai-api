<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\HorizonHealth;

class HorizonBadge extends Component
{
    public function render()
    {
        return view("livewire.admin.horizon-badge", [
            "ok" => HorizonHealth::ok()
        ]);
    }
}
