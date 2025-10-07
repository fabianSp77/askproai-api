<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:update
                            {--force : Force update even if rates are recent}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Update currency exchange rates from ECB API';

    private ExchangeRateService $exchangeRateService;

    public function __construct()
    {
        parent::__construct();
        $this->exchangeRateService = app(ExchangeRateService::class);
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $mode = $dryRun ? '🧪 DRY-RUN MODE' : '🔥 UPDATING RATES';
        $this->info($mode);
        $this->newLine();

        // Check if rates are stale
        if (!$force && !$dryRun) {
            $staleness = $this->checkRateStaleness();
            if ($staleness['hours_old'] < 12) {
                $this->info("✅ Rates are fresh (updated {$staleness['hours_old']}h ago)");
                $this->info('💡 Use --force to update anyway');
                return 0;
            }

            if ($staleness['hours_old'] > 168) { // 7 days
                $this->warn("⚠️  WARNING: Rates are {$staleness['days_old']} days old!");
            }
        }

        $this->info('🌍 Fetching exchange rates from ECB...');

        if ($dryRun) {
            // Dry-run: Just show what would be fetched
            try {
                $rates = $this->exchangeRateService->fetchECBRates();

                if (empty($rates)) {
                    $this->error('❌ Failed to fetch rates from ECB');
                    return 1;
                }

                $this->newLine();
                $this->info('📊 Rates that would be updated:');
                $this->table(
                    ['Currency Pair', 'New Rate', 'Action'],
                    [
                        ['EUR → USD', number_format($rates['USD'], 6), 'Create/Update'],
                        ['USD → EUR', number_format(1 / $rates['USD'], 6), 'Create/Update'],
                        ['EUR → GBP', number_format($rates['GBP'], 6), 'Create/Update'],
                        ['GBP → EUR', number_format(1 / $rates['GBP'], 6), 'Create/Update'],
                        ['USD → GBP', number_format($rates['GBP'] / $rates['USD'], 6), 'Create/Update'],
                        ['GBP → USD', number_format($rates['USD'] / $rates['GBP'], 6), 'Create/Update'],
                    ]
                );

                $this->newLine();
                $this->info('✅ Dry-run complete. No changes were made.');
                return 0;

            } catch (\Exception $e) {
                $this->error("❌ Error: {$e->getMessage()}");
                return 1;
            }
        }

        // Production update
        try {
            $results = $this->exchangeRateService->updateAllRates();

            if (empty($results)) {
                $this->error('❌ Failed to update rates from any source');
                $this->warn('💡 Check your internet connection and API availability');

                Log::error('Exchange rate update failed', [
                    'command' => 'exchange-rates:update',
                    'results' => $results
                ]);

                return 1;
            }

            $this->newLine();
            $this->info('✅ Exchange rates updated successfully!');
            $this->newLine();

            // Display updated rates
            foreach ($results as $source => $rates) {
                $this->line("📡 Source: " . strtoupper($source));
                $this->table(
                    ['Currency Pair', 'Rate'],
                    [
                        ['EUR → USD', number_format($rates['USD'] ?? 0, 6)],
                        ['USD → EUR', number_format(1 / ($rates['USD'] ?? 1), 6)],
                        ['EUR → GBP', number_format($rates['GBP'] ?? 0, 6)],
                        ['GBP → EUR', number_format(1 / ($rates['GBP'] ?? 1), 6)],
                    ]
                );
                $this->newLine();
            }

            Log::info('Exchange rates updated successfully', [
                'command' => 'exchange-rates:update',
                'sources' => array_keys($results),
                'rates' => $results
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to update exchange rates");
            $this->error("Error: {$e->getMessage()}");

            Log::error('Exchange rate update error', [
                'command' => 'exchange-rates:update',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Check how stale the current rates are
     */
    private function checkRateStaleness(): array
    {
        $latestRate = \App\Models\CurrencyExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'EUR')
            ->where('is_active', true)
            ->orderBy('valid_from', 'desc')
            ->first();

        if (!$latestRate) {
            return [
                'hours_old' => 999999,
                'days_old' => 999999,
                'last_update' => null
            ];
        }

        $hoursOld = now()->diffInHours($latestRate->valid_from);
        $daysOld = now()->diffInDays($latestRate->valid_from);

        return [
            'hours_old' => $hoursOld,
            'days_old' => $daysOld,
            'last_update' => $latestRate->valid_from
        ];
    }
}
