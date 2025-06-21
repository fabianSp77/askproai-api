<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\PhoneNumberResolver;
use App\Services\Booking\HotlineRouter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhoneRoutingHealthCheck implements IntegrationHealthCheck
{
    protected array $diagnostics = [];
    protected float $startTime;
    
    public function __construct(
        protected ?PhoneNumberResolver $phoneNumberResolver = null,
        protected ?HotlineRouter $hotlineRouter = null
    ) {
        // Services will be injected or resolved from container
        $this->phoneNumberResolver = $phoneNumberResolver ?? app(PhoneNumberResolver::class);
        $this->hotlineRouter = $hotlineRouter ?? app(HotlineRouter::class);
    }
    
    /**
     * Run the health check for a specific company
     */
    public function check(Company $company): HealthCheckResult
    {
        $this->startTime = microtime(true);
        $this->diagnostics = [];
        
        $details = [];
        $issues = [];
        $suggestions = [];
        $metrics = [];
        
        try {
            // 1. Check phone number configuration
            $phoneNumberCheck = $this->checkPhoneNumbers($company);
            $details['phone_numbers'] = $phoneNumberCheck;
            $metrics['total_phone_numbers'] = $phoneNumberCheck['total'];
            if ($phoneNumberCheck['total'] === 0) {
                $issues[] = 'No phone numbers configured';
                $suggestions[] = 'Configure at least one phone number for the company';
            }
            
            // 2. Check branch coverage
            $branchCoverageCheck = $this->checkBranchCoverage($company);
            $details['branch_coverage'] = $branchCoverageCheck;
            $metrics['branch_coverage_percentage'] = $branchCoverageCheck['coverage_percentage'];
            if (!$branchCoverageCheck['all_covered']) {
                $issues[] = 'Some branches have no phone numbers assigned';
                foreach ($branchCoverageCheck['uncovered_branches'] as $branch) {
                    $suggestions[] = "Assign phone number to branch: {$branch}";
                }
            }
            
            // 3. Check hotline configuration
            $hotlineCheck = $this->checkHotlineConfiguration($company);
            $details['hotline'] = $hotlineCheck;
            if ($hotlineCheck['has_hotline'] && !$hotlineCheck['properly_configured']) {
                $issues[] = 'Hotline is not properly configured';
                $suggestions[] = 'Configure hotline routing strategy and branch mappings';
            }
            
            // 4. Check routing consistency
            $routingConsistencyCheck = $this->checkRoutingConsistency($company);
            $details['routing_consistency'] = $routingConsistencyCheck;
            if (!$routingConsistencyCheck['consistent']) {
                foreach ($routingConsistencyCheck['inconsistencies'] as $inconsistency) {
                    $issues[] = $inconsistency;
                }
                $suggestions[] = 'Review and fix routing configuration inconsistencies';
            }
            
            // 5. Check phone number format validity
            $formatValidityCheck = $this->checkPhoneNumberFormats($company);
            $details['format_validity'] = $formatValidityCheck;
            if ($formatValidityCheck['invalid_count'] > 0) {
                $issues[] = sprintf('%d phone numbers have invalid format', $formatValidityCheck['invalid_count']);
                $suggestions[] = 'Ensure all phone numbers are in international format (e.g., +49...)';
            }
            
            // 6. Test routing resolution
            $routingTestCheck = $this->testRoutingResolution($company);
            $details['routing_test'] = $routingTestCheck;
            $metrics['routing_success_rate'] = $routingTestCheck['success_rate'];
            if ($routingTestCheck['success_rate'] < 1.0) {
                $issues[] = 'Some phone numbers cannot be resolved to branches';
                $suggestions[] = 'Check phone number assignments and routing configuration';
            }
            
            // Determine overall status
            $status = $this->determineOverallStatus($details, $issues);
            $message = $this->buildStatusMessage($status, $details, $issues);
            
        } catch (\Exception $e) {
            Log::error('Phone routing health check failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return HealthCheckResult::unhealthy(
                'Health check failed: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                ['Contact support if this persists']
            );
        }
        
        $responseTime = microtime(true) - $this->startTime;
        
        return new HealthCheckResult(
            status: $status,
            message: $message,
            details: $details,
            metrics: $metrics,
            responseTime: $responseTime,
            issues: $issues,
            suggestions: $suggestions
        );
    }
    
    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'Phone Routing';
    }
    
    /**
     * Get check priority (higher = more important)
     */
    public function getPriority(): int
    {
        return 80; // High priority but lower than Retell/Cal.com
    }
    
    /**
     * Whether this check is critical for operation
     */
    public function isCritical(): bool
    {
        return true; // Phone routing is critical for customer calls
    }
    
    /**
     * Get detailed diagnostics
     */
    public function getDiagnostics(): array
    {
        return $this->diagnostics;
    }
    
    /**
     * Get suggested fixes for common issues
     */
    public function getSuggestedFixes(array $issues): array
    {
        $fixes = [];
        
        foreach ($issues as $issue) {
            $fixes[] = match(true) {
                str_contains($issue, 'No phone numbers') => [
                    'issue' => $issue,
                    'fix' => 'Go to Settings → Phone Numbers and add at least one number',
                    'docs' => '/admin/help/phone-configuration',
                ],
                str_contains($issue, 'branches have no phone') => [
                    'issue' => $issue,
                    'fix' => 'Assign phone numbers to all active branches',
                    'docs' => '/admin/branches',
                ],
                str_contains($issue, 'Hotline') => [
                    'issue' => $issue,
                    'fix' => 'Configure hotline routing in Settings → Phone Configuration',
                    'docs' => '/admin/help/hotline-setup',
                ],
                str_contains($issue, 'invalid format') => [
                    'issue' => $issue,
                    'fix' => 'Update phone numbers to international format (+49...)',
                    'docs' => '/admin/help/phone-formats',
                ],
                str_contains($issue, 'cannot be resolved') => [
                    'issue' => $issue,
                    'fix' => 'Check phone number assignments and ensure all numbers are linked to branches',
                    'docs' => '/admin/phone-numbers',
                ],
                default => [
                    'issue' => $issue,
                    'fix' => 'Review phone routing configuration',
                    'docs' => '/admin/settings/phone-routing',
                ],
            };
        }
        
        return $fixes;
    }
    
    /**
     * Run automatic fixes if possible
     */
    public function attemptAutoFix(Company $company, array $issues): bool
    {
        $fixed = false;
        
        foreach ($issues as $issue) {
            try {
                if (str_contains($issue, 'invalid format')) {
                    $fixed = $this->fixPhoneNumberFormats($company) || $fixed;
                }
                
                // Most phone routing issues require manual configuration
                
            } catch (\Exception $e) {
                Log::warning('Auto-fix failed for phone routing issue', [
                    'company_id' => $company->id,
                    'issue' => $issue,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $fixed;
    }
    
    /**
     * Check phone number configuration
     */
    protected function checkPhoneNumbers(Company $company): array
    {
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)->get();
        $totalCount = $phoneNumbers->count();
        $activeCount = $phoneNumbers->where('active', true)->count();
        
        // Count by type
        $hotlineCount = $phoneNumbers->where('type', 'hotline')->count();
        $directCount = $phoneNumbers->where('type', 'direct')->count();
        $mobileCount = $phoneNumbers->where('type', 'mobile')->count();
        
        // Check for duplicates
        $duplicates = $phoneNumbers->groupBy('number')
            ->filter(fn($group) => $group->count() > 1)
            ->keys();
        
        if ($duplicates->count() > 0) {
            $this->diagnostics[] = "Duplicate phone numbers found: " . $duplicates->implode(', ');
        }
        
        $this->diagnostics[] = sprintf(
            'Phone numbers: %d total, %d active (%d hotline, %d direct, %d mobile)',
            $totalCount,
            $activeCount,
            $hotlineCount,
            $directCount,
            $mobileCount
        );
        
        return [
            'total' => $totalCount,
            'active' => $activeCount,
            'by_type' => [
                'hotline' => $hotlineCount,
                'direct' => $directCount,
                'mobile' => $mobileCount,
            ],
            'duplicates' => $duplicates->toArray(),
            'has_duplicates' => $duplicates->count() > 0,
        ];
    }
    
    /**
     * Check branch coverage
     */
    protected function checkBranchCoverage(Company $company): array
    {
        $branches = $company->branches()->where('is_active', true)->get();
        $totalBranches = $branches->count();
        $coveredBranches = 0;
        $uncoveredBranches = [];
        
        foreach ($branches as $branch) {
            $hasPhoneNumber = PhoneNumber::where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->where('active', true)
                ->exists();
            
            if ($hasPhoneNumber) {
                $coveredBranches++;
            } else {
                $uncoveredBranches[] = $branch->name;
                $this->diagnostics[] = "Branch '{$branch->name}' has no phone number assigned";
            }
        }
        
        $coveragePercentage = $totalBranches > 0 
            ? round(($coveredBranches / $totalBranches) * 100) 
            : 0;
        
        $this->diagnostics[] = sprintf(
            'Branch coverage: %d/%d branches have phone numbers (%.0f%%)',
            $coveredBranches,
            $totalBranches,
            $coveragePercentage
        );
        
        return [
            'total' => $totalBranches,
            'covered' => $coveredBranches,
            'all_covered' => $coveredBranches === $totalBranches && $totalBranches > 0,
            'uncovered_branches' => $uncoveredBranches,
            'coverage_percentage' => $coveragePercentage,
        ];
    }
    
    /**
     * Check hotline configuration
     */
    protected function checkHotlineConfiguration(Company $company): array
    {
        $hotlineNumbers = PhoneNumber::where('company_id', $company->id)
            ->where('type', 'hotline')
            ->where('active', true)
            ->get();
        
        if ($hotlineNumbers->isEmpty()) {
            return [
                'has_hotline' => false,
                'properly_configured' => true, // No hotline is valid
                'hotline_count' => 0,
            ];
        }
        
        $properlyConfigured = true;
        $configurationIssues = [];
        
        foreach ($hotlineNumbers as $hotline) {
            $metadata = $hotline->metadata ?? [];
            
            // Check routing strategy
            if (empty($metadata['routing_strategy'])) {
                $properlyConfigured = false;
                $configurationIssues[] = "Hotline {$hotline->number} has no routing strategy";
            }
            
            // Check voice menu configuration if applicable
            if (($metadata['routing_strategy'] ?? '') === 'voice_menu') {
                if (empty($metadata['voice_keywords'])) {
                    $properlyConfigured = false;
                    $configurationIssues[] = "Hotline {$hotline->number} has voice menu but no keywords configured";
                }
            }
            
            // Check if hotline has branch mappings
            $hasMappings = !empty($metadata['branch_mappings']) || !empty($metadata['voice_keywords']);
            if (!$hasMappings) {
                $properlyConfigured = false;
                $configurationIssues[] = "Hotline {$hotline->number} has no branch mappings";
            }
        }
        
        foreach ($configurationIssues as $issue) {
            $this->diagnostics[] = $issue;
        }
        
        return [
            'has_hotline' => true,
            'hotline_count' => $hotlineNumbers->count(),
            'properly_configured' => $properlyConfigured,
            'configuration_issues' => $configurationIssues,
            'routing_strategies' => $hotlineNumbers->pluck('metadata.routing_strategy')->unique()->filter()->values()->toArray(),
        ];
    }
    
    /**
     * Check routing consistency
     */
    protected function checkRoutingConsistency(Company $company): array
    {
        $inconsistencies = [];
        $consistent = true;
        
        // Check if branches referenced in phone numbers exist and are active
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)
            ->whereNotNull('branch_id')
            ->get();
        
        foreach ($phoneNumbers as $phoneNumber) {
            $branch = Branch::find($phoneNumber->branch_id);
            
            if (!$branch) {
                $inconsistencies[] = "Phone number {$phoneNumber->number} references non-existent branch";
                $consistent = false;
            } elseif (!$branch->is_active) {
                $inconsistencies[] = "Phone number {$phoneNumber->number} assigned to inactive branch: {$branch->name}";
                $consistent = false;
            } elseif ($branch->company_id !== $company->id) {
                $inconsistencies[] = "Phone number {$phoneNumber->number} assigned to branch from different company";
                $consistent = false;
            }
        }
        
        // Check hotline voice keywords reference valid branches
        $hotlines = PhoneNumber::where('company_id', $company->id)
            ->where('type', 'hotline')
            ->get();
        
        foreach ($hotlines as $hotline) {
            $voiceKeywords = $hotline->metadata['voice_keywords'] ?? [];
            $activeBranchNames = $company->branches()
                ->where('active', true)
                ->pluck('name')
                ->toArray();
            
            foreach ($voiceKeywords as $branchName => $keywords) {
                if (!in_array($branchName, $activeBranchNames)) {
                    $inconsistencies[] = "Hotline voice menu references invalid branch: {$branchName}";
                    $consistent = false;
                }
            }
        }
        
        $this->diagnostics[] = $consistent 
            ? 'Routing configuration is consistent' 
            : 'Found ' . count($inconsistencies) . ' routing inconsistencies';
        
        return [
            'consistent' => $consistent,
            'inconsistencies' => $inconsistencies,
            'checks_performed' => [
                'branch_existence' => true,
                'branch_active_status' => true,
                'company_ownership' => true,
                'voice_menu_mappings' => true,
            ],
        ];
    }
    
    /**
     * Check phone number format validity
     */
    protected function checkPhoneNumberFormats(Company $company): array
    {
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)->get();
        $invalidNumbers = [];
        $validCount = 0;
        
        foreach ($phoneNumbers as $phoneNumber) {
            // Check if number starts with + and country code
            if (!preg_match('/^\+\d{1,4}\d{5,}$/', $phoneNumber->number)) {
                $invalidNumbers[] = [
                    'number' => $phoneNumber->number,
                    'issue' => 'Invalid international format',
                    'expected' => 'Should start with + and country code (e.g., +49...)',
                ];
            } else {
                $validCount++;
            }
        }
        
        $this->diagnostics[] = sprintf(
            'Phone number format check: %d valid, %d invalid',
            $validCount,
            count($invalidNumbers)
        );
        
        return [
            'total' => $phoneNumbers->count(),
            'valid_count' => $validCount,
            'invalid_count' => count($invalidNumbers),
            'invalid_numbers' => $invalidNumbers,
            'all_valid' => count($invalidNumbers) === 0,
        ];
    }
    
    /**
     * Test routing resolution
     */
    protected function testRoutingResolution(Company $company): array
    {
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)
            ->where('active', true)
            ->get();
        
        $totalTests = $phoneNumbers->count();
        $successfulTests = 0;
        $failedTests = [];
        
        foreach ($phoneNumbers as $phoneNumber) {
            try {
                // Create a mock webhook data to use the resolver
                $mockWebhookData = [
                    'to' => $phoneNumber->number,
                    'company_id' => $company->id
                ];
                $resolution = $this->phoneNumberResolver->resolveFromWebhook($mockWebhookData);
                
                if ($resolution && $resolution['branch_id']) {
                    $successfulTests++;
                    $branch = Branch::find($resolution['branch_id']);
                    $this->diagnostics[] = sprintf(
                        "✓ %s resolves to branch: %s",
                        $phoneNumber->number,
                        $branch ? $branch->name : 'Unknown'
                    );
                } else {
                    $failedTests[] = [
                        'number' => $phoneNumber->number,
                        'reason' => 'Could not resolve to any branch',
                    ];
                }
                
            } catch (\Exception $e) {
                $failedTests[] = [
                    'number' => $phoneNumber->number,
                    'reason' => $e->getMessage(),
                ];
            }
        }
        
        $successRate = $totalTests > 0 ? $successfulTests / $totalTests : 1.0;
        
        $this->diagnostics[] = sprintf(
            'Routing resolution test: %.0f%% success rate (%d/%d)',
            $successRate * 100,
            $successfulTests,
            $totalTests
        );
        
        return [
            'total_tests' => $totalTests,
            'successful' => $successfulTests,
            'failed' => count($failedTests),
            'success_rate' => $successRate,
            'failed_tests' => $failedTests,
        ];
    }
    
    /**
     * Determine overall status based on check results
     */
    protected function determineOverallStatus(array $details, array $issues): string
    {
        // Critical failures
        if (($details['phone_numbers']['total'] ?? 0) === 0) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (($details['branch_coverage']['coverage_percentage'] ?? 0) === 0) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (($details['routing_test']['success_rate'] ?? 0) < 0.5) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        // Degraded conditions
        if (!($details['branch_coverage']['all_covered'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (!($details['routing_consistency']['consistent'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (!($details['format_validity']['all_valid'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if ($details['hotline']['has_hotline'] && !($details['hotline']['properly_configured'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        // Check if there are any issues
        if (count($issues) > 0) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        return HealthCheckResult::STATUS_HEALTHY;
    }
    
    /**
     * Build status message
     */
    protected function buildStatusMessage(string $status, array $details, array $issues): string
    {
        if ($status === HealthCheckResult::STATUS_HEALTHY) {
            $phoneCount = $details['phone_numbers']['total'] ?? 0;
            $coveragePercentage = $details['branch_coverage']['coverage_percentage'] ?? 0;
            
            return sprintf(
                'Phone routing is healthy: %d numbers configured, %.0f%% branch coverage',
                $phoneCount,
                $coveragePercentage
            );
        }
        
        if ($status === HealthCheckResult::STATUS_DEGRADED) {
            return 'Phone routing needs attention: ' . 
                   implode(', ', array_slice($issues, 0, 2));
        }
        
        return 'Phone routing is not functioning: ' . 
               ($issues[0] ?? 'Unknown error');
    }
    
    /**
     * Fix phone number formats
     */
    protected function fixPhoneNumberFormats(Company $company): bool
    {
        $fixed = false;
        $phoneNumbers = PhoneNumber::where('company_id', $company->id)->get();
        
        foreach ($phoneNumbers as $phoneNumber) {
            $originalNumber = $phoneNumber->number;
            $cleanedNumber = $this->cleanPhoneNumber($originalNumber);
            
            if ($cleanedNumber !== $originalNumber) {
                $phoneNumber->update(['number' => $cleanedNumber]);
                $fixed = true;
                
                $this->diagnostics[] = "Fixed phone number format: {$originalNumber} → {$cleanedNumber}";
            }
        }
        
        return $fixed;
    }
    
    /**
     * Clean phone number to international format
     */
    protected function cleanPhoneNumber(string $number): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $number);
        
        // If number doesn't start with +, assume German number
        if (!str_starts_with($cleaned, '+')) {
            if (str_starts_with($cleaned, '0')) {
                // Remove leading 0 for German numbers
                $cleaned = substr($cleaned, 1);
            }
            $cleaned = '+49' . $cleaned;
        }
        
        return $cleaned;
    }
}