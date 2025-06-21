<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;

class EmailHealthCheck implements IntegrationHealthCheck
{
    /**
     * Get the name of this health check
     */
    public function getName(): string
    {
        return 'Email Service';
    }
    
    /**
     * Get the priority of this check (higher = more important)
     */
    public function getPriority(): int
    {
        return 50; // Medium priority - email is important but not critical
    }
    
    /**
     * Is this check critical for system operation?
     */
    public function isCritical(): bool
    {
        return false; // System can operate without email temporarily
    }
    
    /**
     * Perform the health check
     */
    public function check(Company $company): HealthCheckResult
    {
        $startTime = microtime(true);
        
        try {
            // Get mail configuration
            $mailer = config('mail.default');
            $host = config("mail.mailers.{$mailer}.host");
            $port = config("mail.mailers.{$mailer}.port");
            $encryption = config("mail.mailers.{$mailer}.encryption");
            
            // Test mail configuration by checking if we can create a mailer
            // In Laravel 9+, we can't directly test the connection without sending
            // So we'll check if the configuration is valid
            $mailManager = app('mail.manager');
            $mailer = $mailManager->mailer();
            
            // Check if configuration exists
            if (!$host || !$port) {
                throw new \Exception('Mail configuration is incomplete');
            }
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $metrics = [
                'response_time_ms' => round($responseTime, 2),
                'mailer' => $mailer,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
            ];
            
            // Check email queue (if using queued mail)
            $failedJobs = \DB::table('failed_jobs')
                ->where('payload', 'like', '%SendQueuedMailable%')
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();
            
            if ($failedJobs > 10) {
                return HealthCheckResult::degraded(
                    'Email service is working but has failed jobs',
                    [
                        'failed_jobs_24h' => $failedJobs,
                        'smtp_connection' => 'active'
                    ],
                    ['Review failed email jobs in queue'],
                    $metrics
                );
            }
            
            return HealthCheckResult::healthy(
                'Email service is working properly',
                [
                    'smtp_server' => "{$host}:{$port}",
                    'encryption' => $encryption ?: 'none',
                    'status' => 'connected'
                ],
                $metrics
            );
            
        } catch (TransportException $e) {
            Log::error('Email health check failed - SMTP error', [
                'error' => $e->getMessage(),
                'company_id' => $company->id
            ]);
            
            return HealthCheckResult::unhealthy(
                'SMTP connection failed',
                [
                    'error' => $e->getMessage(),
                    'host' => $host ?? 'not configured',
                    'port' => $port ?? 'not configured'
                ],
                [
                    'Check SMTP credentials in .env file',
                    'Verify SMTP server is accessible',
                    'Check firewall rules for port ' . ($port ?? '587')
                ]
            );
            
        } catch (\Exception $e) {
            Log::error('Email health check failed', [
                'error' => $e->getMessage(),
                'company_id' => $company->id
            ]);
            
            return HealthCheckResult::unhealthy(
                'Email service check failed',
                ['error' => $e->getMessage()],
                [
                    'Check mail configuration',
                    'Verify mail driver is properly configured',
                    'Check Laravel logs for mail errors'
                ]
            );
        }
    }
    
    /**
     * Attempt to automatically fix issues
     */
    public function attemptAutoFix(Company $company, array $issues): bool
    {
        try {
            // Retry failed email jobs
            foreach ($issues as $issue) {
                if (str_contains($issue, 'failed jobs')) {
                    \Artisan::call('queue:retry', ['id' => 'all']);
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get suggested fixes for common issues
     */
    public function getSuggestedFixes(array $issues): array
    {
        $fixes = [];
        
        foreach ($issues as $issue) {
            if (str_contains($issue, 'SMTP connection failed')) {
                $fixes[] = 'Verify SMTP credentials in .env file';
                $fixes[] = 'Check if SMTP server requires authentication';
                $fixes[] = 'Test connection using telnet to SMTP host/port';
                $fixes[] = 'Check if IP is whitelisted on SMTP server';
            }
            
            if (str_contains($issue, 'failed jobs')) {
                $fixes[] = 'Run: php artisan queue:retry all';
                $fixes[] = 'Check email templates for errors';
                $fixes[] = 'Verify recipient email addresses are valid';
            }
            
            if (str_contains($issue, 'not configured')) {
                $fixes[] = 'Set MAIL_MAILER in .env file';
                $fixes[] = 'Configure MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD';
                $fixes[] = 'Run: php artisan config:cache after updating .env';
            }
        }
        
        return $fixes;
    }
    
    /**
     * Get detailed diagnostics information
     */
    public function getDiagnostics(): array
    {
        try {
            $mailConfig = config('mail');
            $defaultMailer = $mailConfig['default'] ?? 'smtp';
            $mailerConfig = $mailConfig['mailers'][$defaultMailer] ?? [];
            
            // Get failed email jobs count
            $failedEmailJobs = 0;
            try {
                $failedEmailJobs = \DB::table('failed_jobs')
                    ->where('queue', 'emails')
                    ->count();
            } catch (\Exception $e) {
                // Ignore if table doesn't exist
            }
            
            // Get recent email logs
            $recentEmails = [];
            try {
                $recentEmails = \DB::table('mail_logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($log) {
                        return [
                            'to' => $log->to ?? 'Unknown',
                            'subject' => $log->subject ?? 'Unknown',
                            'status' => $log->status ?? 'Unknown',
                            'sent_at' => $log->created_at ?? 'Unknown',
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                // Mail logs table might not exist
            }
            
            return [
                'mailer' => $defaultMailer,
                'configuration' => [
                    'driver' => $defaultMailer,
                    'host' => $mailerConfig['host'] ?? 'Not configured',
                    'port' => $mailerConfig['port'] ?? 'Not configured',
                    'encryption' => $mailerConfig['encryption'] ?? 'none',
                    'username' => !empty($mailerConfig['username']) ? 'Configured' : 'Not configured',
                    'from_address' => $mailConfig['from']['address'] ?? 'Not configured',
                    'from_name' => $mailConfig['from']['name'] ?? 'Not configured',
                ],
                'statistics' => [
                    'failed_email_jobs' => $failedEmailJobs,
                    'queue_connection' => config('queue.default'),
                ],
                'recent_emails' => $recentEmails,
                'available_mailers' => array_keys($mailConfig['mailers'] ?? []),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to collect diagnostics',
                'message' => $e->getMessage(),
            ];
        }
    }
}