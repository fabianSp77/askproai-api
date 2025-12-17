<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use Illuminate\Support\Facades\DB;

class BenchmarkCallStatsQuery extends Command
{
    protected $signature = 'benchmark:callstats {--iterations=10}';
    protected $description = 'Benchmark CallStatsOverview query performance';

    public function handle()
    {
        $iterations = $this->option('iterations');

        $this->info('CallStatsOverview Query Performance Benchmark');
        $this->info('=============================================');
        $this->info('Table size: ' . Call::count() . ' rows');
        $this->info('Iterations: ' . $iterations);
        $this->newLine();

        // Test 1: Current implementation with whereDate()
        $this->info('Test 1: Current Implementation (whereDate)');
        $this->line('--------------------------------------------');

        $times1 = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $query = Call::whereDate('created_at', today())
                ->selectRaw('
                    COUNT(*) as total_count,
                    SUM(duration_sec) as total_duration,
                    SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count
                ')
                ->first();

            $elapsed = (microtime(true) - $start) * 1000;
            $times1[] = $elapsed;
            $this->line(sprintf('  Iteration %d: %.2fms', $i + 1, $elapsed));
        }

        $avg1 = array_sum($times1) / count($times1);
        $this->info(sprintf('Average: %.2fms', $avg1));
        $this->newLine();

        // Test 2: Optimized with whereBetween()
        $this->info('Test 2: Optimized Implementation (whereBetween)');
        $this->line('------------------------------------------------');

        $times2 = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $query = Call::whereBetween('created_at', [
                    today()->startOfDay(),
                    today()->endOfDay()
                ])
                ->selectRaw('
                    COUNT(*) as total_count,
                    SUM(duration_sec) as total_duration,
                    SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count
                ')
                ->first();

            $elapsed = (microtime(true) - $start) * 1000;
            $times2[] = $elapsed;
            $this->line(sprintf('  Iteration %d: %.2fms', $i + 1, $elapsed));
        }

        $avg2 = array_sum($times2) / count($times2);
        $this->info(sprintf('Average: %.2fms', $avg2));
        $this->newLine();

        // Show EXPLAIN for both queries
        $this->info('Query Execution Plans');
        $this->line('---------------------');

        // EXPLAIN for whereDate
        $sql1 = Call::whereDate('created_at', today())
            ->selectRaw('COUNT(*) as total_count')
            ->toSql();
        $bindings1 = Call::whereDate('created_at', today())->getBindings();
        $explain1 = DB::select('EXPLAIN ' . str_replace('?', "'".today()->toDateString()."'", $sql1));

        $this->line('whereDate() EXPLAIN:');
        $this->table(['Field', 'Value'], collect($explain1[0])->map(function($value, $key) {
            return ['field' => $key, 'value' => $value];
        })->values()->toArray());

        // EXPLAIN for whereBetween
        $sql2 = Call::whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->selectRaw('COUNT(*) as total_count')
            ->toSql();
        $explain2 = DB::select('EXPLAIN ' . str_replace(['?', '?'],
            ["'".today()->startOfDay()."'", "'".today()->endOfDay()."'"], $sql2));

        $this->line('whereBetween() EXPLAIN:');
        $this->table(['Field', 'Value'], collect($explain2[0])->map(function($value, $key) {
            return ['field' => $key, 'value' => $value];
        })->values()->toArray());

        // Results Summary
        $this->newLine();
        $this->info('Performance Summary');
        $this->line('------------------');

        $improvement = round((($avg1 - $avg2) / $avg1) * 100, 1);
        $speedup = round($avg1 / $avg2, 1);

        $this->table(
            ['Metric', 'whereDate()', 'whereBetween()', 'Improvement'],
            [
                ['Average Time', sprintf('%.2fms', $avg1), sprintf('%.2fms', $avg2), sprintf('%.1f%%', $improvement)],
                ['Min Time', sprintf('%.2fms', min($times1)), sprintf('%.2fms', min($times2)), ''],
                ['Max Time', sprintf('%.2fms', max($times1)), sprintf('%.2fms', max($times2)), ''],
                ['Speedup', '', '', sprintf('%.1fx faster', $speedup)],
            ]
        );

        // Recommendations
        $this->newLine();
        $this->info('Recommendations');
        $this->line('---------------');

        if ($improvement > 50) {
            $this->error('CRITICAL: whereDate() is causing significant performance degradation!');
            $this->warn('Action: Immediately replace all whereDate() with whereBetween()');
        } elseif ($improvement > 20) {
            $this->warn('WARNING: Noticeable performance difference detected');
            $this->info('Action: Consider updating to whereBetween() for better performance');
        } else {
            $this->info('Performance difference is minimal at current scale');
        }

        // Check for missing indexes
        $this->newLine();
        $this->info('Index Check');
        $this->line('-----------');

        $indexes = DB::select("SHOW INDEXES FROM calls WHERE Column_name = 'has_appointment'");
        if (empty($indexes)) {
            $this->error('Missing index on has_appointment column!');
            $this->warn('Run: ALTER TABLE calls ADD INDEX idx_has_appointment (has_appointment);');
        } else {
            $this->info('has_appointment index exists');
        }

        // Scalability projection
        $this->newLine();
        $this->info('Scalability Projection (based on current performance)');
        $this->line('------------------------------------------------------');

        $currentRows = Call::count();
        $projections = [10000, 100000, 1000000];

        $data = [];
        foreach ($projections as $rows) {
            $factor = $rows / $currentRows;
            $projected1 = $avg1 * $factor;
            $projected2 = $avg2 * sqrt($factor); // Assumes index scales logarithmically

            $data[] = [
                'Rows' => number_format($rows),
                'whereDate()' => sprintf('%.0fms', $projected1),
                'whereBetween()' => sprintf('%.0fms', $projected2),
                'Difference' => sprintf('%.0fx', $projected1 / $projected2)
            ];
        }

        $this->table(['Rows', 'whereDate()', 'whereBetween()', 'Difference'], $data);
    }
}