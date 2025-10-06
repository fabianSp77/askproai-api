<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CurrencyExchangeRate;
use App\Services\ExchangeRateService;

class CurrencyExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we already have exchange rates
        if (CurrencyExchangeRate::count() > 0) {
            $this->command->info('Exchange rates already exist, skipping...');
            return;
        }

        // Seed default exchange rates
        CurrencyExchangeRate::seedDefaultRates();
        $this->command->info('Default exchange rates seeded successfully.');

        // Try to fetch current rates from external sources
        $exchangeService = new ExchangeRateService();
        $exchangeService->ensureDefaultRates();

        // Try to update rates from ECB
        $rates = $exchangeService->fetchECBRates();
        if (!empty($rates)) {
            $this->command->info('Successfully fetched and updated rates from ECB.');
        } else {
            $this->command->warn('Could not fetch rates from ECB, using defaults.');
        }
    }
}