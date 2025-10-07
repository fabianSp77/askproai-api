<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\CurrencyExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateRetellCostsCommand extends Command
{
    protected $signature = 'retell:validate-costs
                            {--days=7 : Number of days to validate}
                            {--show-all : Show all calls, not just anomalies}
                            {--fix : Attempt to fix anomalies automatically}';

    protected $description = 'Validate Retell cost calculations and detect anomalies';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $showAll = $this->option('show-all');
        $fix = $this->option('fix');

        $this->info('🔍 Validating Retell Cost Calculations');
        $this->info("📅 Period: Last {$days} days");
        $this->newLine();

        // Get calls with retell costs
        $calls = Call::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('retell_cost_usd')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($calls->isEmpty()) {
            $this->warn('No calls with Retell costs found in the specified period');
            return 0;
        }

        $this->info("📊 Found {$calls->count()} calls to validate");
        $this->newLine();

        $stats = [
            'total' => $calls->count(),
            'correct' => 0,
            'missing_eur' => 0,
            'wrong_conversion' => 0,
            'missing_rate' => 0,
            'implausible_rate' => 0,
            'zero_cost' => 0,
        ];

        $anomalies = [];
        $progressBar = $this->output->createProgressBar($calls->count());
        $progressBar->start();

        foreach ($calls as $call) {
            $issues = $this->validateCall($call);

            if (empty($issues)) {
                $stats['correct']++;
            } else {
                foreach ($issues as $issue) {
                    $stats[$issue['type']]++;
                }
                $anomalies[] = [
                    'call' => $call,
                    'issues' => $issues
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display statistics
        $this->displayStatistics($stats);

        // Display anomalies
        if (!empty($anomalies)) {
            $this->newLine();
            $this->displayAnomalies($anomalies, $showAll);

            if ($fix) {
                $this->newLine();
                $this->attemptFixes($anomalies);
            }
        } else {
            $this->newLine();
            $this->info('✅ No anomalies found! All cost calculations are correct.');
        }

        return 0;
    }

    private function validateCall(Call $call): array
    {
        $issues = [];

        // Check 1: Zero cost
        if ($call->retell_cost_usd == 0) {
            $issues[] = [
                'type' => 'zero_cost',
                'message' => 'Retell cost is zero',
                'severity' => 'warning'
            ];
        }

        // Check 2: Missing EUR cents
        if ($call->retell_cost_usd > 0 && ($call->retell_cost_eur_cents === null || $call->retell_cost_eur_cents == 0)) {
            $issues[] = [
                'type' => 'missing_eur',
                'message' => 'EUR cents missing or zero',
                'severity' => 'error'
            ];
        }

        // Check 3: Missing exchange rate
        if ($call->retell_cost_usd > 0 && !$call->exchange_rate_used) {
            $issues[] = [
                'type' => 'missing_rate',
                'message' => 'Exchange rate not stored',
                'severity' => 'warning'
            ];
        }

        // Check 4: Implausible exchange rate (should be between 0.70 and 1.20)
        if ($call->exchange_rate_used && ($call->exchange_rate_used < 0.70 || $call->exchange_rate_used > 1.20)) {
            $issues[] = [
                'type' => 'implausible_rate',
                'message' => sprintf('Implausible rate: %.6f', $call->exchange_rate_used),
                'severity' => 'error'
            ];
        }

        // Check 5: Wrong conversion calculation
        if ($call->retell_cost_usd > 0 && $call->retell_cost_eur_cents > 0 && $call->exchange_rate_used) {
            $expectedEurCents = (int) round($call->retell_cost_usd * $call->exchange_rate_used * 100);
            $actualEurCents = $call->retell_cost_eur_cents;
            $diff = abs($expectedEurCents - $actualEurCents);

            // Allow 1 cent tolerance due to rounding
            if ($diff > 1) {
                $issues[] = [
                    'type' => 'wrong_conversion',
                    'message' => sprintf(
                        'Conversion mismatch: Expected %d cents, got %d cents (diff: %d)',
                        $expectedEurCents,
                        $actualEurCents,
                        $diff
                    ),
                    'severity' => 'error',
                    'expected' => $expectedEurCents,
                    'actual' => $actualEurCents
                ];
            }
        }

        return $issues;
    }

    private function displayStatistics(array $stats): void
    {
        $this->info('📊 Validation Statistics:');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['✅ Correct', $stats['correct'], $this->percentage($stats['correct'], $stats['total'])],
                ['⚠️  Zero Cost', $stats['zero_cost'], $this->percentage($stats['zero_cost'], $stats['total'])],
                ['❌ Missing EUR', $stats['missing_eur'], $this->percentage($stats['missing_eur'], $stats['total'])],
                ['❌ Wrong Conversion', $stats['wrong_conversion'], $this->percentage($stats['wrong_conversion'], $stats['total'])],
                ['⚠️  Missing Rate', $stats['missing_rate'], $this->percentage($stats['missing_rate'], $stats['total'])],
                ['❌ Implausible Rate', $stats['implausible_rate'], $this->percentage($stats['implausible_rate'], $stats['total'])],
            ]
        );
    }

    private function displayAnomalies(array $anomalies, bool $showAll): void
    {
        $this->error("⚠️  Found " . count($anomalies) . " calls with anomalies:");
        $this->newLine();

        $limit = $showAll ? count($anomalies) : 10;
        $displayed = 0;

        foreach ($anomalies as $anomaly) {
            if ($displayed >= $limit) {
                break;
            }

            $call = $anomaly['call'];
            $this->line("📞 Call #{$call->id} - {$call->created_at->format('Y-m-d H:i')}");
            $this->line("   USD: \${$call->retell_cost_usd} | EUR: {$call->retell_cost_eur_cents} cents | Rate: {$call->exchange_rate_used}");

            foreach ($anomaly['issues'] as $issue) {
                $icon = $issue['severity'] === 'error' ? '❌' : '⚠️ ';
                $this->line("   {$icon} {$issue['message']}");
            }

            $this->newLine();
            $displayed++;
        }

        if ($displayed < count($anomalies)) {
            $remaining = count($anomalies) - $displayed;
            $this->info("... and {$remaining} more anomalies (use --show-all to see all)");
        }
    }

    private function attemptFixes(array $anomalies): void
    {
        $this->warn('🔧 Attempting automatic fixes...');

        $fixed = 0;
        $failed = 0;

        foreach ($anomalies as $anomaly) {
            $call = $anomaly['call'];
            $hasFixableIssue = false;

            foreach ($anomaly['issues'] as $issue) {
                if ($issue['type'] === 'missing_eur' || $issue['type'] === 'wrong_conversion') {
                    $hasFixableIssue = true;
                    break;
                }
            }

            if (!$hasFixableIssue) {
                continue;
            }

            try {
                // Recalculate EUR cents
                $rate = $call->exchange_rate_used ?? CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? 0.92;
                $newEurCents = (int) round($call->retell_cost_usd * $rate * 100);

                $call->update([
                    'retell_cost_eur_cents' => $newEurCents,
                    'exchange_rate_used' => $rate,
                ]);

                $fixed++;
                $this->line("✅ Fixed Call #{$call->id}: {$newEurCents} EUR cents");

            } catch (\Exception $e) {
                $failed++;
                $this->line("❌ Failed to fix Call #{$call->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("✅ Fixed: {$fixed} calls");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed} calls");
        }
    }

    private function percentage(int $count, int $total): string
    {
        if ($total == 0) {
            return '0%';
        }

        return round(($count / $total) * 100, 1) . '%';
    }
}
