<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\CostCalculator;
use Illuminate\Console\Command;

class TestCostRevenueCalculation extends Command
{
    protected $signature = 'test:cost-revenue {--call-id= : Specific call ID to test}';
    protected $description = 'Test cost calculation with revenue tracking (only paid appointments)';

    public function handle()
    {
        $this->info('🔍 Testing Cost & Revenue Calculation System');
        $this->line(str_repeat('━', 60));

        $callId = $this->option('call-id');

        if ($callId) {
            $call = Call::find($callId);
            if (!$call) {
                $this->error("Call ID {$callId} not found");
                return 1;
            }
            $calls = collect([$call]);
        } else {
            // Get last 5 calls with appointments
            $calls = Call::with('appointments')
                ->has('appointments')
                ->latest()
                ->limit(5)
                ->get();
        }

        $costCalculator = new CostCalculator();

        foreach ($calls as $call) {
            $this->line("\n" . str_repeat('─', 60));
            $this->info("📞 Call ID: {$call->id}");

            // Cost Details
            $this->line("\n💰 COST ANALYSIS:");
            $this->table(
                ['Component', 'Value (EUR cents)', 'EUR'],
                [
                    ['Retell Cost', $call->retell_cost_eur_cents ?? 0, number_format(($call->retell_cost_eur_cents ?? 0) / 100, 2)],
                    ['Twilio Cost', $call->twilio_cost_eur_cents ?? 0, number_format(($call->twilio_cost_eur_cents ?? 0) / 100, 2)],
                    ['Total External', $call->total_external_cost_eur_cents ?? 0, number_format(($call->total_external_cost_eur_cents ?? 0) / 100, 2)],
                    ['Base Cost (calculated)', $call->base_cost ?? 0, number_format(($call->base_cost ?? 0) / 100, 2)],
                ]
            );

            // Cost Calculation Method
            $usesActual = $call->total_external_cost_eur_cents && $call->total_external_cost_eur_cents > 0;
            $method = $usesActual ? '✅ ACTUAL (from external APIs)' : '⚠️ ESTIMATED (fallback)';
            $this->line("Method: {$method}");

            // Revenue Analysis
            $this->line("\n📊 REVENUE ANALYSIS:");

            $appointments = $call->appointments;
            $this->line("Total Appointments: {$appointments->count()}");

            if ($appointments->count() > 0) {
                $appointmentData = [];
                $totalRevenue = 0;
                $paidCount = 0;

                foreach ($appointments as $appt) {
                    $price = $appt->price ?? 0;
                    $isPaid = $price > 0;

                    if ($isPaid) {
                        $paidCount++;
                        $totalRevenue += $price;
                    }

                    $appointmentData[] = [
                        'ID' => $appt->id,
                        'Service' => $appt->service->name ?? 'N/A',
                        'Price (EUR)' => number_format($price, 2),
                        'Counted' => $isPaid ? '✅ YES' : '❌ FREE'
                    ];
                }

                $this->table(
                    ['ID', 'Service', 'Price (EUR)', 'Counted in Revenue'],
                    $appointmentData
                );

                $this->line("\n📈 Revenue Summary:");
                $this->line("  Paid Appointments: {$paidCount}");
                $this->line("  Free Appointments: " . ($appointments->count() - $paidCount));
                $this->line("  Total Revenue: €" . number_format($totalRevenue, 2));
            } else {
                $this->line("  No appointments for this call");
            }

            // Profit Calculation
            $this->line("\n💵 PROFIT CALCULATION:");
            $revenue = $call->getAppointmentRevenue();
            $profit = $call->getCallProfit();
            $baseCost = $call->base_cost ?? 0;
            $margin = $baseCost > 0 ? round(($profit / $baseCost) * 100, 1) : 0;

            $this->table(
                ['Metric', 'EUR cents', 'EUR'],
                [
                    ['Revenue (paid only)', $revenue, number_format($revenue / 100, 2)],
                    ['Cost (base)', $baseCost, number_format($baseCost / 100, 2)],
                    ['Profit', $profit, number_format($profit / 100, 2)],
                    ['Margin', $margin . '%', $margin . '%'],
                ]
            );

            $profitStatus = $profit > 0 ? '✅ PROFITABLE' : '❌ LOSS';
            $this->line("Status: {$profitStatus}");

            // Cost Breakdown
            if ($call->cost_breakdown) {
                $breakdown = json_decode($call->cost_breakdown, true);
                if ($breakdown && isset($breakdown['base'])) {
                    $this->line("\n🔍 DETAILED COST BREAKDOWN:");
                    $base = $breakdown['base'];

                    $this->table(
                        ['Component', 'Value'],
                        [
                            ['Retell (EUR cents)', $base['retell_cost_eur_cents'] ?? 0],
                            ['Twilio (EUR cents)', $base['twilio_cost_eur_cents'] ?? 0],
                            ['LLM Tokens (cents)', $base['llm_tokens'] ?? 0],
                            ['Total External (cents)', $base['total_external'] ?? 0],
                            ['Exchange Rate', $base['exchange_rate'] ?? 'N/A'],
                            ['Calculation Method', $base['calculation_method'] ?? 'N/A'],
                        ]
                    );
                }
            }
        }

        $this->line("\n" . str_repeat('━', 60));
        $this->info('✅ Cost & Revenue Test Complete');

        return 0;
    }
}
