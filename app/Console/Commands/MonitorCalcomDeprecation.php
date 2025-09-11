<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomHybridService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MonitorCalcomDeprecation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:monitor-deprecation 
                            {--alert : Send email alerts if thresholds are exceeded}
                            {--report : Generate detailed usage report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Cal.com V1 API usage and deprecation timeline';

    /**
     * V1 sunset date
     */
    const SUNSET_DATE = '2025-12-31';
    
    /**
     * Warning thresholds
     */
    const WARNING_DAYS_REMAINING = 90;  // Warn when less than 90 days remain
    const WARNING_V1_USAGE = 50;        // Warn when V1 usage exceeds 50%
    const CRITICAL_DAYS_REMAINING = 30; // Critical when less than 30 days remain
    const CRITICAL_V1_USAGE = 75;       // Critical when V1 usage exceeds 75%

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  Cal.com V1 API Deprecation Monitor   ');
        $this->info('========================================');
        
        // Get current metrics
        $service = new CalcomHybridService();
        $metrics = $service->getMetrics();
        
        // Calculate days remaining
        $sunsetDate = Carbon::parse(self::SUNSET_DATE);
        $today = Carbon::now();
        $daysRemaining = $today->diffInDays($sunsetDate, false);
        
        // Determine alert level
        $alertLevel = $this->determineAlertLevel($daysRemaining, $metrics['v1_percentage']);
        
        // Display status
        $this->displayStatus($metrics, $daysRemaining, $alertLevel);
        
        // Generate report if requested
        if ($this->option('report')) {
            $this->generateDetailedReport($metrics, $daysRemaining);
        }
        
        // Send alerts if requested and thresholds exceeded
        if ($this->option('alert') && $alertLevel !== 'OK') {
            $this->sendAlerts($metrics, $daysRemaining, $alertLevel);
        }
        
        // Log metrics
        $this->logMetrics($metrics, $daysRemaining, $alertLevel);
        
        // Return appropriate exit code
        return match($alertLevel) {
            'CRITICAL' => 2,
            'WARNING' => 1,
            default => 0
        };
    }
    
    /**
     * Determine alert level based on metrics
     */
    private function determineAlertLevel(int $daysRemaining, float $v1Usage): string
    {
        if ($daysRemaining <= 0) {
            return 'EXPIRED';
        }
        
        if ($daysRemaining <= self::CRITICAL_DAYS_REMAINING || $v1Usage >= self::CRITICAL_V1_USAGE) {
            return 'CRITICAL';
        }
        
        if ($daysRemaining <= self::WARNING_DAYS_REMAINING || $v1Usage >= self::WARNING_V1_USAGE) {
            return 'WARNING';
        }
        
        return 'OK';
    }
    
    /**
     * Display current status
     */
    private function displayStatus(array $metrics, int $daysRemaining, string $alertLevel): void
    {
        $this->newLine();
        $this->info('Current Status:');
        $this->line('---------------');
        
        // Alert level with color
        $alertColor = match($alertLevel) {
            'EXPIRED' => 'error',
            'CRITICAL' => 'error',
            'WARNING' => 'warn',
            default => 'info'
        };
        
        $this->line("Alert Level: <$alertColor>$alertLevel</$alertColor>");
        
        // Days remaining
        if ($daysRemaining > 0) {
            $daysColor = $daysRemaining <= self::CRITICAL_DAYS_REMAINING ? 'error' : 
                        ($daysRemaining <= self::WARNING_DAYS_REMAINING ? 'warn' : 'info');
            $this->line("Days Until V1 Sunset: <$daysColor>$daysRemaining days</$daysColor>");
        } else {
            $this->error("⚠️  V1 API HAS BEEN SUNSET!");
        }
        
        // API usage
        $this->newLine();
        $this->info('API Usage Statistics:');
        $this->line('--------------------');
        $this->line("Total API Calls: {$metrics['total_calls']}");
        
        $v1Color = $metrics['v1_percentage'] >= self::CRITICAL_V1_USAGE ? 'error' :
                   ($metrics['v1_percentage'] >= self::WARNING_V1_USAGE ? 'warn' : 'info');
        $this->line("V1 Calls: {$metrics['v1_calls']} (<$v1Color>{$metrics['v1_percentage']}%</$v1Color>)");
        
        $this->line("V2 Calls: {$metrics['v2_calls']} ({$metrics['v2_percentage']}%)");
        
        if ($metrics['errors'] > 0) {
            $this->warn("Errors Encountered: {$metrics['errors']}");
        }
        
        // Recommendations
        $this->newLine();
        $this->info('Recommendations:');
        $this->line('----------------');
        
        if ($alertLevel === 'EXPIRED') {
            $this->error('🚨 IMMEDIATE ACTION REQUIRED: V1 API is no longer available!');
            $this->error('   Switch to V2 immediately or service will fail.');
        } elseif ($alertLevel === 'CRITICAL') {
            $this->error('⚠️  URGENT: Begin immediate migration to V2 API');
            $this->warn('   - Review V2 documentation');
            $this->warn('   - Test V2 endpoints thoroughly');
            $this->warn('   - Consider platform subscription if needed');
        } elseif ($alertLevel === 'WARNING') {
            $this->warn('📋 Schedule V2 migration planning');
            $this->line('   - Assess platform subscription requirements');
            $this->line('   - Plan migration timeline');
            $this->line('   - Identify V1-dependent features');
        } else {
            $this->info('✅ V1 usage is within acceptable limits');
            $this->line('   Continue monitoring and gradual migration');
        }
    }
    
    /**
     * Generate detailed report
     */
    private function generateDetailedReport(array $metrics, int $daysRemaining): void
    {
        $this->newLine(2);
        $this->info('Detailed Migration Report:');
        $this->line('=========================');
        
        // V1 Operations still in use
        $this->newLine();
        $this->info('V1 Operations Currently in Use:');
        $this->line('- Event Type Management');
        $this->line('- Availability Checking');
        $this->line('- Legacy Booking Format Support');
        
        // V2 Operations active
        $this->newLine();
        $this->info('V2 Operations Successfully Migrated:');
        $this->line('- Booking Creation');
        $this->line('- Booking Cancellation');
        $this->line('- Booking Rescheduling');
        $this->line('- Booking Retrieval');
        
        // Migration tasks
        $this->newLine();
        $this->info('Remaining Migration Tasks:');
        
        $tasks = [
            'Evaluate platform subscription ($299/month) for full V2 access',
            'Migrate event type management to V2 (requires platform)',
            'Update availability checking to V2 slots endpoint',
            'Remove V1 fallback logic after full migration',
            'Update all documentation to V2 standards'
        ];
        
        foreach ($tasks as $i => $task) {
            $this->line(sprintf('  %d. %s', $i + 1, $task));
        }
        
        // Timeline
        $this->newLine();
        $this->info('Migration Timeline:');
        $this->table(
            ['Milestone', 'Target Date', 'Status'],
            [
                ['Hybrid implementation', '2025-09-11', '✅ Complete'],
                ['V2 booking operations', '2025-09-15', '✅ Complete'],
                ['Platform subscription decision', '2025-10-01', '⏳ Pending'],
                ['Full V2 migration', '2025-11-30', '⏳ Pending'],
                ['V1 sunset', '2025-12-31', $daysRemaining > 0 ? "📅 $daysRemaining days" : '⚠️ EXPIRED']
            ]
        );
        
        // Risk assessment
        $this->newLine();
        $this->info('Risk Assessment:');
        
        $riskLevel = $daysRemaining <= 30 ? 'HIGH' : 
                    ($daysRemaining <= 90 ? 'MEDIUM' : 'LOW');
        
        $this->line("Migration Risk Level: <" . 
                   ($riskLevel === 'HIGH' ? 'error' : 
                   ($riskLevel === 'MEDIUM' ? 'warn' : 'info')) . 
                   ">$riskLevel</>");
        
        if ($metrics['v1_percentage'] > 50) {
            $this->warn("⚠️  High V1 dependency detected ({$metrics['v1_percentage']}% of calls)");
        }
    }
    
    /**
     * Send alert emails
     */
    private function sendAlerts(array $metrics, int $daysRemaining, string $alertLevel): void
    {
        $this->newLine();
        $this->info('Sending alerts...');
        
        // In production, you would send actual emails
        // For now, we'll just log the alert
        
        $alertData = [
            'level' => $alertLevel,
            'days_remaining' => $daysRemaining,
            'v1_usage' => $metrics['v1_percentage'],
            'v2_usage' => $metrics['v2_percentage'],
            'total_calls' => $metrics['total_calls']
        ];
        
        Log::channel('calcom')->warning('Cal.com V1 Deprecation Alert', $alertData);
        
        $this->info('Alert logged to system');
        
        // Uncomment to send actual email alerts
        /*
        Mail::raw(
            "Cal.com V1 API Deprecation Alert\n\n" .
            "Alert Level: $alertLevel\n" .
            "Days Remaining: $daysRemaining\n" .
            "V1 Usage: {$metrics['v1_percentage']}%\n" .
            "Action Required: Migrate to V2 API",
            function ($message) use ($alertLevel) {
                $message->to(config('mail.admin_email'))
                        ->subject("[$alertLevel] Cal.com V1 API Deprecation Warning");
            }
        );
        */
    }
    
    /**
     * Log metrics for historical tracking
     */
    private function logMetrics(array $metrics, int $daysRemaining, string $alertLevel): void
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'alert_level' => $alertLevel,
            'days_remaining' => $daysRemaining,
            'metrics' => $metrics
        ];
        
        // Log to file for tracking
        Log::channel('calcom')->info('Deprecation Monitor Run', $logData);
        
        // Store in database for trending (if you have a metrics table)
        // MetricHistory::create($logData);
    }
}