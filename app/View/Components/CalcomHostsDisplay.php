<?php

namespace App\View\Components;

use App\Models\Service;
use App\Services\CalcomServiceHostsResolver;
use Illuminate\View\Component;
use Illuminate\View\View;

class CalcomHostsDisplay extends Component
{
    public function __construct(
        public Service $service,
    ) {
    }

    public function render(): View
    {
        $resolver = new CalcomServiceHostsResolver();
        $summary = $resolver->getHostsSummary($this->service);

        return view('components.calcom-hosts-display', [
            'summary' => $summary,
            'service' => $this->service,
        ]);
    }
}
