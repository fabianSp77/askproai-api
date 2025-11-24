<?php

namespace App\Console\Commands\Metrics;

use App\Models\Company;
use App\Services\Metrics\ReservationMetricsCollector;
use Illuminate\Console\Command;

class ShowReservationMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:reservations
                            {--company= : Filter by company ID}
                            {--watch : Watch mode - refresh every 5 seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display optimistic reservation system metrics';

    /**
     * Execute the console command.
     */
    public function handle(ReservationMetricsCollector $collector)
    {
        $companyId = $this->option('company');
        $watchMode = $this->option('watch');

        if ($watchMode) {
            // Watch mode - refresh every 5 seconds
            while (true) {
                $this->displayMetrics($collector, $companyId);
                sleep(5);
                if (posix_isatty(STDOUT)) {
                    echo "\033[2J\033[H"; // Clear screen
                }
            }
        } else {
            // Single display
            $this->displayMetrics($collector, $companyId);
        }

        return 0;
    }

    private function displayMetrics(ReservationMetricsCollector $collector, ?string $companyId): void
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║       OPTIMISTIC RESERVATION SYSTEM METRICS                  ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($companyId) {
            // Display metrics for specific company
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company ID {$companyId} not found");
                return;
            }

            $this->info("Company: {$company->name} (ID: {$companyId})");
            $this->newLine();

            $metrics = $collector->getMetrics($companyId);
            $this->displayCompanyMetrics($metrics);
        } else {
            // Display metrics for all companies
            $companies = Company::all();

            if ($companies->isEmpty()) {
                $this->warn('No companies found in the system');
                return;
            }

            foreach ($companies as $company) {
                $this->info("Company: {$company->name} (ID: {$company->id})");
                $metrics = $collector->getMetrics($company->id);
                $this->displayCompanyMetrics($metrics);
                $this->newLine();
            }
        }

        $this->info('Last updated: ' . now()->format('Y-m-d H:i:s'));
    }

    private function displayCompanyMetrics(array $metrics): void
    {
        // Lifecycle Metrics
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created (Total)', $this->formatNumber($metrics['created'])],
                ['  └─ Compound', $this->formatNumber($metrics['created_compound'])],
                ['Converted', $this->formatSuccess($metrics['converted'])],
                ['  └─ Compound', $this->formatSuccess($metrics['converted_compound'])],
                ['Expired', $this->formatWarning($metrics['expired'])],
                ['Cancelled', $this->formatWarning($metrics['cancelled'])],
                ['Errors', $this->formatError($metrics['errors'])],
            ]
        );

        // Status Metrics
        $this->table(
            ['Status', 'Value'],
            [
                ['Active Reservations', $this->formatNumber($metrics['active'])],
                ['Conversion Rate', $this->formatPercentage($metrics['conversion_rate'])],
                ['Completion Rate', $this->formatPercentage($metrics['completion_rate'])],
                ['Active Rate', $this->formatPercentage($metrics['active_rate'])],
            ]
        );
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }

    private function formatSuccess(int $number): string
    {
        return "<fg=green>{$number}</>";
    }

    private function formatWarning(int $number): string
    {
        return $number > 0 ? "<fg=yellow>{$number}</>" : (string) $number;
    }

    private function formatError(int $number): string
    {
        return $number > 0 ? "<fg=red>{$number}</>" : (string) $number;
    }

    private function formatPercentage(float $percentage): string
    {
        $formatted = number_format($percentage, 2) . '%';

        if ($percentage >= 90) {
            return "<fg=green>{$formatted}</>";
        } elseif ($percentage >= 70) {
            return "<fg=yellow>{$formatted}</>";
        } else {
            return "<fg=red>{$formatted}</>";
        }
    }
}
