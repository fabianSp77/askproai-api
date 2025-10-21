<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\View;

/**
 * CalcomHostsHtmlGenerator
 *
 * Generates HTML string for displaying Cal.com hosts in Filament forms
 */
class CalcomHostsHtmlGenerator
{
    public function __construct(
        private CalcomServiceHostsResolver $resolver = new CalcomServiceHostsResolver()
    ) {
    }

    /**
     * Generate HTML for service hosts display
     */
    public function generate(Service $service): string
    {
        try {
            $summary = $this->resolver->getHostsSummary($service);

            return View::make('filament.fields.calcom-hosts-display', [
                'record' => $service,
                'summary' => $summary,
            ])->render();
        } catch (\Exception $e) {
            \Log::error('CalcomHostsHtmlGenerator error', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return '<div class="p-4 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 rounded">
                ❌ Fehler beim Laden der Cal.com Hosts: ' . $e->getMessage() . '
            </div>';
        }
    }
}
