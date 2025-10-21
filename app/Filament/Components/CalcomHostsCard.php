<?php

namespace App\Filament\Components;

use App\Models\Service;
use App\Services\CalcomServiceHostsResolver;
use Filament\Support\Enums\MaxWidth;
use Illuminate\View\Component;

/**
 * CalcomHostsCard
 *
 * Displays Cal.com hosts for a service with their mapping status and available services
 * Replaces the error-prone staff repeater with automatic Cal.com data
 */
class CalcomHostsCard extends Component
{
    public function __construct(
        public Service $service,
    ) {
    }

    public function render()
    {
        $resolver = new CalcomServiceHostsResolver();
        $summary = $resolver->getHostsSummary($this->service);

        return view('filament.components.calcom-hosts-card', [
            'summary' => $summary,
            'service' => $this->service,
        ]);
    }
}
