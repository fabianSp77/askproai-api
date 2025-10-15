<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use Carbon\Carbon;

/**
 * Monitor V77 Prompt Performance
 * Track anonymous calls success rate after V77 deployment
 */
class MonitorAnonymousCallsSuccess extends Command
{
    protected $signature = 'monitor:anonymous-calls {--days=1}';
    protected $description = 'Monitor V77 anonymous calls success rate';

    public function handle()
    {
        $days = $this->option('days');
        $since = Carbon::now()->subDays($days);

        $this->info("📊 V77 Monitoring Report - Last {$days} day(s)");
        $this->info("═══════════════════════════════════════════════════");

        // Anonymous calls statistics
        $anonymousCalls = Call::where('from_number', 'anonymous')
            ->where('created_at', '>=', $since)
            ->get();

        $totalAnonymous = $anonymousCalls->count();
        $withCustomer = $anonymousCalls->whereNotNull('customer_id')->count();
        $withoutCustomer = $totalAnonymous - $withCustomer;

        $successRate = $totalAnonymous > 0
            ? round(($withCustomer / $totalAnonymous) * 100, 1)
            : 0;

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Anonymous Calls', $totalAnonymous, '100%'],
                ['✅ With customer_id', $withCustomer, $successRate . '%'],
                ['❌ Without customer_id', $withoutCustomer, (100 - $successRate) . '%'],
            ]
        );

        // Invalid names check
        $invalidNames = Call::where('created_at', '>=', $since)
            ->where(function($q) {
                $q->where('customer_name', 'LIKE', '%guten Tag%')
                  ->orWhere('customer_name', 'LIKE', '%mein Name%')
                  ->orWhere('customer_name', 'LIKE', '%Herr%')
                  ->orWhere('customer_name', 'LIKE', '%Frau%');
            })
            ->count();

        $this->newLine();
        $this->info("🔍 Name Validation:");
        if ($invalidNames > 0) {
            $this->warn("⚠️  Found {$invalidNames} invalid names (guten Tag, mein Name, etc.)");
        } else {
            $this->info("✅ No invalid names detected - V77 validation working!");
        }

        // Target comparison
        $this->newLine();
        $this->info("🎯 V77 Target vs. Actual:");

        $target = 85; // 85% success rate target
        if ($successRate >= $target) {
            $this->info("✅ SUCCESS: {$successRate}% ≥ {$target}% target");
        } else {
            $this->warn("⚠️  Below target: {$successRate}% < {$target}% (expected after V77)");
        }

        return 0;
    }
}
