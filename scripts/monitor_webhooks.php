#!/usr/bin/env php
<?php
/**
 * Webhook Monitoring Script
 * Monitors for failed webhook deliveries and alerts administrators
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class WebhookMonitor
{
    private $alertThreshold = 3; // Number of failures before alert
    private $timeWindow = 10; // Minutes to check for failures

    public function run()
    {
        echo "=== Webhook Monitoring Started ===\n";
        echo "Time: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";

        // Check for recent webhook failures in logs
        $this->checkRecentFailures();

        // Check for missing calls (calls in Retell but not in DB)
        $this->checkMissingCalls();

        // Check webhook endpoint health
        $this->checkWebhookHealth();

        echo "\n=== Monitoring Complete ===\n";
    }

    private function checkRecentFailures()
    {
        echo "Checking for recent webhook failures...\n";

        $logFile = storage_path('logs/laravel.log');
        $since = Carbon::now()->subMinutes($this->timeWindow);

        // Read recent log entries
        $logs = $this->getRecentLogs($logFile, $since);

        // Count webhook failures
        $retellFailures = 0;
        $calcomFailures = 0;

        foreach ($logs as $line) {
            if (strpos($line, 'retell.signature') !== false && strpos($line, 'ERROR') !== false) {
                $retellFailures++;
            }
            if (strpos($line, 'webhooks/retell') !== false && strpos($line, '500') !== false) {
                $retellFailures++;
            }
            if (strpos($line, 'calcom.signature') !== false && strpos($line, 'ERROR') !== false) {
                $calcomFailures++;
            }
        }

        if ($retellFailures >= $this->alertThreshold) {
            $this->sendAlert("⚠️ Retell Webhook Failures",
                "Detected $retellFailures Retell webhook failures in the last $this->timeWindow minutes");
            echo "  ❌ Found $retellFailures Retell webhook failures - ALERT SENT\n";
        } else {
            echo "  ✅ Retell webhooks: $retellFailures failures (threshold: $this->alertThreshold)\n";
        }

        if ($calcomFailures >= $this->alertThreshold) {
            $this->sendAlert("⚠️ Cal.com Webhook Failures",
                "Detected $calcomFailures Cal.com webhook failures in the last $this->timeWindow minutes");
            echo "  ❌ Found $calcomFailures Cal.com webhook failures - ALERT SENT\n";
        } else {
            echo "  ✅ Cal.com webhooks: $calcomFailures failures (threshold: $this->alertThreshold)\n";
        }
    }

    private function checkMissingCalls()
    {
        echo "\nChecking for missing calls...\n";

        try {
            $client = new \App\Services\RetellApiClient();

            // Get recent calls from Retell
            $recentCalls = $client->getAllCalls([
                'start_timestamp' => Carbon::now()->subHours(1)->timestamp * 1000,
                'end_timestamp' => Carbon::now()->timestamp * 1000,
                'limit' => 20
            ]);

            $missingCalls = [];

            foreach ($recentCalls as $call) {
                $exists = DB::table('calls')
                    ->where('retell_call_id', $call['call_id'])
                    ->exists();

                if (!$exists) {
                    $missingCalls[] = $call['call_id'];
                }
            }

            if (count($missingCalls) > 0) {
                echo "  ⚠️ Found " . count($missingCalls) . " missing calls:\n";
                foreach ($missingCalls as $callId) {
                    echo "    - $callId\n";

                    // Auto-import missing calls
                    try {
                        $callData = $client->getCallDetail($callId);
                        if ($callData) {
                            $client->syncCallToDatabase($callData);
                            echo "      ✅ Auto-imported\n";
                        }
                    } catch (\Exception $e) {
                        echo "      ❌ Import failed: " . $e->getMessage() . "\n";
                    }
                }
            } else {
                echo "  ✅ No missing calls detected\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Error checking for missing calls: " . $e->getMessage() . "\n";
        }
    }

    private function checkWebhookHealth()
    {
        echo "\nChecking webhook endpoint health...\n";

        $endpoints = [
            'Retell' => 'https://api.askproai.de/api/webhooks/retell/diagnostic',
            'Cal.com' => 'https://api.askproai.de/api/calcom'
        ];

        foreach ($endpoints as $name => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                echo "  ✅ $name webhook endpoint: Healthy (HTTP $httpCode)\n";
            } else {
                echo "  ❌ $name webhook endpoint: Unhealthy (HTTP $httpCode)\n";
                $this->sendAlert("⚠️ $name Webhook Endpoint Down",
                    "$name webhook endpoint returned HTTP $httpCode");
            }
        }
    }

    private function getRecentLogs($file, Carbon $since)
    {
        if (!file_exists($file)) {
            return [];
        }

        $lines = [];
        $handle = fopen($file, 'r');

        if (!$handle) {
            return [];
        }

        // Read file backwards for efficiency
        $buffer = '';
        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);

        while ($position > 0) {
            $chunkSize = min(8192, $position);
            $position -= $chunkSize;
            fseek($handle, $position);
            $chunk = fread($handle, $chunkSize);
            $buffer = $chunk . $buffer;

            // Process complete lines
            $newlines = explode("\n", $buffer);
            $buffer = array_shift($newlines);

            foreach (array_reverse($newlines) as $line) {
                if ($this->isRecentLogEntry($line, $since)) {
                    $lines[] = $line;
                } elseif ($this->isOlderLogEntry($line, $since)) {
                    // Stop reading if we've gone too far back
                    fclose($handle);
                    return $lines;
                }
            }
        }

        fclose($handle);
        return $lines;
    }

    private function isRecentLogEntry($line, Carbon $since)
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $timestamp = Carbon::parse($matches[1]);
            return $timestamp->isAfter($since);
        }
        return false;
    }

    private function isOlderLogEntry($line, Carbon $since)
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $timestamp = Carbon::parse($matches[1]);
            return $timestamp->isBefore($since);
        }
        return false;
    }

    private function sendAlert($subject, $message)
    {
        // Log the alert
        Log::error("WEBHOOK MONITOR ALERT: $subject - $message");

        // Store alert in database
        DB::table('system_alerts')->insert([
            'type' => 'webhook_failure',
            'severity' => 'high',
            'subject' => $subject,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // TODO: Send email/SMS notification to admins
        // Mail::to(config('monitoring.admin_email'))->send(new WebhookAlert($subject, $message));
    }
}

// Run the monitor
$monitor = new WebhookMonitor();
$monitor->run();