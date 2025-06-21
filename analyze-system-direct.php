#!/usr/bin/env php
<?php

/**
 * AskProAI Direct System Analysis Script
 * 
 * This script directly uses the MCP services without going through the API,
 * making it easier to run for system administrators.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\DatabaseMCPServer;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\QueueMCPServer;
use App\Services\MCP\SentryMCPServer;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class DirectSystemAnalyzer
{
    private $databaseMCP;
    private $calcomMCP;
    private $retellMCP;
    private $queueMCP;
    private $sentryMCP;
    private $companyId;
    private $report = [];
    private $criticalIssues = [];
    private $warnings = [];
    private $recommendations = [];
    
    public function __construct()
    {
        // Initialize MCP services
        $this->databaseMCP = app(DatabaseMCPServer::class);
        $this->calcomMCP = app(CalcomMCPServer::class);
        $this->retellMCP = app(RetellMCPServer::class);
        $this->queueMCP = app(QueueMCPServer::class);
        
        // Sentry is optional
        try {
            $this->sentryMCP = app(SentryMCPServer::class);
        } catch (Exception $e) {
            $this->sentryMCP = null;
        }
        
        // Get the first company for analysis
        $company = \App\Models\Company::first();
        if (!$company) {
            throw new Exception("No company found in the database.");
        }
        
        $this->companyId = $company->id;
        
        echo "🔍 AskProAI Direct System Analysis\n";
        echo "Company: {$company->name} (ID: {$company->id})\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }
    
    public function analyze()
    {
        $startTime = microtime(true);
        
        try {
            // 1. Queue Health
            $this->analyzeQueueHealth();
            
            // 2. Database Health
            $this->analyzeDatabaseHealth();
            
            // 3. Retell.ai Integration
            $this->analyzeRetellIntegration();
            
            // 4. Cal.com Integration
            $this->analyzeCalcomIntegration();
            
            // 5. Recent Errors
            $this->analyzeSentryErrors();
            
            // 6. Generate Report
            $this->generateReport($startTime);
            
        } catch (Exception $e) {
            echo "❌ Analysis failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    private function analyzeQueueHealth()
    {
        echo "📊 Analyzing Queue Health...\n";
        
        try {
            $overview = $this->queueMCP->getOverview();
            
            $this->report['queue'] = $overview;
            
            // Check for issues
            if ($overview['horizon_status'] !== 'running') {
                $this->criticalIssues[] = "Horizon queue processor is not running!";
            }
            
            if ($overview['failed_jobs'] > 100) {
                $this->criticalIssues[] = "High number of failed jobs: {$overview['failed_jobs']}";
            } elseif ($overview['failed_jobs'] > 50) {
                $this->warnings[] = "Elevated failed job count: {$overview['failed_jobs']}";
            }
            
            // Get recent failed jobs
            $failedJobs = $this->queueMCP->getFailedJobs(['limit' => 10]);
            if (!empty($failedJobs['jobs'])) {
                $this->report['recent_failed_jobs'] = array_slice($failedJobs['jobs'], 0, 5);
            }
            
            echo "✓ Queue analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze queue health: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Queue health check failed";
        }
    }
    
    private function analyzeDatabaseHealth()
    {
        echo "🗄️ Analyzing Database Health...\n";
        
        try {
            // Failed appointments
            $failedAppointments = $this->databaseMCP->getFailedAppointments(['hours' => 24, 'limit' => 100]);
            
            if (isset($failedAppointments['data'])) {
                $failedCount = count($failedAppointments['data']);
                $this->report['failed_appointments_24h'] = $failedCount;
                
                if ($failedCount > 10) {
                    $this->criticalIssues[] = "High appointment failure rate: {$failedCount} failures in last 24 hours";
                }
            }
            
            // Call statistics
            $callStats = $this->databaseMCP->getCallStats(['days' => 7]);
            
            if (isset($callStats['data'])) {
                $this->report['call_stats_7d'] = $callStats['data'];
                
                // Calculate totals
                $totalCalls = 0;
                $completedCalls = 0;
                $totalCost = 0;
                
                foreach ($callStats['data'] as $day) {
                    $totalCalls += $day->total_calls ?? 0;
                    $completedCalls += $day->completed_calls ?? 0;
                    $totalCost += $day->total_cost ?? 0;
                }
                
                $successRate = $totalCalls > 0 ? ($completedCalls / $totalCalls) * 100 : 0;
                
                $this->report['call_summary'] = [
                    'total_calls' => $totalCalls,
                    'completed_calls' => $completedCalls,
                    'success_rate' => round($successRate, 2) . '%',
                    'total_cost' => '$' . number_format($totalCost, 2)
                ];
            }
            
            // Tenant stats
            $tenantStats = $this->databaseMCP->getTenantStats($this->companyId);
            
            if (isset($tenantStats['data'][0])) {
                $stats = $tenantStats['data'][0];
                $this->report['company_stats'] = [
                    'branches' => $stats->branch_count ?? 0,
                    'customers' => $stats->customer_count ?? 0,
                    'appointments' => $stats->appointment_count ?? 0,
                    'calls' => $stats->call_count ?? 0
                ];
            }
            
            echo "✓ Database analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze database health: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Database health check failed";
        }
    }
    
    private function analyzeRetellIntegration()
    {
        echo "📞 Analyzing Retell.ai Integration...\n";
        
        try {
            // Test connection
            $connectionTest = $this->retellMCP->testConnection($this->companyId);
            
            $this->report['retell_connection'] = $connectionTest;
            
            if (!$connectionTest['connected']) {
                $this->criticalIssues[] = "Retell.ai connection failed: " . ($connectionTest['message'] ?? 'Unknown error');
            }
            
            // Get call statistics
            $callStats = $this->retellMCP->getCallStats([
                'company_id' => $this->companyId,
                'days' => 1
            ]);
            
            if (isset($callStats['summary'])) {
                $this->report['retell_calls_24h'] = $callStats['summary'];
            }
            
            // Get phone numbers
            $phoneNumbers = $this->retellMCP->getPhoneNumbers($this->companyId);
            
            if ($phoneNumbers['count'] === 0) {
                $this->criticalIssues[] = "No phone numbers configured for receiving calls";
            }
            
            $this->report['phone_numbers'] = $phoneNumbers['count'];
            
            echo "✓ Retell.ai analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze Retell.ai: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Retell.ai check failed";
        }
    }
    
    private function analyzeCalcomIntegration()
    {
        echo "📅 Analyzing Cal.com Integration...\n";
        
        try {
            // Test connection
            $connectionTest = $this->calcomMCP->testConnection($this->companyId);
            
            $this->report['calcom_connection'] = $connectionTest;
            
            if (!$connectionTest['connected']) {
                $this->criticalIssues[] = "Cal.com connection failed: " . ($connectionTest['message'] ?? 'Unknown error');
            }
            
            // Get event types
            $eventTypes = $this->calcomMCP->getEventTypes($this->companyId);
            
            if (!isset($eventTypes['error'])) {
                $this->report['calcom_event_types'] = $eventTypes['count'] ?? 0;
                
                if ($eventTypes['count'] === 0) {
                    $this->criticalIssues[] = "No Cal.com event types available";
                }
            }
            
            // Get assignments
            $assignments = $this->calcomMCP->getEventTypeAssignments($this->companyId);
            
            if (!isset($assignments['error'])) {
                $this->report['calcom_assignments'] = [
                    'total_branches' => count($assignments['branches'] ?? []),
                    'total_event_types' => $assignments['total_event_types'] ?? 0
                ];
            }
            
            echo "✓ Cal.com analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze Cal.com: " . $e->getMessage() . "\n\n";
            $this->warnings[] = "Cal.com check failed";
        }
    }
    
    private function analyzeSentryErrors()
    {
        echo "🐛 Analyzing Recent Errors...\n";
        
        try {
            if (!$this->sentryMCP || !config('mcp-sentry.sentry.auth_token')) {
                echo "ℹ️ Sentry not configured, skipping\n\n";
                return;
            }
            
            $issues = $this->sentryMCP->listIssues(['limit' => 10]);
            
            if (!isset($issues['error'])) {
                $this->report['sentry_issues'] = count($issues);
            }
            
            echo "✓ Error analysis complete\n\n";
            
        } catch (Exception $e) {
            echo "⚠️ Failed to analyze errors: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function generateReport($startTime)
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "📊 SYSTEM ANALYSIS REPORT\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        // Summary
        $status = empty($this->criticalIssues) ? (empty($this->warnings) ? '✅ HEALTHY' : '⚠️ NEEDS ATTENTION') : '❌ CRITICAL';
        echo "Status: {$status}\n";
        echo "Execution Time: {$executionTime}s\n";
        echo "Critical Issues: " . count($this->criticalIssues) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n\n";
        
        // Critical Issues
        if (!empty($this->criticalIssues)) {
            echo "🚨 CRITICAL ISSUES\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->criticalIssues as $issue) {
                echo "❌ {$issue}\n";
            }
            echo "\n";
        }
        
        // Warnings
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️ {$warning}\n";
            }
            echo "\n";
        }
        
        // Key Metrics
        echo "📈 KEY METRICS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        if (isset($this->report['queue'])) {
            echo "Queue Status: " . strtoupper($this->report['queue']['horizon_status']) . "\n";
            echo "Failed Jobs: " . $this->report['queue']['failed_jobs'] . "\n";
        }
        
        if (isset($this->report['call_summary'])) {
            echo "Calls (7d): " . $this->report['call_summary']['total_calls'] . 
                 " (Success: " . $this->report['call_summary']['success_rate'] . ")\n";
        }
        
        if (isset($this->report['company_stats'])) {
            echo "Branches: " . $this->report['company_stats']['branches'] . "\n";
            echo "Customers: " . number_format($this->report['company_stats']['customers']) . "\n";
            echo "Appointments: " . number_format($this->report['company_stats']['appointments']) . "\n";
        }
        
        echo "\n";
        
        // Integration Status
        echo "🔌 INTEGRATIONS\n";
        echo "───────────────────────────────────────────────────────────────\n";
        
        if (isset($this->report['retell_connection'])) {
            $status = $this->report['retell_connection']['connected'] ? '✅' : '❌';
            echo "Retell.ai: {$status}\n";
        }
        
        if (isset($this->report['calcom_connection'])) {
            $status = $this->report['calcom_connection']['connected'] ? '✅' : '❌';
            echo "Cal.com: {$status}\n";
        }
        
        echo "\n";
        
        // Recommendations
        $this->generateRecommendations();
        if (!empty($this->recommendations)) {
            echo "💡 RECOMMENDATIONS\n";
            echo "───────────────────────────────────────────────────────────────\n";
            foreach ($this->recommendations as $i => $rec) {
                echo ($i + 1) . ". {$rec}\n";
            }
            echo "\n";
        }
        
        // Save report
        $reportFile = storage_path('logs/system-analysis-direct-' . date('Y-m-d-His') . '.json');
        file_put_contents($reportFile, json_encode([
            'timestamp' => now()->toIso8601String(),
            'execution_time' => $executionTime,
            'status' => $status,
            'critical_issues' => $this->criticalIssues,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'report' => $this->report
        ], JSON_PRETTY_PRINT));
        
        echo "───────────────────────────────────────────────────────────────\n";
        echo "📄 Report saved to: {$reportFile}\n";
        echo "═══════════════════════════════════════════════════════════════\n";
    }
    
    private function generateRecommendations()
    {
        if (isset($this->report['queue']['horizon_status']) && $this->report['queue']['horizon_status'] !== 'running') {
            $this->recommendations[] = "Start Horizon: php artisan horizon";
        }
        
        if (($this->report['queue']['failed_jobs'] ?? 0) > 0) {
            $this->recommendations[] = "Review failed jobs in admin panel";
        }
        
        if (isset($this->report['retell_connection']) && !$this->report['retell_connection']['connected']) {
            $this->recommendations[] = "Configure Retell.ai API credentials";
        }
        
        if (isset($this->report['calcom_connection']) && !$this->report['calcom_connection']['connected']) {
            $this->recommendations[] = "Configure Cal.com API credentials";
        }
        
        if (($this->report['phone_numbers'] ?? 0) === 0) {
            $this->recommendations[] = "Add phone numbers for receiving calls";
        }
        
        if (($this->report['calcom_event_types'] ?? 0) === 0) {
            $this->recommendations[] = "Create or sync Cal.com event types";
        }
    }
}

// Run the analysis
try {
    $analyzer = new DirectSystemAnalyzer();
    $analyzer->analyze();
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}