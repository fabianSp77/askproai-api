<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System-wide German tax rates
        $systemTaxRates = [
            [
                'name' => 'Standard (19%)',
                'rate' => 19.00,
                'is_default' => true,
                'is_system' => true,
                'description' => 'Regulärer Mehrwertsteuersatz in Deutschland',
                'valid_from' => '2007-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ermäßigt (7%)',
                'rate' => 7.00,
                'is_default' => false,
                'is_system' => true,
                'description' => 'Ermäßigter Steuersatz (z.B. Lebensmittel, Bücher)',
                'valid_from' => '2007-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Steuerfrei (0%)',
                'rate' => 0.00,
                'is_default' => false,
                'is_system' => true,
                'description' => 'Steuerfreie Leistungen / Kleinunternehmer',
                'valid_from' => '2000-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reverse Charge (0%)',
                'rate' => 0.00,
                'is_default' => false,
                'is_system' => true,
                'description' => 'Umkehrung der Steuerschuldnerschaft (B2B EU)',
                'valid_from' => '2010-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tax_rates')->insert($systemTaxRates);

        // System-wide payment terms
        $paymentTerms = [
            [
                'name' => 'Sofort fällig',
                'days' => 0,
                'is_default' => false,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '14 Tage netto',
                'days' => 14,
                'is_default' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '30 Tage netto',
                'days' => 30,
                'is_default' => false,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '60 Tage netto',
                'days' => 60,
                'is_default' => false,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_terms')->insert($paymentTerms);
    }
}