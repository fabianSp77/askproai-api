<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupAlerts extends Command
{
    protected $signature = 'alerts:cleanup {--days=30 : Number of days to keep alerts}';
    protected $description = 'Clean up old system alerts';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up alerts older than {$days} days...");
        
        // Delete old acknowledged alerts
        $deletedAcknowledged = DB::table('system_alerts')
            ->where('acknowledged', true)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
        
        // Delete old low/medium severity alerts (keep high/critical longer)
        $deletedLowSeverity = DB::table('system_alerts')
            ->whereIn('severity', ['low', 'medium'])
            ->where('created_at', '<', $cutoffDate->copy()->addDays(15)) // Keep these 15 days less
            ->delete();
        
        $totalDeleted = $deletedAcknowledged + $deletedLowSeverity;
        
        $this->info("✅ Deleted {$totalDeleted} old alerts");
        $this->info("  - Acknowledged: {$deletedAcknowledged}");
        $this->info("  - Low/Medium severity: {$deletedLowSeverity}");
        
        // Show remaining alert statistics
        $stats = DB::table('system_alerts')
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get();
        
        $this->info("\nRemaining alerts by severity:");
        foreach ($stats as $stat) {
            $this->info("  - {$stat->severity}: {$stat->count}");
        }
        
        return 0;
    }
}