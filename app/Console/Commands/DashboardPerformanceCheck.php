<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Analytics\RoiCalculationService;
use App\Models\Company;
use Carbon\Carbon;

class DashboardPerformanceCheck extends Command
{
    protected $signature = 'dashboard:performance-check {--company=1}';
    protected $description = 'Überprüfe Dashboard Performance und Query-Optimierungen';

    private RoiCalculationService $roiService;

    public function __construct(RoiCalculationService $roiService)
    {
        parent::__construct();
        $this->roiService = $roiService;
    }

    public function handle()
    {
        $companyId = $this->option('company');
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company mit ID {$companyId} nicht gefunden!");
            return 1;
        }

        $this->info("Dashboard Performance Check für: {$company->name}");
        $this->info(str_repeat('=', 50));

        // Enable query log
        DB::enableQueryLog();
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Simulate dashboard data loading
        $this->info("\n📊 Lade Dashboard-Daten...");
        
        // 1. ROI Calculation
        $roiStart = microtime(true);
        $roiData = $this->roiService->calculateRoi(
            $company,
            Carbon::now()->subDays(7),
            Carbon::now()
        );
        $roiTime = round((microtime(true) - $roiStart) * 1000, 2);
        $this->info("✓ ROI-Berechnung: {$roiTime}ms");

        // 2. System Health Check (simulated)
        $healthStart = microtime(true);
        $systemHealth = [
            'calcom' => ['status' => true, 'responseTime' => rand(50, 200)],
            'retell' => ['status' => true, 'responseTime' => rand(100, 300)],
        ];
        $healthTime = round((microtime(true) - $healthStart) * 1000, 2);
        $this->info("✓ System Health: {$healthTime}ms");

        // 3. Branch Performance
        $branchStart = microtime(true);
        $branches = [];
        foreach ($company->branches as $branch) {
            $branchRoi = $this->roiService->calculateRoi(
                $company,
                Carbon::now()->subDays(7),
                Carbon::now(),
                $branch
            );
            $branches[] = [
                'branch_name' => $branch->name,
                'roi_percentage' => $branchRoi['summary']['roi_percentage'],
                'calls' => $branchRoi['call_metrics']->total_calls ?? 0,
            ];
        }
        $branchTime = round((microtime(true) - $branchStart) * 1000, 2);
        $this->info("✓ Branch Performance ({$company->branches->count()} Filialen): {$branchTime}ms");

        // Calculate totals
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        $totalMemory = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));

        // Display results
        $this->info("\n📈 Performance Ergebnisse:");
        $this->info(str_repeat('-', 50));
        
        $this->table(
            ['Metrik', 'Wert', 'Status'],
            [
                ['Gesamtzeit', "{$totalTime}ms", $totalTime < 2000 ? '✅' : '⚠️'],
                ['Speichernutzung', "{$totalMemory}MB", $totalMemory < 50 ? '✅' : '⚠️'],
                ['Anzahl Queries', $queryCount, $queryCount < 15 ? '✅' : '⚠️'],
                ['Query Zeit', round($totalQueryTime, 2) . "ms", $totalQueryTime < 500 ? '✅' : '⚠️'],
                ['ROI Berechnung', "{$roiTime}ms", $roiTime < 500 ? '✅' : '⚠️'],
                ['Branch Performance', "{$branchTime}ms", $branchTime < 1000 ? '✅' : '⚠️'],
            ]
        );

        // Show slow queries
        $slowQueries = array_filter($queries, fn($q) => $q['time'] > 50);
        if (count($slowQueries) > 0) {
            $this->warn("\n⚠️  Langsame Queries gefunden (>50ms):");
            foreach ($slowQueries as $query) {
                $this->line("  - " . substr($query['query'], 0, 80) . "... ({$query['time']}ms)");
            }
        }

        // Optimization suggestions
        $this->info("\n💡 Optimierungsvorschläge:");
        
        if ($queryCount > 15) {
            $this->warn("- Zu viele Queries! Prüfe N+1 Query Probleme.");
            $this->line("  Nutze Eager Loading: ->with(['relation'])");
        }
        
        if ($totalTime > 2000) {
            $this->warn("- Dashboard lädt zu langsam!");
            $this->line("  Implementiere Redis Caching für ROI-Daten");
        }
        
        if ($totalMemory > 50) {
            $this->warn("- Hohe Speichernutzung!");
            $this->line("  Limitiere Datenmengen mit ->limit() und Pagination");
        }

        // Success metrics
        $this->info("\n✅ Ziel-Metriken:");
        $this->line("- Ladezeit: < 2000ms (aktuell: {$totalTime}ms)");
        $this->line("- Queries: < 15 (aktuell: {$queryCount})");
        $this->line("- Speicher: < 50MB (aktuell: {$totalMemory}MB)");

        return 0;
    }
}