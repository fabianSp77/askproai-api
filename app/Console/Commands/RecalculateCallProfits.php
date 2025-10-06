<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\CostCalculator;
use Illuminate\Console\Command;

class RecalculateCallProfits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:recalculate-profits
                            {--limit=1000 : Anzahl der Anrufe pro Batch}
                            {--from= : Von Datum (YYYY-MM-DD)}
                            {--to= : Bis Datum (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Berechnet Profit-Daten für alle Anrufe neu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║      PROFIT-NEUBERECHNUNG FÜR ALLE ANRUFE                ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $calculator = new CostCalculator();
        $limit = (int) $this->option('limit');
        $from = $this->option('from');
        $to = $this->option('to');

        // Build query
        $query = Call::query();

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
            $this->info("Von Datum: $from");
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
            $this->info("Bis Datum: $to");
        }

        $totalCalls = $query->count();
        $this->info("Gesamt Anrufe zu verarbeiten: $totalCalls");
        $this->newLine();

        if ($totalCalls === 0) {
            $this->warn('Keine Anrufe gefunden.');
            return Command::SUCCESS;
        }

        // Process in batches
        $processed = 0;
        $updated = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($totalCalls);
        $progressBar->start();

        $query->chunk($limit, function ($calls) use ($calculator, &$processed, &$updated, &$failed, $progressBar) {
            foreach ($calls as $call) {
                try {
                    // Calculate and update costs with profit
                    $calculator->updateCallCosts($call);

                    if ($call->wasChanged()) {
                        $updated++;
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("\nFehler bei Anruf {$call->id}: " . $e->getMessage());
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║                    ZUSAMMENFASSUNG                        ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->table(
            ['Metrik', 'Wert'],
            [
                ['Verarbeitet', $processed],
                ['Aktualisiert', $updated],
                ['Fehlgeschlagen', $failed],
            ]
        );

        // Show sample profit data
        if ($updated > 0) {
            $this->newLine();
            $this->info('📊 Beispiel Profit-Daten (letzte 5 aktualisierte Anrufe):');

            $sampleCalls = Call::whereNotNull('total_profit')
                ->latest('updated_at')
                ->limit(5)
                ->get();

            $this->table(
                ['ID', 'Basis (€)', 'Mandant (€)', 'Kunde (€)', 'Profit (€)', 'Marge (%)'],
                $sampleCalls->map(function ($call) {
                    return [
                        $call->id,
                        number_format($call->base_cost / 100, 2, ',', '.'),
                        number_format($call->reseller_cost / 100, 2, ',', '.'),
                        number_format($call->customer_cost / 100, 2, ',', '.'),
                        number_format($call->total_profit / 100, 2, ',', '.'),
                        $call->profit_margin_total ?? 0,
                    ];
                })->toArray()
            );
        }

        $this->info('✅ Profit-Neuberechnung abgeschlossen!');

        return Command::SUCCESS;
    }
}
