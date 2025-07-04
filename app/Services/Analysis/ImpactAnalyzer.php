<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\DataFlow\DataFlowLogger;
use App\Traits\UsesMCPServers;

/**
 * Impact Analyzer Service
 * 
 * Analyzes changes before deployment to:
 * - Identify potential breaking changes
 * - Warn about risky modifications
 * - Generate automatic rollback plans
 * - Ensure system stability
 * 
 * Works closely with SystemUnderstandingService to understand existing code.
 */
class ImpactAnalyzer
{
    use UsesMCPServers;
    
    // SystemUnderstandingService is already defined in UsesMCPServers trait
    protected DataFlowLogger $dataFlowLogger;
    
    /**
     * Risk levels for changes
     */
    const RISK_LEVELS = [
        'low' => 'Safe changes with minimal impact',
        'medium' => 'Changes that may affect some functionality',
        'high' => 'Changes that could break existing functionality',
        'critical' => 'Changes that will definitely break the system'
    ];
    
    /**
     * Change types we analyze
     */
    const CHANGE_TYPES = [
        'api_contract' => 'API endpoint or response format changes',
        'database_schema' => 'Database table or column changes',
        'method_signature' => 'Method parameter or return type changes',
        'class_removal' => 'Removing entire classes or services',
        'dependency_update' => 'Updating external dependencies',
        'configuration' => 'Environment or config file changes',
        'integration' => 'External service integration changes',
        'mcp_server' => 'MCP server modifications'
    ];
    
    public function __construct(
        SystemUnderstandingService $systemUnderstanding,
        DataFlowLogger $dataFlowLogger
    ) {
        $this->systemUnderstanding = $systemUnderstanding;
        $this->dataFlowLogger = $dataFlowLogger;
        $this->initializeMCP();
    }
    
    /**
     * Analyze the impact of proposed changes
     * 
     * @param array $changes List of changes to analyze
     * @param array $options Analysis options
     * @return array Detailed impact analysis
     */
    public function analyzeChanges(array $changes, array $options = []): array
    {
        $correlationId = Str::uuid()->toString();
        
        // Start tracking the analysis
        $this->dataFlowLogger->startFlow(
            'internal_processing',
            'impact_analyzer',
            'analysis_report',
            ['total_changes' => count($changes)]
        );
        
        $analysis = [
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
            'risk_level' => 'low',
            'total_changes' => count($changes),
            'breaking_changes' => [],
            'warnings' => [],
            'affected_systems' => [],
            'affected_components' => [],
            'rollback_plan' => [],
            'recommendations' => [],
            'automated_tests_required' => [],
            'deployment_strategy' => 'standard'
        ];
        
        try {
            // Analyze each change
            foreach ($changes as $change) {
                $changeAnalysis = $this->analyzeIndividualChange($change);
                $this->mergeAnalysisResults($analysis, $changeAnalysis);
            }
            
            // Analyze cumulative impact
            $this->analyzeCumulativeImpact($analysis);
            
            // Generate rollback plan
            $analysis['rollback_plan'] = $this->generateRollbackPlan($changes, $analysis);
            
            // Generate recommendations
            $analysis['recommendations'] = $this->generateRecommendations($analysis);
            
            // Determine deployment strategy
            $analysis['deployment_strategy'] = $this->determineDeploymentStrategy($analysis);
            
            // Log the analysis
            $this->dataFlowLogger->completeFlow($correlationId, 'completed', $analysis);
            
        } catch (\Exception $e) {
            Log::error('Impact analysis failed', [
                'error' => $e->getMessage(),
                'changes' => $changes
            ]);
            
            $analysis['error'] = $e->getMessage();
            $analysis['risk_level'] = 'critical';
            
            $this->dataFlowLogger->completeFlow($correlationId, 'failed', $analysis);
        }
        
        return $analysis;
    }
    
    /**
     * Analyze an individual change
     */
    protected function analyzeIndividualChange(array $change): array
    {
        $changeType = $this->detectChangeType($change);
        $analysis = [
            'type' => $changeType,
            'risk_level' => 'low',
            'breaking' => false,
            'warnings' => [],
            'affected_components' => [],
            'tests_required' => []
        ];
        
        switch ($changeType) {
            case 'api_contract':
                $analysis = $this->analyzeApiContractChange($change, $analysis);
                break;
                
            case 'database_schema':
                $analysis = $this->analyzeDatabaseSchemaChange($change, $analysis);
                break;
                
            case 'method_signature':
                $analysis = $this->analyzeMethodSignatureChange($change, $analysis);
                break;
                
            case 'class_removal':
                $analysis = $this->analyzeClassRemoval($change, $analysis);
                break;
                
            case 'dependency_update':
                $analysis = $this->analyzeDependencyUpdate($change, $analysis);
                break;
                
            case 'configuration':
                $analysis = $this->analyzeConfigurationChange($change, $analysis);
                break;
                
            case 'integration':
                $analysis = $this->analyzeIntegrationChange($change, $analysis);
                break;
                
            case 'mcp_server':
                $analysis = $this->analyzeMCPServerChange($change, $analysis);
                break;
        }
        
        return $analysis;
    }
    
    /**
     * Detect the type of change
     */
    protected function detectChangeType(array $change): string
    {
        // Check for API route changes
        if (isset($change['file']) && str_contains($change['file'], 'routes/') ||
            isset($change['type']) && $change['type'] === 'route') {
            return 'api_contract';
        }
        
        // Check for migration files
        if (isset($change['file']) && str_contains($change['file'], 'migrations/')) {
            return 'database_schema';
        }
        
        // Check for method signature changes
        if (isset($change['type']) && in_array($change['type'], ['method_signature', 'parameter_change'])) {
            return 'method_signature';
        }
        
        // Check for class removal
        if (isset($change['action']) && $change['action'] === 'delete' &&
            isset($change['file']) && str_ends_with($change['file'], '.php')) {
            return 'class_removal';
        }
        
        // Check for composer.json changes
        if (isset($change['file']) && str_contains($change['file'], 'composer.json')) {
            return 'dependency_update';
        }
        
        // Check for config changes
        if (isset($change['file']) && (str_contains($change['file'], 'config/') || 
            str_contains($change['file'], '.env'))) {
            return 'configuration';
        }
        
        // Check for MCP server changes
        if (isset($change['file']) && str_contains($change['file'], 'Services/MCP/')) {
            return 'mcp_server';
        }
        
        // Check for integration changes
        if (isset($change['file']) && (str_contains($change['file'], 'Services/Webhooks/') ||
            str_contains($change['file'], 'Http/Controllers/') && str_contains($change['file'], 'Webhook'))) {
            return 'integration';
        }
        
        return 'unknown';
    }
    
    /**
     * Analyze API contract changes
     */
    protected function analyzeApiContractChange(array $change, array $analysis): array
    {
        // Check if it's a breaking change
        if (isset($change['details'])) {
            // Removing endpoints is always breaking
            if ($change['action'] === 'delete' || isset($change['details']['removed_endpoint'])) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'critical';
                $analysis['warnings'][] = 'Removing API endpoint will break existing clients';
            }
            
            // Changing response format is breaking
            if (isset($change['details']['response_format_changed'])) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'API response format change may break clients';
            }
            
            // Adding required parameters is breaking
            if (isset($change['details']['required_param_added'])) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Adding required parameter breaks existing API calls';
            }
        }
        
        // Find affected components
        $analysis['affected_components'] = $this->findApiConsumers($change['endpoint'] ?? '');
        
        // Tests required
        $analysis['tests_required'][] = 'API contract tests';
        $analysis['tests_required'][] = 'Integration tests for all consumers';
        
        return $analysis;
    }
    
    /**
     * Analyze database schema changes
     */
    protected function analyzeDatabaseSchemaChange(array $change, array $analysis): array
    {
        // Parse migration file if available
        if (isset($change['file']) && File::exists($change['file'])) {
            $content = File::get($change['file']);
            
            // Check for dangerous operations
            if (str_contains($content, 'dropColumn') || str_contains($content, 'drop(')) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'critical';
                $analysis['warnings'][] = 'Dropping columns will cause data loss';
            }
            
            if (str_contains($content, 'dropTable') || str_contains($content, 'drop()')) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'critical';
                $analysis['warnings'][] = 'Dropping tables will cause data loss';
            }
            
            if (str_contains($content, 'renameColumn')) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Renaming columns requires code updates';
            }
            
            // Check for index changes
            if (str_contains($content, 'dropIndex')) {
                $analysis['risk_level'] = 'medium';
                $analysis['warnings'][] = 'Dropping indexes may impact performance';
            }
        }
        
        // Find affected models
        $analysis['affected_components'] = $this->findAffectedModels($change);
        
        // Tests required
        $analysis['tests_required'][] = 'Database migration rollback test';
        $analysis['tests_required'][] = 'Model integration tests';
        
        return $analysis;
    }
    
    /**
     * Analyze method signature changes
     */
    protected function analyzeMethodSignatureChange(array $change, array $analysis): array
    {
        $class = $change['class'] ?? '';
        $method = $change['method'] ?? '';
        
        // Use SystemUnderstandingService to find usage
        $componentAnalysis = $this->systemUnderstanding->analyzeComponent($class);
        
        if (isset($componentAnalysis['implementation']['methods'][$method])) {
            $methodInfo = $componentAnalysis['implementation']['methods'][$method];
            
            // Check if method is public
            if ($methodInfo['visibility'] === 'public') {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Changing public method signature may break consumers';
                
                // Find all callers
                $callers = $this->findMethodCallers($class, $method);
                $analysis['affected_components'] = array_merge(
                    $analysis['affected_components'],
                    $callers
                );
            }
        }
        
        // Parameter changes
        if (isset($change['details']['parameters'])) {
            if (isset($change['details']['parameters']['added'])) {
                $analysis['warnings'][] = 'Adding parameters requires updating all callers';
            }
            
            if (isset($change['details']['parameters']['removed'])) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Removing parameters breaks existing calls';
            }
        }
        
        // Return type changes
        if (isset($change['details']['return_type_changed'])) {
            $analysis['risk_level'] = 'medium';
            $analysis['warnings'][] = 'Return type change may affect consumers';
        }
        
        $analysis['tests_required'][] = 'Unit tests for the modified method';
        $analysis['tests_required'][] = 'Integration tests for all callers';
        
        return $analysis;
    }
    
    /**
     * Analyze class removal
     */
    protected function analyzeClassRemoval(array $change, array $analysis): array
    {
        $className = $this->extractClassNameFromFile($change['file'] ?? '');
        
        if ($className) {
            // This is always a breaking change
            $analysis['breaking'] = true;
            $analysis['risk_level'] = 'critical';
            $analysis['warnings'][] = "Removing class {$className} will break all dependencies";
            
            // Find all dependencies
            $dependencies = $this->findClassDependencies($className);
            $analysis['affected_components'] = $dependencies;
            
            // Check if it's a service
            if (str_contains($className, 'Service')) {
                $analysis['warnings'][] = 'Removing a service class affects business logic';
            }
            
            // Check if it's a model
            if (str_contains($className, 'Models\\')) {
                $analysis['warnings'][] = 'Removing a model requires database migration';
            }
        }
        
        $analysis['tests_required'][] = 'Remove all tests for the deleted class';
        $analysis['tests_required'][] = 'Update tests for dependent classes';
        
        return $analysis;
    }
    
    /**
     * Analyze dependency updates
     */
    protected function analyzeDependencyUpdate(array $change, array $analysis): array
    {
        // Check for major version updates
        if (isset($change['details']['version_change'])) {
            $oldVersion = $change['details']['version_change']['from'] ?? '';
            $newVersion = $change['details']['version_change']['to'] ?? '';
            
            if ($this->isMajorVersionChange($oldVersion, $newVersion)) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Major version update may introduce breaking changes';
            }
        }
        
        // Check for security updates
        if (isset($change['details']['security_update'])) {
            $analysis['risk_level'] = 'medium';
            $analysis['warnings'][] = 'Security update - test thoroughly but deploy quickly';
        }
        
        // Check specific packages
        $package = $change['package'] ?? '';
        if (in_array($package, ['laravel/framework', 'filament/filament'])) {
            $analysis['risk_level'] = 'high';
            $analysis['warnings'][] = 'Core framework update - extensive testing required';
            $analysis['affected_components'][] = 'Entire application';
        }
        
        $analysis['tests_required'][] = 'Full test suite run';
        $analysis['tests_required'][] = 'Manual testing of affected features';
        
        return $analysis;
    }
    
    /**
     * Analyze configuration changes
     */
    protected function analyzeConfigurationChange(array $change, array $analysis): array
    {
        $file = $change['file'] ?? '';
        
        // Environment file changes
        if (str_contains($file, '.env')) {
            $analysis['risk_level'] = 'medium';
            $analysis['warnings'][] = 'Environment changes require careful deployment';
            
            // Check for sensitive changes
            if (isset($change['details']['keys'])) {
                foreach ($change['details']['keys'] as $key) {
                    if (in_array($key, ['DB_', 'MAIL_', 'AWS_', 'STRIPE_'])) {
                        $analysis['risk_level'] = 'high';
                        $analysis['warnings'][] = "Critical configuration change: {$key}";
                    }
                }
            }
        }
        
        // Config file changes
        if (str_contains($file, 'config/')) {
            $configFile = basename($file, '.php');
            
            if (in_array($configFile, ['database', 'queue', 'cache'])) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = "Core configuration change: {$configFile}";
                $analysis['affected_components'][] = "All components using {$configFile}";
            }
        }
        
        $analysis['tests_required'][] = 'Configuration validation test';
        $analysis['tests_required'][] = 'Service connectivity tests';
        
        return $analysis;
    }
    
    /**
     * Analyze integration changes
     */
    protected function analyzeIntegrationChange(array $change, array $analysis): array
    {
        // Identify which integration
        $integration = $this->identifyIntegration($change['file'] ?? '');
        
        if ($integration) {
            $analysis['affected_components'][] = "{$integration} integration";
            
            // Check data flow impact
            $dataFlows = $this->dataFlowLogger->findFlows([
                'source' => $integration,
                'date_from' => now()->subDays(7)
            ]);
            
            if (count($dataFlows) > 100) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = "{$integration} processes high volume - careful testing required";
            }
            
            // Webhook changes are critical
            if (str_contains($change['file'] ?? '', 'Webhook')) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'Webhook changes affect real-time data processing';
            }
        }
        
        $analysis['tests_required'][] = 'Integration tests with mock external service';
        $analysis['tests_required'][] = 'End-to-end flow test';
        
        return $analysis;
    }
    
    /**
     * Analyze MCP server changes
     */
    protected function analyzeMCPServerChange(array $change, array $analysis): array
    {
        $serverName = $this->extractMCPServerName($change['file'] ?? '');
        
        if ($serverName) {
            $analysis['affected_components'][] = "MCP Server: {$serverName}";
            
            // Check if other services use this MCP server
            $usage = $this->findMCPServerUsage($serverName);
            $analysis['affected_components'] = array_merge(
                $analysis['affected_components'],
                $usage
            );
            
            if (count($usage) > 5) {
                $analysis['risk_level'] = 'high';
                $analysis['warnings'][] = 'MCP server used by many services - broad impact';
            }
            
            // Method removal is critical
            if (isset($change['details']['methods_removed'])) {
                $analysis['breaking'] = true;
                $analysis['risk_level'] = 'critical';
                $analysis['warnings'][] = 'Removing MCP methods breaks dependent services';
            }
        }
        
        $analysis['tests_required'][] = 'MCP server health check';
        $analysis['tests_required'][] = 'MCP method availability test';
        
        return $analysis;
    }
    
    /**
     * Analyze cumulative impact of all changes
     */
    protected function analyzeCumulativeImpact(array &$analysis): void
    {
        // Count risk levels
        $riskCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];
        
        foreach ($analysis['breaking_changes'] as $change) {
            $riskCounts[$change['risk_level']]++;
        }
        
        // Determine overall risk
        if ($riskCounts['critical'] > 0) {
            $analysis['risk_level'] = 'critical';
        } elseif ($riskCounts['high'] > 2) {
            $analysis['risk_level'] = 'critical';
        } elseif ($riskCounts['high'] > 0) {
            $analysis['risk_level'] = 'high';
        } elseif ($riskCounts['medium'] > 3) {
            $analysis['risk_level'] = 'high';
        } elseif ($riskCounts['medium'] > 0) {
            $analysis['risk_level'] = 'medium';
        }
        
        // Check for system-wide impact
        $uniqueComponents = array_unique($analysis['affected_components']);
        if (count($uniqueComponents) > 10) {
            $analysis['warnings'][] = 'Changes affect many components - consider phased deployment';
            if ($analysis['risk_level'] === 'medium') {
                $analysis['risk_level'] = 'high';
            }
        }
        
        // Check for external system impact
        $externalSystems = array_intersect($analysis['affected_systems'], ['retell', 'calcom', 'stripe']);
        if (count($externalSystems) > 1) {
            $analysis['warnings'][] = 'Multiple external integrations affected - coordinate carefully';
        }
    }
    
    /**
     * Generate rollback plan
     */
    protected function generateRollbackPlan(array $changes, array $analysis): array
    {
        $plan = [
            'preparation' => [],
            'rollback_steps' => [],
            'validation_steps' => [],
            'estimated_time' => 0
        ];
        
        // Preparation steps
        $plan['preparation'][] = [
            'step' => 'Create full database backup',
            'command' => 'php artisan backup:run --only-db',
            'time_estimate' => 5
        ];
        
        $plan['preparation'][] = [
            'step' => 'Tag current version',
            'command' => 'git tag rollback-' . now()->format('Y-m-d-His'),
            'time_estimate' => 1
        ];
        
        $plan['preparation'][] = [
            'step' => 'Document current configuration',
            'command' => 'php artisan config:cache && cp bootstrap/cache/config.php storage/rollback/',
            'time_estimate' => 1
        ];
        
        // Rollback steps based on change types
        foreach ($changes as $change) {
            $type = $this->detectChangeType($change);
            
            switch ($type) {
                case 'database_schema':
                    $plan['rollback_steps'][] = [
                        'step' => 'Rollback database migration',
                        'command' => 'php artisan migrate:rollback --step=1',
                        'time_estimate' => 2
                    ];
                    break;
                    
                case 'dependency_update':
                    $plan['rollback_steps'][] = [
                        'step' => 'Restore composer.lock',
                        'command' => 'git checkout HEAD~1 composer.lock && composer install',
                        'time_estimate' => 5
                    ];
                    break;
                    
                case 'configuration':
                    $plan['rollback_steps'][] = [
                        'step' => 'Restore configuration',
                        'command' => 'cp storage/rollback/config.php bootstrap/cache/',
                        'time_estimate' => 1
                    ];
                    break;
            }
        }
        
        // General rollback steps
        $plan['rollback_steps'][] = [
            'step' => 'Revert code changes',
            'command' => 'git revert HEAD --no-edit',
            'time_estimate' => 2
        ];
        
        $plan['rollback_steps'][] = [
            'step' => 'Clear all caches',
            'command' => 'php artisan optimize:clear',
            'time_estimate' => 1
        ];
        
        $plan['rollback_steps'][] = [
            'step' => 'Restart services',
            'command' => 'sudo systemctl restart php8.3-fpm && php artisan horizon:terminate',
            'time_estimate' => 1
        ];
        
        // Validation steps
        $plan['validation_steps'][] = [
            'step' => 'Run health checks',
            'command' => 'php artisan health:check',
            'time_estimate' => 1
        ];
        
        $plan['validation_steps'][] = [
            'step' => 'Test critical endpoints',
            'command' => 'php artisan test --group=critical',
            'time_estimate' => 3
        ];
        
        // Calculate total time
        $plan['estimated_time'] = array_sum(array_column($plan['preparation'], 'time_estimate')) +
                                  array_sum(array_column($plan['rollback_steps'], 'time_estimate')) +
                                  array_sum(array_column($plan['validation_steps'], 'time_estimate'));
        
        return $plan;
    }
    
    /**
     * Generate recommendations
     */
    protected function generateRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // Based on risk level
        switch ($analysis['risk_level']) {
            case 'critical':
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => 'Postpone deployment',
                    'reason' => 'Critical breaking changes detected',
                    'steps' => [
                        'Review all breaking changes',
                        'Create migration plan for affected systems',
                        'Notify all stakeholders',
                        'Schedule maintenance window'
                    ]
                ];
                break;
                
            case 'high':
                $recommendations[] = [
                    'priority' => 'high',
                    'action' => 'Implement feature flags',
                    'reason' => 'High-risk changes need gradual rollout',
                    'steps' => [
                        'Add feature flags for new functionality',
                        'Deploy with flags disabled',
                        'Enable for small percentage of users',
                        'Monitor and gradually increase'
                    ]
                ];
                break;
                
            case 'medium':
                $recommendations[] = [
                    'priority' => 'medium',
                    'action' => 'Enhanced monitoring',
                    'reason' => 'Medium risk requires close observation',
                    'steps' => [
                        'Set up additional alerts',
                        'Monitor error rates',
                        'Track performance metrics',
                        'Be ready for quick rollback'
                    ]
                ];
                break;
        }
        
        // Based on affected systems
        if (in_array('retell', $analysis['affected_systems'])) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Test phone system thoroughly',
                'reason' => 'Phone system is critical for business',
                'steps' => [
                    'Make test calls',
                    'Verify webhook processing',
                    'Check call recordings',
                    'Test error scenarios'
                ]
            ];
        }
        
        if (in_array('calcom', $analysis['affected_systems'])) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Verify calendar functionality',
                'reason' => 'Booking system must remain functional',
                'steps' => [
                    'Test appointment creation',
                    'Verify availability checks',
                    'Test rescheduling',
                    'Check calendar sync'
                ]
            ];
        }
        
        // Based on breaking changes
        if (count($analysis['breaking_changes']) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Communicate with stakeholders',
                'reason' => 'Breaking changes affect users',
                'steps' => [
                    'Document all breaking changes',
                    'Create migration guide',
                    'Notify API consumers',
                    'Provide support during transition'
                ]
            ];
        }
        
        // Testing recommendations
        if (count($analysis['automated_tests_required']) > 5) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Increase test coverage',
                'reason' => 'Many tests required indicates gaps',
                'steps' => [
                    'Write missing unit tests',
                    'Add integration tests',
                    'Create E2E test scenarios',
                    'Set up continuous testing'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Determine deployment strategy
     */
    protected function determineDeploymentStrategy(array $analysis): string
    {
        if ($analysis['risk_level'] === 'critical') {
            return 'blue_green';
        }
        
        if ($analysis['risk_level'] === 'high') {
            return 'canary';
        }
        
        if (count($analysis['affected_systems']) > 2) {
            return 'phased';
        }
        
        if (in_array('database_schema', array_column($analysis['breaking_changes'], 'type'))) {
            return 'maintenance_window';
        }
        
        return 'standard';
    }
    
    /**
     * Helper methods
     */
    
    protected function mergeAnalysisResults(array &$main, array $individual): void
    {
        if ($individual['breaking']) {
            $main['breaking_changes'][] = $individual;
        }
        
        $main['warnings'] = array_merge($main['warnings'], $individual['warnings']);
        $main['affected_components'] = array_merge($main['affected_components'], $individual['affected_components']);
        $main['automated_tests_required'] = array_merge($main['automated_tests_required'], $individual['tests_required']);
        
        // Update risk level
        $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        if ($riskLevels[$individual['risk_level']] > $riskLevels[$main['risk_level']]) {
            $main['risk_level'] = $individual['risk_level'];
        }
    }
    
    protected function findApiConsumers(string $endpoint): array
    {
        // This would search for API consumers
        // For now, return common consumers
        return ['frontend', 'mobile_app', 'third_party_integrations'];
    }
    
    protected function findAffectedModels(array $change): array
    {
        // Extract table name from migration
        if (isset($change['table'])) {
            // Convert table name to model name
            $modelName = Str::studly(Str::singular($change['table']));
            return ["App\\Models\\{$modelName}"];
        }
        
        return [];
    }
    
    protected function findMethodCallers(string $class, string $method): array
    {
        // This would search the codebase for method calls
        // For now, return empty
        return [];
    }
    
    protected function findClassDependencies(string $className): array
    {
        // This would search for class usage
        // For now, return empty
        return [];
    }
    
    protected function extractClassNameFromFile(string $file): ?string
    {
        if (!str_ends_with($file, '.php')) {
            return null;
        }
        
        $parts = explode('/', $file);
        $filename = array_pop($parts);
        $className = str_replace('.php', '', $filename);
        
        // Try to build namespace
        $appIndex = array_search('app', $parts);
        if ($appIndex !== false) {
            $namespaceParts = array_slice($parts, $appIndex + 1);
            $namespace = 'App\\' . implode('\\', $namespaceParts);
            return $namespace . '\\' . $className;
        }
        
        return $className;
    }
    
    protected function isMajorVersionChange(string $oldVersion, string $newVersion): bool
    {
        $oldMajor = explode('.', $oldVersion)[0] ?? '0';
        $newMajor = explode('.', $newVersion)[0] ?? '0';
        
        return $oldMajor !== $newMajor;
    }
    
    protected function identifyIntegration(string $file): ?string
    {
        $integrations = ['retell', 'calcom', 'stripe', 'whatsapp', 'twilio'];
        
        foreach ($integrations as $integration) {
            if (stripos($file, $integration) !== false) {
                return $integration;
            }
        }
        
        return null;
    }
    
    protected function extractMCPServerName(string $file): ?string
    {
        if (preg_match('/Services\/MCP\/(\w+)MCPServer/', $file, $matches)) {
            return strtolower($matches[1]);
        }
        
        return null;
    }
    
    protected function findMCPServerUsage(string $serverName): array
    {
        // This would search for MCP server usage
        // For now, return common services
        return ['AppointmentService', 'CustomerService', 'WebhookProcessor'];
    }
    
    /**
     * Run pre-deployment analysis
     */
    public function preDeploymentCheck(): array
    {
        // Get recent changes from git
        $changes = $this->getGitChanges();
        
        // Run analysis
        $analysis = $this->analyzeChanges($changes);
        
        // Add deployment readiness
        $analysis['deployment_ready'] = $analysis['risk_level'] !== 'critical';
        $analysis['approval_required'] = in_array($analysis['risk_level'], ['high', 'critical']);
        
        return $analysis;
    }
    
    /**
     * Get changes from git
     */
    protected function getGitChanges(): array
    {
        $changes = [];
        
        // This would parse git diff
        // For now, return empty array
        
        return $changes;
    }
}