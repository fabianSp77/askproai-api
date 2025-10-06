<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\CostCalculator;
use Illuminate\Console\Command;

class RecalculateCallCosts extends Command
{
    protected $signature = 'costs:recalculate {--force : Force recalculation without confirmation}';
    protected $description = 'Recalculate all call costs using the corrected CostCalculator logic';

    public function handle()
    {
        $this->info('🔄 Recalculating Call Costs');
        $this->line(str_repeat('━', 60));

        // Get calls with external costs (these need recalculation)
        $callsWithExternal = Call::whereNotNull('total_external_cost_eur_cents')
            ->where('total_external_cost_eur_cents', '>', 0)
            ->count();

        // Get calls without external costs (use fallback)
        $callsWithoutExternal = Call::where(function($query) {
            $query->whereNull('total_external_cost_eur_cents')
                  ->orWhere('total_external_cost_eur_cents', '=', 0);
        })->count();

        $this->line("\n📊 Statistics:");
        $this->line("  Calls with external costs: {$callsWithExternal}");
        $this->line("  Calls without external costs: {$callsWithoutExternal}");
        $this->line("  Total calls: " . ($callsWithExternal + $callsWithoutExternal));

        if (!$this->option('force') && !$this->confirm("\nRecalculate costs for ALL calls?", true)) {
            $this->warn('Aborted.');
            return 1;
        }

        $this->line("\n🔄 Processing calls...");
        $bar = $this->output->createProgressBar(Call::count());

        $calculator = new CostCalculator();
        $updatedCount = 0;
        $errorCount = 0;

        Call::chunk(100, function ($calls) use ($calculator, $bar, &$updatedCount, &$errorCount) {
            foreach ($calls as $call) {
                try {
                    $calculator->updateCallCosts($call);
                    $updatedCount++;
                } catch (\Exception $e) {
                    $this->error("\nError updating call {$call->id}: {$e->getMessage()}");
                    $errorCount++;
                }
                $bar->advance();
            }
        });

        $bar->finish();

        $this->line("\n\n" . str_repeat('━', 60));
        $this->info("✅ Recalculation Complete!");
        $this->line("  Updated: {$updatedCount} calls");
        if ($errorCount > 0) {
            $this->warn("  Errors: {$errorCount} calls");
        }

        // Show sample results
        $this->line("\n📊 Sample Results (with external costs):");
        $samples = Call::whereNotNull('total_external_cost_eur_cents')
            ->where('total_external_cost_eur_cents', '>', 0)
            ->latest()
            ->limit(3)
            ->get();

        $sampleData = [];
        foreach ($samples as $call) {
            $sampleData[] = [
                'ID' => $call->id,
                'External (¢)' => $call->total_external_cost_eur_cents,
                'Base (¢)' => $call->base_cost,
                'Match' => $call->total_external_cost_eur_cents == $call->base_cost ? '✅' : '❌'
            ];
        }

        $this->table(['ID', 'External (¢)', 'Base (¢)', 'Match'], $sampleData);

        return 0;
    }
}
