<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class HorizonBadge extends Component { public function render() { return view("livewire.admin.horizon-badge", ["ok" => AppServicesHorizonHealth::ok()]); }}
{
    public function render()
    {
        return <<<'HTML'
        <div>
            {{-- If your happiness depends on money, you will never be happy with yourself. --}}
        </div>
        HTML;
    }
}
