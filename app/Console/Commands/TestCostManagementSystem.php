<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\CurrencyExchangeRate;
use App\Models\PlatformCost;
use App\Services\ExchangeRateService;
use App\Services\PlatformCostService;

class TestCostManagementSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the cost management system components';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 Testing Cost Management System');
        $this->line('');

        $allTestsPassed = true;

        // Test 1: Check database tables
        $this->info('1️⃣ Checking database tables...');
        try {
            $tables = [
                'platform_costs' => PlatformCost::count(),
                'currency_exchange_rates' => CurrencyExchangeRate::count(),
                'monthly_cost_reports' => \App\Models\MonthlyCostReport::count(),
            ];

            foreach ($tables as $table => $count) {
                $this->line("   ✅ Table '{$table}' exists with {$count} records");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Database table check failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');

        // Test 2: Check exchange rates
        $this->info('2️⃣ Testing exchange rates...');
        try {
            $usdToEur = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
            if ($usdToEur) {
                $this->line("   ✅ USD to EUR rate: " . number_format($usdToEur, 4));
            } else {
                $this->warn("   ⚠️ No USD to EUR rate found");
            }

            $eurToUsd = CurrencyExchangeRate::getCurrentRate('EUR', 'USD');
            if ($eurToUsd) {
                $this->line("   ✅ EUR to USD rate: " . number_format($eurToUsd, 4));
            } else {
                $this->warn("   ⚠️ No EUR to USD rate found");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Exchange rate test failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');

        // Test 3: Test currency conversion
        $this->info('3️⃣ Testing currency conversion...');
        try {
            $exchangeService = new ExchangeRateService();

            $testAmountUsd = 100;
            $convertedEur = $exchangeService->convertUsdToEur($testAmountUsd);
            $this->line("   ✅ \$100 USD = €" . number_format($convertedEur, 2) . " EUR");

            $testCentsUsd = 1000; // $10
            $convertedCentsEur = $exchangeService->convertUsdCentsToEurCents($testCentsUsd);
            $this->line("   ✅ 1000¢ USD = {$convertedCentsEur}¢ EUR");
        } catch (\Exception $e) {
            $this->error("   ❌ Currency conversion test failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');

        // Test 4: Check recent calls with costs
        $this->info('4️⃣ Checking recent calls with external costs...');
        try {
            $recentCallsWithCosts = Call::whereNotNull('total_external_cost_eur_cents')
                ->where('total_external_cost_eur_cents', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentCallsWithCosts->count() > 0) {
                $this->line("   ✅ Found {$recentCallsWithCosts->count()} calls with external costs:");
                foreach ($recentCallsWithCosts as $call) {
                    $this->line(sprintf(
                        "      - Call #%d: Retell: \$%.4f, Twilio: \$%.4f, Total: €%.2f",
                        $call->id,
                        $call->retell_cost_usd ?? 0,
                        $call->twilio_cost_usd ?? 0,
                        ($call->total_external_cost_eur_cents ?? 0) / 100
                    ));
                }
            } else {
                $this->warn("   ⚠️ No calls found with external costs tracked");
                $this->line("      (This is normal if no calls have been processed since the update)");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Call cost check failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');

        // Test 5: Check platform costs
        $this->info('5️⃣ Checking platform cost records...');
        try {
            $platformCosts = PlatformCost::selectRaw('platform, COUNT(*) as count, SUM(amount_cents) as total_cents')
                ->groupBy('platform')
                ->get();

            if ($platformCosts->count() > 0) {
                $this->line("   ✅ Platform costs by service:");
                foreach ($platformCosts as $cost) {
                    $this->line(sprintf(
                        "      - %s: %d records, Total: €%.2f",
                        ucfirst($cost->platform),
                        $cost->count,
                        $cost->total_cents / 100
                    ));
                }
            } else {
                $this->warn("   ⚠️ No platform costs tracked yet");
                $this->line("      (Costs will be tracked when calls are processed)");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Platform cost check failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');

        // Test 6: Verify configuration
        $this->info('6️⃣ Checking configuration...');
        try {
            $configChecks = [
                'Retell cost per minute' => config('platform-costs.retell.pricing.per_minute_usd'),
                'Twilio inbound cost' => config('platform-costs.twilio.pricing.inbound_per_minute_usd'),
                'Cal.com user cost' => config('platform-costs.calcom.pricing.per_user_per_month_usd'),
                'Default USD to EUR rate' => config('platform-costs.exchange_rates.defaults.USD_TO_EUR'),
            ];

            foreach ($configChecks as $name => $value) {
                if ($value !== null) {
                    $this->line("   ✅ {$name}: {$value}");
                } else {
                    $this->warn("   ⚠️ {$name}: Not configured");
                }
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Configuration check failed: " . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->line('');
        $this->line('═══════════════════════════════════════════');

        if ($allTestsPassed) {
            $this->info('✅ All tests passed! Cost management system is operational.');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Some tests failed. Please check the errors above.');
            return Command::FAILURE;
        }
    }
}