<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\WebhookEvent;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorRetellHealth extends Command
{
    protected $signature = 'retell:monitor
                            {--continuous : Run continuously with updates every minute}
                            {--alert : Send alerts for critical issues}
                            {--detailed : Show detailed information}';

    protected $description = 'Monitor Retell integration health and performance';

    private $metrics = [];
    private $issues = [];
    private $warnings = [];

    public function handle()
    {
        if ($this->option('continuous')) {
            $this->runContinuousMonitoring();
        } else {
            $this->runSingleCheck();
        }
    }

    private function runContinuousMonitoring()
    {
        $this->info('🔍 Starting continuous monitoring (Press Ctrl+C to stop)');

        while (true) {
            $this->clearConsole();
            $this->displayHeader();
            $this->runAllChecks();
            $this->displayResults();

            if ($this->option('alert') && count($this->issues) > 0) {
                $this->sendAlerts();
            }

            sleep(60); // Update every minute
        }
    }

    private function runSingleCheck()
    {
        $this->displayHeader();
        $this->runAllChecks();
        $this->displayResults();

        if ($this->option('alert') && count($this->issues) > 0) {
            $this->sendAlerts();
        }

        // Return exit code based on health
        return count($this->issues) > 0 ? 1 : 0;
    }

    private function runAllChecks()
    {
        $this->metrics = [];
        $this->issues = [];
        $this->warnings = [];

        // 1. Check webhook processing
        $this->checkWebhookProcessing();

        // 2. Check call imports
        $this->checkCallImports();

        // 3. Check appointment bookings
        $this->checkAppointmentBookings();

        // 4. Check Cal.com integration
        $this->checkCalcomIntegration();

        // 5. Check database health
        $this->checkDatabaseHealth();

        // 6. Check auto-import status
        $this->checkAutoImportStatus();

        // 7. Check timezone consistency
        $this->checkTimezoneConsistency();

        // 8. Check error rates
        $this->checkErrorRates();

        // 9. Performance metrics
        $this->checkPerformanceMetrics();

        // 10. Check for stuck calls
        $this->checkStuckCalls();
    }

    private function checkWebhookProcessing()
    {
        $lastWebhook = WebhookEvent::where('provider', 'retell')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastWebhook) {
            $this->warnings[] = 'No webhooks received yet';
            $this->metrics['last_webhook'] = 'Never';
            return;
        }

        $minutesAgo = Carbon::now()->diffInMinutes($lastWebhook->created_at);
        $this->metrics['last_webhook'] = $minutesAgo . ' minutes ago';

        if ($minutesAgo > 60) {
            $this->issues[] = 'No webhooks received in last hour';
        } elseif ($minutesAgo > 30) {
            $this->warnings[] = 'No webhooks received in last 30 minutes';
        }

        // Check failed webhooks
        $failedCount = WebhookEvent::where('provider', 'retell')
            ->where('status', 'failed')
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        $this->metrics['failed_webhooks_1h'] = $failedCount;

        if ($failedCount > 5) {
            $this->issues[] = "$failedCount failed webhooks in last hour";
        } elseif ($failedCount > 2) {
            $this->warnings[] = "$failedCount failed webhooks in last hour";
        }
    }

    private function checkCallImports()
    {
        // Recent calls
        $recentCalls = Call::where('created_at', '>', Carbon::now()->subHour())->count();
        $this->metrics['calls_last_hour'] = $recentCalls;

        // Calls today
        $callsToday = Call::whereDate('created_at', Carbon::today())->count();
        $this->metrics['calls_today'] = $callsToday;

        // Check for missing timestamps
        $missingTimestamps = Call::whereNull('start_timestamp')
            ->where('created_at', '>', Carbon::now()->subDay())
            ->count();

        if ($missingTimestamps > 0) {
            $this->warnings[] = "$missingTimestamps calls missing timestamps";
        }

        // Check timezone correctness
        $utcCalls = DB::select("
            SELECT COUNT(*) as count
            FROM calls
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            AND HOUR(created_at) != HOUR(CONVERT_TZ(created_at, 'UTC', 'Europe/Berlin'))
        ");

        if ($utcCalls[0]->count > 0) {
            $this->issues[] = "{$utcCalls[0]->count} calls with incorrect timezone";
        }
    }

    private function checkAppointmentBookings()
    {
        // Appointments today
        $appointmentsToday = Appointment::whereDate('scheduled_at', Carbon::today())->count();
        $this->metrics['appointments_today'] = $appointmentsToday;

        // Appointments tomorrow
        $appointmentsTomorrow = Appointment::whereDate('scheduled_at', Carbon::tomorrow())->count();
        $this->metrics['appointments_tomorrow'] = $appointmentsTomorrow;

        // Failed appointments
        $failedAppointments = Appointment::where('status', 'failed')
            ->where('created_at', '>', Carbon::now()->subDay())
            ->count();

        $this->metrics['failed_appointments_24h'] = $failedAppointments;

        if ($failedAppointments > 3) {
            $this->issues[] = "$failedAppointments failed appointments in last 24h";
        } elseif ($failedAppointments > 0) {
            $this->warnings[] = "$failedAppointments failed appointments in last 24h";
        }

        // Check for appointments without Cal.com ID
        $unsynced = Appointment::whereNull('calcom_booking_id')
            ->where('status', 'confirmed')
            ->where('created_at', '>', Carbon::now()->subDay())
            ->count();

        if ($unsynced > 0) {
            $this->warnings[] = "$unsynced appointments not synced with Cal.com";
        }
    }

    private function checkCalcomIntegration()
    {
        try {
            $response = Http::timeout(5)->get(config('app.url') . '/api/health/calcom');

            if ($response->successful()) {
                $data = $response->json();
                $this->metrics['calcom_status'] = $data['status'] ?? 'unknown';

                if ($data['status'] !== 'healthy' && $data['status'] !== 'ok') {
                    $this->issues[] = 'Cal.com integration unhealthy';
                }
            } else {
                $this->issues[] = 'Cal.com health check failed';
                $this->metrics['calcom_status'] = 'error';
            }
        } catch (\Exception $e) {
            $this->issues[] = 'Cal.com health check timeout';
            $this->metrics['calcom_status'] = 'timeout';
        }
    }

    private function checkDatabaseHealth()
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $queryTime = (microtime(true) - $start) * 1000;

            $this->metrics['db_response_ms'] = round($queryTime, 2);

            if ($queryTime > 100) {
                $this->warnings[] = "Slow database response: {$queryTime}ms";
            }

            // Check table sizes
            $result = DB::select("
                SELECT
                    TABLE_NAME,
                    ROUND(DATA_LENGTH / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = 'api_gateway'
                    AND TABLE_NAME IN ('calls', 'appointments', 'webhook_events')
                ORDER BY DATA_LENGTH DESC
            ");

            foreach ($result as $table) {
                $this->metrics["table_{$table->TABLE_NAME}_mb"] = $table->size_mb;

                if ($table->size_mb > 1000) {
                    $this->warnings[] = "Table {$table->TABLE_NAME} is large: {$table->size_mb}MB";
                }
            }

        } catch (\Exception $e) {
            $this->issues[] = 'Database connection failed';
            $this->metrics['db_response_ms'] = 'error';
        }
    }

    private function checkAutoImportStatus()
    {
        $logFile = '/var/www/api-gateway/storage/logs/auto_import.log';

        if (file_exists($logFile)) {
            $lastModified = filemtime($logFile);
            $minutesAgo = (time() - $lastModified) / 60;

            $this->metrics['auto_import_last_run'] = round($minutesAgo) . ' minutes ago';

            if ($minutesAgo > 10) {
                $this->issues[] = 'Auto-import not running (last: ' . round($minutesAgo) . ' min ago)';
            } elseif ($minutesAgo > 7) {
                $this->warnings[] = 'Auto-import may be delayed';
            }

            // Check for errors in log
            $recentLog = shell_exec("tail -20 $logFile 2>/dev/null | grep -c ERROR");
            if ($recentLog > 0) {
                $this->warnings[] = "$recentLog errors in auto-import log";
            }
        } else {
            $this->warnings[] = 'Auto-import log not found';
            $this->metrics['auto_import_last_run'] = 'unknown';
        }
    }

    private function checkTimezoneConsistency()
    {
        // Get a recent call and check its timezone
        $recentCall = Call::orderBy('created_at', 'desc')->first();

        if ($recentCall && $recentCall->start_timestamp) {
            $timestamp = Carbon::createFromTimestampMs($recentCall->start_timestamp);
            $berlinTime = $timestamp->setTimezone('Europe/Berlin');
            $storedTime = Carbon::parse($recentCall->created_at);

            $hourDiff = abs($berlinTime->hour - $storedTime->hour);

            if ($hourDiff > 0 && $hourDiff != 12) {
                $this->warnings[] = "Timezone inconsistency detected ({$hourDiff}h difference)";
            }

            $this->metrics['timezone_check'] = 'Berlin (UTC+' . $berlinTime->offsetHours . ')';
        }
    }

    private function checkErrorRates()
    {
        // Check Laravel error log
        $errorLog = '/var/www/api-gateway/storage/logs/laravel.log';

        if (file_exists($errorLog)) {
            $recentErrors = shell_exec("tail -1000 $errorLog 2>/dev/null | grep -c 'ERROR\\|CRITICAL'");
            $this->metrics['errors_in_log'] = intval($recentErrors);

            if ($recentErrors > 50) {
                $this->issues[] = "$recentErrors errors in Laravel log";
            } elseif ($recentErrors > 20) {
                $this->warnings[] = "$recentErrors errors in Laravel log";
            }
        }
    }

    private function checkPerformanceMetrics()
    {
        // Check API response time
        try {
            $start = microtime(true);
            Http::timeout(5)->get(config('app.url') . '/api/health');
            $responseTime = (microtime(true) - $start) * 1000;

            $this->metrics['api_response_ms'] = round($responseTime, 2);

            if ($responseTime > 500) {
                $this->warnings[] = "Slow API response: {$responseTime}ms";
            }
        } catch (\Exception $e) {
            $this->issues[] = 'API health check failed';
            $this->metrics['api_response_ms'] = 'error';
        }
    }

    private function checkStuckCalls()
    {
        // Check for calls stuck in 'in_progress'
        $stuckCalls = Call::where('status', 'in_progress')
            ->where('created_at', '<', Carbon::now()->subHours(2))
            ->count();

        if ($stuckCalls > 0) {
            $this->warnings[] = "$stuckCalls calls stuck in progress";
            $this->metrics['stuck_calls'] = $stuckCalls;
        }
    }

    private function displayHeader()
    {
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║            RETELL INTEGRATION HEALTH MONITOR            ║');
        $this->info('║                                                          ║');
        $this->info('║  Time: ' . str_pad(Carbon::now()->format('Y-m-d H:i:s'), 47) . '║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    private function displayResults()
    {
        // Display metrics
        $this->info('📊 METRICS:');
        $this->table(
            ['Metric', 'Value'],
            collect($this->metrics)->map(function ($value, $key) {
                return [str_replace('_', ' ', ucfirst($key)), $value];
            })->toArray()
        );

        // Display issues
        if (count($this->issues) > 0) {
            $this->error('');
            $this->error('🚨 CRITICAL ISSUES:');
            foreach ($this->issues as $issue) {
                $this->error('  ❌ ' . $issue);
            }
        }

        // Display warnings
        if (count($this->warnings) > 0) {
            $this->warn('');
            $this->warn('⚠️ WARNINGS:');
            foreach ($this->warnings as $warning) {
                $this->warn('  ⚡ ' . $warning);
            }
        }

        // Overall status
        $this->info('');
        if (count($this->issues) > 0) {
            $this->error('OVERALL STATUS: ❌ CRITICAL - Immediate attention required');
        } elseif (count($this->warnings) > 0) {
            $this->warn('OVERALL STATUS: ⚠️ WARNING - Monitor closely');
        } else {
            $this->info('OVERALL STATUS: ✅ HEALTHY - All systems operational');
        }
    }

    private function sendAlerts()
    {
        // Log critical issues
        foreach ($this->issues as $issue) {
            Log::critical('Retell Monitor Alert: ' . $issue);
        }

        // You could add email/Slack notifications here
        if ($this->option('detailed')) {
            $this->info('');
            $this->info('📧 Alerts sent for ' . count($this->issues) . ' critical issues');
        }
    }

    private function clearConsole()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
}