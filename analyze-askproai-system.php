#!/usr/bin/env php
<?php

/**
 * AskProAI System Analysis Script
 * 
 * This script uses the MCP (Model Context Protocol) endpoints to perform
 * a comprehensive analysis of the AskProAI system health, integrations,
 * and performance metrics.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class AskProAISystemAnalyzer
{
    private $apiUrl;
    private $authToken;
    private $companyId;
    private $report = [];
    private $criticalIssues = [];
    private $warnings = [];
    private $recommendations = [];
    
    public function __construct()
    {
        $this->apiUrl = config('app.url') . '/api/mcp';
        
        // Get the first company for analysis (or specify a company ID)
        $company = \App\Models\Company::first();
        if (!$company) {
            throw new Exception("No company found in the database. Please ensure the system is properly set up.");
        }
        
        $this->companyId = $company->id;
        
        // Create an API token for the analysis
        $user = \App\Models\User::where('company_id', $company->id)->first();
        if (!$user) {
            // Create a system user for analysis
            $user = \App\Models\User::create([
                'name' => 'System Analyzer',
                'email' => 'analyzer@askproai.local',
                'password' => bcrypt(Str::random(32)),
                'company_id' => $company->id,
            ]);
        }
        
        $this->authToken = $user->createToken('system-analysis')->plainTextToken;
        
        echo "🔍 AskProAI System Analysis\n";
        echo "Company: {$company->name} (ID: {$company->id})\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }
    
    /**
     * Run the complete system analysis
     */
    public function analyze()
    {
        $startTime = microtime(true);
        
        try {
            // 1. Check Queue Health
            $this->analyzeQueueHealth();
            
            // 2. Check Database Health
            $this->analyzeDatabaseHealth();
            
            // 3. Check Retell.ai Integration
            $this->analyzeRetellIntegration();
            
            // 4. Check Cal.com Integration
            $this->analyzeCalcomIntegration();
            
            // 5. Check Recent Errors (if Sentry is configured)
            $this->analyzeSentryErrors();
            
            // 6. Generate Performance Metrics
            $this->analyzePerformanceMetrics();
            
            // 7. Compile and Display Report
            $this->generateReport($startTime);
            
        } catch (Exception $e) {
            echo "❌ Analysis failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    /**
     * Analyze Queue Health
     */
    private function analyzeQueueHealth()
    {
        echo "📊 Analyzing Queue Health...\n";
        
        try {
            // Get queue overview
            $overview = $this->callMCP('queue/overview');
            
            if ($overview) {
                $this->report['queue'] = [
                    'status' => $overview['horizon_status'] ?? 'unknown',
                    'failed_jobs' => $overview['failed_jobs'] ?? 0,
                    'queues' => $overview['queues'] ?? [],
                    'workers' => $overview['workers'] ?? [],
                    'throughput' => $overview['throughput'] ?? []
                ];
                
                // Check for critical issues
                if ($overview['horizon_status'] !== 'running') {
                    $this->criticalIssues[] = "Horizon queue processor is not running!";
                }
                
                if ($overview['failed_jobs'] > 100) {
                    $this->criticalIssues[] = "High number of failed jobs: {$overview['failed_jobs']}";
                } elseif ($overview['failed_jobs'] > 50) {
                    $this->warnings[] = "Elevated failed job count: {$overview['failed_jobs']}";
                }
                
                // Check queue sizes
                foreach ($overview['queues'] as $queue => $data) {
                    if ($data['size'] > 1000) {
                        $this->criticalIssues[] = "Queue '{$queue}' has {$data['size']} pending jobs!";
                    } elseif ($data['size'] > 500) {
                        $this->warnings[] = "Queue '{$queue}' backlog: {$data['size']} jobs";
                    }
                }
            }
            
            // Get failed jobs details
            $failedJobs = $this->callMCP('queue/failed-jobs', ['limit' => 10]);
            if ($failedJobs && isset($failedJobs['jobs'])) {
                $this->report['recent_failed_jobs'] = array_slice($failedJobs['jobs'], 0, 5);
                
                // Analyze failure patterns
                $failureTypes = [];
                foreach ($failedJobs['jobs'] as $job) {
                    $jobType = $job['name'] ?? 'Unknown';
                    $failureTypes[$jobType] = ($failureTypes[$jobType] ?? 0) + 1;
                }
                $this->report['failure_patterns'] = $failureTypes;
            }
            
            echo "✓ Queue analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze queue health: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Queue health check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Analyze Database Health
     */
    private function analyzeDatabaseHealth()
    {
        echo "🗄️ Analyzing Database Health...\n";
        
        try {
            // Get failed appointments from last 24 hours
            $failedAppointments = $this->callMCP('database/failed-appointments', ['hours' => 24, 'limit' => 100]);
            
            if ($failedAppointments && isset($failedAppointments['data'])) {
                $failedCount = count($failedAppointments['data']);
                $this->report['failed_appointments_24h'] = $failedCount;
                
                if ($failedCount > 10) {
                    $this->criticalIssues[] = "High appointment failure rate: {$failedCount} failures in last 24 hours";
                } elseif ($failedCount > 5) {
                    $this->warnings[] = "Elevated appointment failures: {$failedCount} in last 24 hours";
                }
                
                // Analyze failure reasons
                $failureReasons = [];
                foreach ($failedAppointments['data'] as $appointment) {
                    $reason = $appointment->error_message ?? 'Unknown';
                    $failureReasons[$reason] = ($failureReasons[$reason] ?? 0) + 1;
                }
                $this->report['appointment_failure_reasons'] = $failureReasons;
            }
            
            // Get call statistics
            $callStats = $this->callMCP('database/call-stats', ['days' => 7]);
            
            if ($callStats && isset($callStats['data'])) {
                $this->report['call_stats_7d'] = $callStats['data'];
                
                // Calculate averages
                $totalCalls = 0;
                $completedCalls = 0;
                $failedCalls = 0;
                $totalCost = 0;
                
                foreach ($callStats['data'] as $day) {
                    $totalCalls += $day->total_calls ?? 0;
                    $completedCalls += $day->completed_calls ?? 0;
                    $failedCalls += $day->failed_calls ?? 0;
                    $totalCost += $day->total_cost ?? 0;
                }
                
                $successRate = $totalCalls > 0 ? ($completedCalls / $totalCalls) * 100 : 0;
                
                $this->report['call_summary'] = [
                    'total_calls' => $totalCalls,
                    'completed_calls' => $completedCalls,
                    'failed_calls' => $failedCalls,
                    'success_rate' => round($successRate, 2) . '%',
                    'total_cost' => '$' . number_format($totalCost, 2),
                    'avg_cost_per_call' => $totalCalls > 0 ? '$' . number_format($totalCost / $totalCalls, 2) : '$0.00'
                ];
                
                if ($successRate < 80) {
                    $this->warnings[] = "Low call success rate: {$successRate}%";
                }
                
                if ($failedCalls > $completedCalls * 0.2) {
                    $this->warnings[] = "High call failure rate: {$failedCalls} failed out of {$totalCalls} total";
                }
            }
            
            // Get tenant statistics
            $tenantStats = $this->callMCP('database/tenant-stats', ['company_id' => $this->companyId]);
            
            if ($tenantStats && isset($tenantStats['data'])) {
                $stats = $tenantStats['data'][0] ?? null;
                if ($stats) {
                    $this->report['company_stats'] = [
                        'branches' => $stats->branch_count ?? 0,
                        'customers' => $stats->customer_count ?? 0,
                        'appointments' => $stats->appointment_count ?? 0,
                        'calls' => $stats->call_count ?? 0
                    ];
                }
            }
            
            echo "✓ Database analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze database health: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Database health check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Analyze Retell.ai Integration
     */
    private function analyzeRetellIntegration()
    {
        echo "📞 Analyzing Retell.ai Integration...\n";
        
        try {
            // Test connection
            $connectionTest = $this->callMCP("retell/test/{$this->companyId}");
            
            if ($connectionTest) {
                $this->report['retell_connection'] = [
                    'connected' => $connectionTest['connected'] ?? false,
                    'agent_count' => $connectionTest['agent_count'] ?? 0,
                    'configured_agent' => $connectionTest['configured_agent'] ?? null
                ];
                
                if (!$connectionTest['connected']) {
                    $this->criticalIssues[] = "Retell.ai connection failed: " . ($connectionTest['message'] ?? 'Unknown error');
                } elseif ($connectionTest['agent_count'] === 0) {
                    $this->criticalIssues[] = "No Retell.ai agents configured";
                } elseif (!$connectionTest['configured_agent']) {
                    $this->warnings[] = "Retell.ai agent ID configured but agent not found";
                }
            }
            
            // Get call statistics
            $callStats = $this->callMCP('retell/call-stats', [
                'company_id' => $this->companyId,
                'days' => 1
            ]);
            
            if ($callStats && isset($callStats['summary'])) {
                $summary = $callStats['summary'];
                $this->report['retell_calls_24h'] = [
                    'total' => $summary->total_calls ?? 0,
                    'completed' => $summary->completed_calls ?? 0,
                    'failed' => $summary->failed_calls ?? 0,
                    'with_appointments' => $summary->calls_with_appointments ?? 0,
                    'avg_duration' => round(($summary->avg_duration_seconds ?? 0) / 60, 2) . ' minutes',
                    'total_cost' => '$' . number_format($summary->total_cost ?? 0, 2)
                ];
                
                // Calculate conversion rate
                if ($summary->total_calls > 0) {
                    $conversionRate = ($summary->calls_with_appointments / $summary->total_calls) * 100;
                    $this->report['retell_calls_24h']['conversion_rate'] = round($conversionRate, 2) . '%';
                    
                    if ($conversionRate < 20) {
                        $this->warnings[] = "Low call-to-appointment conversion rate: {$conversionRate}%";
                    }
                }
            }
            
            // Get recent calls for analysis
            $recentCalls = $this->callMCP('retell/recent-calls', [
                'company_id' => $this->companyId,
                'limit' => 10
            ]);
            
            if ($recentCalls && isset($recentCalls['calls'])) {
                $this->report['recent_calls_sample'] = array_slice($recentCalls['calls'], 0, 5);
            }
            
            // Check phone numbers
            $phoneNumbers = $this->callMCP("retell/phone-numbers/{$this->companyId}");
            
            if ($phoneNumbers) {
                $this->report['phone_numbers'] = $phoneNumbers['count'] ?? 0;
                
                if ($phoneNumbers['count'] === 0) {
                    $this->criticalIssues[] = "No phone numbers configured for receiving calls";
                }
            }
            
            echo "✓ Retell.ai analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze Retell.ai integration: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Retell.ai integration check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Analyze Cal.com Integration
     */
    private function analyzeCalcomIntegration()
    {
        echo "📅 Analyzing Cal.com Integration...\n";
        
        try {
            // Test connection
            $connectionTest = $this->callMCP("calcom/test/{$this->companyId}");
            
            if ($connectionTest) {
                $this->report['calcom_connection'] = [
                    'connected' => $connectionTest['connected'] ?? false,
                    'user' => $connectionTest['user'] ?? null
                ];
                
                if (!$connectionTest['connected']) {
                    $this->criticalIssues[] = "Cal.com connection failed: " . ($connectionTest['message'] ?? 'Unknown error');
                }
            }
            
            // Get event types
            $eventTypes = $this->callMCP('calcom/event-types', ['company_id' => $this->companyId]);
            
            if ($eventTypes && !isset($eventTypes['error'])) {
                $this->report['calcom_event_types'] = $eventTypes['count'] ?? 0;
                
                if ($eventTypes['count'] === 0) {
                    $this->criticalIssues[] = "No Cal.com event types available";
                }
            }
            
            // Get recent bookings
            $bookings = $this->callMCP('calcom/bookings', [
                'company_id' => $this->companyId,
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d')
            ]);
            
            if ($bookings && isset($bookings['bookings'])) {
                $this->report['calcom_bookings_7d'] = $bookings['count'] ?? 0;
            }
            
            // Check event type assignments
            $assignments = $this->callMCP("calcom/assignments/{$this->companyId}");
            
            if ($assignments && !isset($assignments['error'])) {
                $totalBranches = count($assignments['branches'] ?? []);
                $branchesWithEventTypes = 0;
                $unassignedStaff = 0;
                
                foreach ($assignments['branches'] as $branch) {
                    if ($branch['calcom_event_type_id']) {
                        $branchesWithEventTypes++;
                    }
                    
                    foreach ($branch['staff'] as $staff) {
                        if (count($staff['assigned_event_types']) === 0) {
                            $unassignedStaff++;
                        }
                    }
                }
                
                $this->report['calcom_assignments'] = [
                    'total_branches' => $totalBranches,
                    'branches_configured' => $branchesWithEventTypes,
                    'staff_without_events' => $unassignedStaff
                ];
                
                if ($branchesWithEventTypes === 0) {
                    $this->criticalIssues[] = "No branches have Cal.com event types assigned";
                } elseif ($branchesWithEventTypes < $totalBranches) {
                    $this->warnings[] = "Only {$branchesWithEventTypes} out of {$totalBranches} branches have Cal.com configured";
                }
                
                if ($unassignedStaff > 0) {
                    $this->warnings[] = "{$unassignedStaff} staff members have no event types assigned";
                }
            }
            
            echo "✓ Cal.com analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze Cal.com integration: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Cal.com integration check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Analyze Sentry Errors (if configured)
     */
    private function analyzeSentryErrors()
    {
        echo "🐛 Analyzing Recent Errors...\n";
        
        try {
            // Check if Sentry is configured
            if (!config('mcp-sentry.sentry.auth_token')) {
                echo "ℹ️ Sentry not configured, skipping error analysis\n\n";
                return;
            }
            
            // Get recent issues
            $issues = $this->callMCP('sentry/issues', ['limit' => 25]);
            
            if ($issues && is_array($issues)) {
                $this->report['sentry_issues'] = count($issues);
                
                // Group by level
                $issueLevels = [];
                foreach ($issues as $issue) {
                    $level = $issue['level'] ?? 'unknown';
                    $issueLevels[$level] = ($issueLevels[$level] ?? 0) + 1;
                }
                
                $this->report['sentry_issue_levels'] = $issueLevels;
                
                if (($issueLevels['error'] ?? 0) > 10) {
                    $this->warnings[] = "High number of error-level issues in Sentry: " . $issueLevels['error'];
                }
                
                if (($issueLevels['fatal'] ?? 0) > 0) {
                    $this->criticalIssues[] = "Fatal errors detected in Sentry: " . $issueLevels['fatal'];
                }
                
                // Get top 5 issues by frequency
                $topIssues = array_slice($issues, 0, 5);
                $this->report['top_errors'] = array_map(function($issue) {
                    return [
                        'title' => $issue['title'] ?? 'Unknown',
                        'culprit' => $issue['culprit'] ?? 'Unknown',
                        'level' => $issue['level'] ?? 'unknown',
                        'count' => $issue['count'] ?? 0,
                        'user_count' => $issue['userCount'] ?? 0,
                        'last_seen' => $issue['lastSeen'] ?? null
                    ];
                }, $topIssues);
            }
            
            echo "✓ Error analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze Sentry errors: " . $e->getMessage() . "\n\n";
            // Don't add to warnings as Sentry might not be configured
        }
    }
    
    /**
     * Analyze Performance Metrics
     */
    private function analyzePerformanceMetrics()
    {
        echo "⚡ Analyzing Performance Metrics...\n";
        
        try {
            // Get queue metrics
            $queueMetrics = $this->callMCP('queue/metrics', ['period' => 'hour']);
            
            if ($queueMetrics) {
                $this->report['performance_metrics'] = [
                    'jobs_per_minute' => $queueMetrics['throughput']['jobs_per_minute'] ?? 0,
                    'avg_job_runtime' => round($queueMetrics['throughput']['runtime_average'] ?? 0, 2) . 'ms',
                    'avg_wait_time' => round($queueMetrics['wait_time']['average'] ?? 0, 2) . 's',
                    'max_wait_time' => round($queueMetrics['wait_time']['max'] ?? 0, 2) . 's'
                ];
                
                if (($queueMetrics['throughput']['runtime_average'] ?? 0) > 5000) {
                    $this->warnings[] = "High average job runtime: " . $queueMetrics['throughput']['runtime_average'] . "ms";
                }
                
                if (($queueMetrics['wait_time']['max'] ?? 0) > 300) {
                    $this->warnings[] = "High maximum queue wait time: " . $queueMetrics['wait_time']['max'] . " seconds";
                }
            }
            
            // Calculate booking conversion funnel
            if (isset($this->report['call_summary']) && isset($this->report['retell_calls_24h'])) {
                $totalCalls = $this->report['retell_calls_24h']['total'] ?? 0;
                $callsWithAppointments = $this->report['retell_calls_24h']['with_appointments'] ?? 0;
                $totalAppointments = $this->report['company_stats']['appointments'] ?? 0;
                
                $this->report['conversion_funnel'] = [
                    'calls_received' => $totalCalls,
                    'calls_to_appointments' => $callsWithAppointments,
                    'total_appointments' => $totalAppointments,
                    'conversion_rate' => $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100, 2) . '%' : '0%'
                ];
            }
            
            echo "✓ Performance analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze performance metrics: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Performance metrics check failed: " . $e->getMessage();
        }
    }
    
    /**
     * Generate and display the final report
     */
    private function generateReport($startTime)
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "📊 ASKPROAI SYSTEM ANALYSIS REPORT\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        // Executive Summary
        echo "📋 EXECUTIVE SUMMARY\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        $overallStatus = empty($this->criticalIssues) ? (empty($this->warnings) ? '✅ HEALTHY' : '⚠️ NEEDS ATTENTION') : '❌ CRITICAL';
        echo "Overall System Status: {$overallStatus}\n";
        echo "Analysis completed in: {$executionTime} seconds\n";
        echo "Critical Issues: " . count($this->criticalIssues) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n\n";
        
        // Critical Issues
        if (!empty($this->criticalIssues)) {
            echo "🚨 CRITICAL ISSUES (Immediate Action Required)\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->criticalIssues as $issue) {
                echo "❌ {$issue}\n";
            }
            echo "\n";
        }
        
        // Warnings
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS (Should Be Addressed)\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️ {$warning}\n";
            }
            echo "\n";
        }
        
        // System Overview
        echo "🏢 SYSTEM OVERVIEW\n";
        echo "───────────────────────────────────────────────────────────────\n";
        if (isset($this->report['company_stats'])) {
            echo "Branches: " . $this->report['company_stats']['branches'] . "\n";
            echo "Customers: " . number_format($this->report['company_stats']['customers']) . "\n";
            echo "Total Appointments: " . number_format($this->report['company_stats']['appointments']) . "\n";
            echo "Total Calls: " . number_format($this->report['company_stats']['calls']) . "\n";
        }
        echo "\n";
        
        // Queue Health
        echo "📊 QUEUE HEALTH\n";
        echo "───────────────────────────────────────────────────────────────\n";
        if (isset($this->report['queue'])) {
            echo "Horizon Status: " . strtoupper($this->report['queue']['status']) . "\n";
            echo "Failed Jobs: " . $this->report['queue']['failed_jobs'] . "\n";
            echo "Active Workers: " . ($this->report['queue']['workers']['active'] ?? 0) . "\n";
            echo "Jobs/Minute: " . ($this->report['queue']['throughput']['jobs_per_minute'] ?? 0) . "\n";
            
            if (!empty($this->report['queue']['queues'])) {
                echo "\nQueue Sizes:\n";
                foreach ($this->report['queue']['queues'] as $queue => $data) {
                    echo "  - {$queue}: {$data['size']} jobs (status: {$data['status']})\n";
                }
            }
        }
        echo "\n";
        
        // Call Statistics
        echo "📞 CALL STATISTICS (Last 7 Days)\n";
        echo "───────────────────────────────────────────────────────────────\n";
        if (isset($this->report['call_summary'])) {
            echo "Total Calls: " . number_format($this->report['call_summary']['total_calls']) . "\n";
            echo "Completed: " . number_format($this->report['call_summary']['completed_calls']) . "\n";
            echo "Failed: " . number_format($this->report['call_summary']['failed_calls']) . "\n";
            echo "Success Rate: " . $this->report['call_summary']['success_rate'] . "\n";
            echo "Total Cost: " . $this->report['call_summary']['total_cost'] . "\n";
            echo "Avg Cost/Call: " . $this->report['call_summary']['avg_cost_per_call'] . "\n";
        }
        echo "\n";
        
        // Integration Status
        echo "🔌 INTEGRATION STATUS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        // Retell.ai Status
        echo "Retell.ai (Phone System):\n";
        if (isset($this->report['retell_connection'])) {
            $status = $this->report['retell_connection']['connected'] ? '✅ Connected' : '❌ Disconnected';
            echo "  Status: {$status}\n";
            echo "  Agents: " . $this->report['retell_connection']['agent_count'] . "\n";
            echo "  Phone Numbers: " . ($this->report['phone_numbers'] ?? 0) . "\n";
        }
        
        if (isset($this->report['retell_calls_24h'])) {
            echo "  Last 24h: " . $this->report['retell_calls_24h']['total'] . " calls, " . 
                 ($this->report['retell_calls_24h']['conversion_rate'] ?? '0%') . " conversion\n";
        }
        
        echo "\nCal.com (Calendar System):\n";
        if (isset($this->report['calcom_connection'])) {
            $status = $this->report['calcom_connection']['connected'] ? '✅ Connected' : '❌ Disconnected';
            echo "  Status: {$status}\n";
            echo "  Event Types: " . ($this->report['calcom_event_types'] ?? 0) . "\n";
            echo "  Bookings (7d): " . ($this->report['calcom_bookings_7d'] ?? 0) . "\n";
        }
        
        if (isset($this->report['calcom_assignments'])) {
            echo "  Branches Configured: " . $this->report['calcom_assignments']['branches_configured'] . 
                 "/" . $this->report['calcom_assignments']['total_branches'] . "\n";
        }
        echo "\n";
        
        // Performance Metrics
        if (isset($this->report['performance_metrics'])) {
            echo "⚡ PERFORMANCE METRICS\n";
            echo "───────────────────────────────────────────────────────────────\n";
            echo "Jobs/Minute: " . $this->report['performance_metrics']['jobs_per_minute'] . "\n";
            echo "Avg Job Runtime: " . $this->report['performance_metrics']['avg_job_runtime'] . "\n";
            echo "Avg Queue Wait: " . $this->report['performance_metrics']['avg_wait_time'] . "\n";
            echo "Max Queue Wait: " . $this->report['performance_metrics']['max_wait_time'] . "\n\n";
        }
        
        // Recommendations
        $this->generateRecommendations();
        if (!empty($this->recommendations)) {
            echo "💡 RECOMMENDATIONS\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->recommendations as $i => $recommendation) {
                echo ($i + 1) . ". {$recommendation}\n";
            }
            echo "\n";
        }
        
        // Save detailed report to file
        $reportFile = storage_path('logs/system-analysis-' . date('Y-m-d-His') . '.json');
        file_put_contents($reportFile, json_encode([
            'timestamp' => now()->toIso8601String(),
            'execution_time' => $executionTime,
            'status' => $overallStatus,
            'critical_issues' => $this->criticalIssues,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'detailed_report' => $this->report
        ], JSON_PRETTY_PRINT));
        
        echo "───────────────────────────────────────────────────────────────\n";
        echo "📄 Detailed report saved to: {$reportFile}\n";
        echo "═══════════════════════════════════════════════════════════════\n";
    }
    
    /**
     * Generate recommendations based on findings
     */
    private function generateRecommendations()
    {
        // Queue recommendations
        if (isset($this->report['queue']['status']) && $this->report['queue']['status'] !== 'running') {
            $this->recommendations[] = "Start Horizon immediately: php artisan horizon";
        }
        
        if (($this->report['queue']['failed_jobs'] ?? 0) > 0) {
            $this->recommendations[] = "Review and retry failed jobs: php artisan horizon:retry all";
        }
        
        // Integration recommendations
        if (isset($this->report['retell_connection']) && !$this->report['retell_connection']['connected']) {
            $this->recommendations[] = "Configure Retell.ai API credentials in the admin panel";
        }
        
        if (isset($this->report['calcom_connection']) && !$this->report['calcom_connection']['connected']) {
            $this->recommendations[] = "Configure Cal.com API credentials in the admin panel";
        }
        
        if (($this->report['phone_numbers'] ?? 0) === 0) {
            $this->recommendations[] = "Configure at least one phone number for receiving calls";
        }
        
        if (($this->report['calcom_event_types'] ?? 0) === 0) {
            $this->recommendations[] = "Sync Cal.com event types or create appointment types";
        }
        
        // Performance recommendations
        if (isset($this->report['retell_calls_24h']['conversion_rate'])) {
            $conversionRate = floatval(str_replace('%', '', $this->report['retell_calls_24h']['conversion_rate']));
            if ($conversionRate < 20) {
                $this->recommendations[] = "Improve AI agent scripts to increase call-to-appointment conversion";
            }
        }
        
        if (isset($this->report['call_summary']['success_rate'])) {
            $successRate = floatval(str_replace('%', '', $this->report['call_summary']['success_rate']));
            if ($successRate < 80) {
                $this->recommendations[] = "Investigate call failures and improve system reliability";
            }
        }
        
        // Capacity recommendations
        if (isset($this->report['calcom_assignments']['staff_without_events']) && 
            $this->report['calcom_assignments']['staff_without_events'] > 0) {
            $this->recommendations[] = "Assign Cal.com event types to all active staff members";
        }
        
        // Monitoring recommendations
        if (!config('mcp-sentry.sentry.auth_token')) {
            $this->recommendations[] = "Configure Sentry for better error tracking and monitoring";
        }
        
        // General recommendations
        if (empty($this->criticalIssues) && empty($this->warnings)) {
            $this->recommendations[] = "System is healthy - consider setting up automated monitoring alerts";
        }
    }
    
    /**
     * Call an MCP endpoint
     */
    private function callMCP($endpoint, $params = [])
    {
        try {
            $url = $this->apiUrl . '/' . $endpoint;
            
            $response = Http::withToken($this->authToken)
                ->timeout(30);
            
            if (!empty($params)) {
                if (in_array(strtoupper(explode('/', $endpoint)[0]), ['POST', 'PUT', 'PATCH'])) {
                    $response = $response->post($url, $params);
                } else {
                    $response = $response->get($url, $params);
                }
            } else {
                $response = $response->get($url);
            }
            
            if ($response->successful()) {
                return $response->json();
            } else {
                throw new Exception("API call failed: " . $response->status() . " - " . $response->body());
            }
            
        } catch (Exception $e) {
            echo "⚠️ MCP call failed ({$endpoint}): " . $e->getMessage() . "\n";
            return null;
        }
    }
}

// Run the analysis
try {
    $analyzer = new AskProAISystemAnalyzer();
    $analyzer->analyze();
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}