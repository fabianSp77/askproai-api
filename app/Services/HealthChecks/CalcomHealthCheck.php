<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;
use App\Services\CalcomService;
use App\Services\Calcom\CalcomBackwardsCompatibility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CalcomHealthCheck implements IntegrationHealthCheck
{
    protected array $diagnostics = [];
    protected float $startTime;
    
    public function __construct(
        protected ?CalcomV2Service $calcomV2Service = null,
        protected mixed $calcomService = null
    ) {
        // Services will be injected or resolved from container
        $this->calcomV2Service = $calcomV2Service ?? app(CalcomV2Service::class);
        $this->calcomService = $calcomService ?? app(CalcomService::class);
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
            // 1. Check if company has Cal.com API key
            $apiKeyCheck = $this->checkApiKey($company);
            $details['api_key'] = $apiKeyCheck;
            if (!$apiKeyCheck['valid']) {
                $issues[] = 'No valid Cal.com API key configured';
                $suggestions[] = 'Add your Cal.com API key in company settings or use OAuth';
            }
            
            // 2. Check API connectivity
            if ($apiKeyCheck['valid']) {
                $apiCheck = $this->checkApiConnectivity($company);
                $details['api_connectivity'] = $apiCheck;
                if (!$apiCheck['success']) {
                    $issues[] = 'Cannot connect to Cal.com API';
                    $suggestions[] = 'Verify API key is correct and has proper permissions';
                }
            }
            
            // 3. Check event types synchronization
            $eventTypesCheck = $this->checkEventTypes($company);
            $details['event_types'] = $eventTypesCheck;
            $metrics['event_types_count'] = $eventTypesCheck['count'];
            if ($eventTypesCheck['count'] === 0) {
                $issues[] = 'No Cal.com event types synchronized';
                $suggestions[] = 'Import event types from Cal.com or create new ones';
            } elseif ($eventTypesCheck['outdated'] > 0) {
                $issues[] = sprintf('%d event types need re-synchronization', $eventTypesCheck['outdated']);
                $suggestions[] = 'Run sync command: php artisan calcom:sync-event-types';
            }
            
            // 4. Check branch mappings
            $branchMappingCheck = $this->checkBranchMappings($company);
            $details['branch_mappings'] = $branchMappingCheck;
            if (!$branchMappingCheck['all_mapped']) {
                $issues[] = 'Some branches lack Cal.com event type mapping';
                foreach ($branchMappingCheck['unmapped_branches'] as $branch) {
                    $suggestions[] = "Map event type for branch: {$branch}";
                }
            }
            
            // 5. Check availability slots
            $availabilityCheck = $this->checkAvailability($company);
            $details['availability'] = $availabilityCheck;
            $metrics['available_slots_next_7_days'] = $availabilityCheck['total_slots'];
            if ($availabilityCheck['total_slots'] === 0) {
                $issues[] = 'No available booking slots in the next 7 days';
                $suggestions[] = 'Check staff schedules and working hours configuration';
            }
            
            // 6. Check recent booking success rate
            $bookingSuccessRate = $this->checkBookingSuccessRate($company);
            $details['booking_success_rate'] = $bookingSuccessRate;
            $metrics['booking_success_rate'] = $bookingSuccessRate['rate'];
            if ($bookingSuccessRate['rate'] < 0.9 && $bookingSuccessRate['total'] > 10) {
                $issues[] = sprintf('Low booking success rate: %.0f%%', $bookingSuccessRate['rate'] * 100);
                $suggestions[] = 'Check for API errors in logs and verify event type settings';
            }
            
            // 7. Check webhook configuration
            $webhookCheck = $this->checkWebhookConfiguration($company);
            $details['webhook_config'] = $webhookCheck;
            if (!$webhookCheck['configured']) {
                $issues[] = 'Cal.com webhooks not properly configured';
                $suggestions[] = 'Configure webhook URL in Cal.com: ' . $webhookCheck['expected_url'];
            }
            
            // Determine overall status
            $status = $this->determineOverallStatus($details, $issues);
            $message = $this->buildStatusMessage($status, $details, $issues);
            
        } catch (\Exception $e) {
            Log::error('Cal.com health check failed', [
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
        return 'Cal.com';
    }
    
    /**
     * Get check priority (higher = more important)
     */
    public function getPriority(): int
    {
        return 90; // High priority as it's critical for booking functionality
    }
    
    /**
     * Whether this check is critical for operation
     */
    public function isCritical(): bool
    {
        return true; // Calendar system is critical
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
                str_contains($issue, 'API key') => [
                    'issue' => $issue,
                    'fix' => 'Go to Settings → Integrations → Cal.com and add your API key',
                    'docs' => 'https://cal.com/docs/enterprise-features/api/api-keys',
                ],
                str_contains($issue, 'event types') => [
                    'issue' => $issue,
                    'fix' => 'Run: php artisan calcom:sync-event-types',
                    'docs' => 'https://cal.com/docs/core-features/event-types',
                ],
                str_contains($issue, 'branch') => [
                    'issue' => $issue,
                    'fix' => 'Go to Branches and assign Cal.com event types',
                    'docs' => '/admin/help/branch-event-mapping',
                ],
                str_contains($issue, 'availability') => [
                    'issue' => $issue,
                    'fix' => 'Check staff schedules and working hours',
                    'docs' => '/admin/staff?tab=schedules',
                ],
                str_contains($issue, 'webhook') => [
                    'issue' => $issue,
                    'fix' => 'Configure webhooks in Cal.com dashboard',
                    'docs' => 'https://cal.com/docs/core-features/webhooks',
                ],
                default => [
                    'issue' => $issue,
                    'fix' => 'Check system logs for more details',
                    'docs' => '/admin/logs',
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
                if (str_contains($issue, 'event types') && str_contains($issue, 'synchron')) {
                    $fixed = $this->syncEventTypes($company) || $fixed;
                }
                
                if (str_contains($issue, 'webhook')) {
                    // We can't auto-configure webhooks in Cal.com without OAuth
                    $this->diagnostics[] = 'Cannot auto-configure webhooks - manual setup required';
                }
                
            } catch (\Exception $e) {
                Log::warning('Auto-fix failed for Cal.com issue', [
                    'company_id' => $company->id,
                    'issue' => $issue,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $fixed;
    }
    
    /**
     * Check if company has API key configured
     */
    protected function checkApiKey(Company $company): array
    {
        $hasKey = !empty($company->calcom_api_key);
        $hasTeamSlug = !empty($company->calcom_team_slug);
        
        $this->diagnostics[] = $hasKey 
            ? 'API key is configured' 
            : 'API key is missing';
            
        if ($hasTeamSlug) {
            $this->diagnostics[] = "Team slug configured: {$company->calcom_team_slug}";
        }
        
        return [
            'valid' => $hasKey,
            'has_team_slug' => $hasTeamSlug,
            'configured_at' => $hasKey && $company->updated_at ? $company->updated_at->toDateTimeString() : null,
        ];
    }
    
    /**
     * Check API connectivity
     */
    protected function checkApiConnectivity(Company $company): array
    {
        try {
            $cacheKey = "calcom_api_check_{$company->id}";
            
            return Cache::remember($cacheKey, 300, function() use ($company) {
                $startTime = microtime(true);
                
                // Try to get user info as a connectivity test
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . decrypt($company->calcom_api_key),
                    'Accept' => 'application/json',
                ])
                ->timeout(5)
                ->get('https://api.cal.com/v2/me');
                
                $responseTime = (microtime(true) - $startTime) * 1000; // Convert to ms
                
                $this->diagnostics[] = sprintf(
                    'API connectivity test completed in %.0fms with status %d',
                    $responseTime,
                    $response->status()
                );
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->diagnostics[] = sprintf(
                        'Connected as: %s (%s)',
                        $data['data']['username'] ?? 'Unknown',
                        $data['data']['email'] ?? 'Unknown'
                    );
                }
                
                return [
                    'success' => $response->successful(),
                    'status_code' => $response->status(),
                    'response_time_ms' => round($responseTime),
                    'error' => $response->failed() ? $response->body() : null,
                    'user_info' => $response->successful() ? $response->json()['data'] ?? [] : null,
                ];
            });
            
        } catch (\Exception $e) {
            $this->diagnostics[] = 'API connectivity test failed: ' . $e->getMessage();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }
    
    /**
     * Check event types synchronization
     */
    protected function checkEventTypes(Company $company): array
    {
        $eventTypes = CalcomEventType::where('company_id', $company->id)->get();
        $totalCount = $eventTypes->count();
        $activeCount = $eventTypes->where('is_active', true)->count();
        
        // Check for outdated event types (not synced in last 24 hours)
        $outdatedCount = $eventTypes
            ->filter(fn($et) => $et->updated_at < now()->subDay())
            ->count();
        
        $this->diagnostics[] = sprintf(
            'Event types: %d total, %d active, %d outdated',
            $totalCount,
            $activeCount,
            $outdatedCount
        );
        
        // Check if event types have proper duration and buffer settings
        $misconfigured = $eventTypes->filter(function($et) {
            return $et->length < 15 || // Less than 15 minutes
                   empty($et->title) ||
                   empty($et->slug);
        })->count();
        
        if ($misconfigured > 0) {
            $this->diagnostics[] = "{$misconfigured} event types have invalid configuration";
        }
        
        return [
            'count' => $totalCount,
            'active' => $activeCount,
            'outdated' => $outdatedCount,
            'misconfigured' => $misconfigured,
            'last_sync' => $eventTypes->max('updated_at')?->toDateTimeString(),
        ];
    }
    
    /**
     * Check branch to event type mappings
     */
    protected function checkBranchMappings(Company $company): array
    {
        $branches = $company->branches()->where('is_active', true)->get();
        $totalBranches = $branches->count();
        $mappedBranches = $branches->whereNotNull('calcom_event_type_id')->count();
        $unmappedBranches = [];
        
        foreach ($branches as $branch) {
            if (empty($branch->calcom_event_type_id)) {
                $unmappedBranches[] = $branch->name;
                $this->diagnostics[] = "Branch '{$branch->name}' has no Cal.com event type assigned";
            }
        }
        
        $this->diagnostics[] = sprintf(
            'Branch mappings: %d/%d branches have event types assigned',
            $mappedBranches,
            $totalBranches
        );
        
        return [
            'total' => $totalBranches,
            'mapped' => $mappedBranches,
            'all_mapped' => $mappedBranches === $totalBranches && $totalBranches > 0,
            'unmapped_branches' => $unmappedBranches,
            'coverage_percentage' => $totalBranches > 0 
                ? round(($mappedBranches / $totalBranches) * 100) 
                : 0,
        ];
    }
    
    /**
     * Check availability for next 7 days
     */
    protected function checkAvailability(Company $company): array
    {
        try {
            $startDate = now()->startOfDay();
            $endDate = now()->addDays(7)->endOfDay();
            
            $totalSlots = 0;
            $branchSlots = [];
            
            // Check availability for each branch
            foreach ($company->branches()->where('is_active', true)->get() as $branch) {
                if (empty($branch->calcom_event_type_id)) {
                    continue;
                }
                
                try {
                    // Use CalcomV2Service to check availability
                    // Check each day in the range
                    $daySlots = 0;
                    $currentDate = $startDate->copy();
                    
                    while ($currentDate <= $endDate) {
                        $result = $this->calcomV2Service->checkAvailability(
                            $branch->calcom_event_type_id,
                            $currentDate->toDateString(),
                            'Europe/Berlin'
                        );
                        
                        if ($result['success'] && isset($result['slots'])) {
                            $daySlots += count($result['slots']);
                        }
                        
                        $currentDate->addDay();
                    }
                    
                    $totalSlots += $daySlots;
                    $branchSlots[$branch->name] = $daySlots;
                    
                } catch (\Exception $e) {
                    $this->diagnostics[] = "Failed to check availability for branch '{$branch->name}': " . $e->getMessage();
                    $branchSlots[$branch->name] = 0;
                }
            }
            
            $this->diagnostics[] = sprintf(
                'Availability check: %d total slots across all branches in next 7 days',
                $totalSlots
            );
            
            return [
                'total_slots' => $totalSlots,
                'branch_slots' => $branchSlots,
                'check_period_days' => 7,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ];
            
        } catch (\Exception $e) {
            $this->diagnostics[] = 'Availability check failed: ' . $e->getMessage();
            
            return [
                'total_slots' => 0,
                'branch_slots' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check recent booking success rate
     */
    protected function checkBookingSuccessRate(Company $company): array
    {
        $since = now()->subDays(7);
        
        // Check appointments created via Cal.com
        $totalBookings = $company->appointments()
            ->where('created_at', '>=', $since)
            ->whereNotNull('calcom_booking_id')
            ->count();
        
        $successfulBookings = $company->appointments()
            ->where('created_at', '>=', $since)
            ->whereNotNull('calcom_booking_id')
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();
        
        $failedBookings = $company->appointments()
            ->where('created_at', '>=', $since)
            ->whereNotNull('calcom_booking_id')
            ->whereIn('status', ['cancelled', 'no_show'])
            ->count();
        
        $rate = $totalBookings > 0 ? $successfulBookings / $totalBookings : 1.0;
        
        $this->diagnostics[] = sprintf(
            'Booking success rate (7 days): %.1f%% (%d successful, %d failed, %d total)',
            $rate * 100,
            $successfulBookings,
            $failedBookings,
            $totalBookings
        );
        
        return [
            'rate' => $rate,
            'successful' => $successfulBookings,
            'failed' => $failedBookings,
            'total' => $totalBookings,
            'period_days' => 7,
        ];
    }
    
    /**
     * Check webhook configuration
     */
    protected function checkWebhookConfiguration(Company $company): array
    {
        $expectedUrl = config('app.url') . '/api/calcom/webhook';
        
        // We can't directly check Cal.com webhook configuration without OAuth
        // So we check if we've received any webhooks recently
        $recentWebhooks = \DB::table('webhook_events')
            ->where('provider', 'calcom')
            ->where('created_at', '>=', now()->subDays(1))
            ->count();
        
        $configured = $recentWebhooks > 0;
        
        $this->diagnostics[] = $configured
            ? "Webhooks are working: {$recentWebhooks} events in last 24h"
            : "No webhook events received in last 24h";
        
        return [
            'configured' => $configured,
            'recent_events' => $recentWebhooks,
            'expected_url' => $expectedUrl,
            'last_event' => $configured ? \DB::table('webhook_events')
                ->where('provider', 'calcom')
                ->latest()
                ->value('created_at') : null,
        ];
    }
    
    /**
     * Determine overall status based on check results
     */
    protected function determineOverallStatus(array $details, array $issues): string
    {
        // Critical failures
        if (!($details['api_key']['valid'] ?? false)) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (!($details['api_connectivity']['success'] ?? false)) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (($details['event_types']['count'] ?? 0) === 0) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        // Degraded conditions
        if (!($details['branch_mappings']['all_mapped'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (($details['availability']['total_slots'] ?? 0) === 0) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (($details['booking_success_rate']['rate'] ?? 1) < 0.9) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (!($details['webhook_config']['configured'] ?? true)) {
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
            $eventTypeCount = $details['event_types']['count'] ?? 0;
            $availableSlots = $details['availability']['total_slots'] ?? 0;
            
            return sprintf(
                'Cal.com is healthy: %d event types, %d available slots in next 7 days',
                $eventTypeCount,
                $availableSlots
            );
        }
        
        if ($status === HealthCheckResult::STATUS_DEGRADED) {
            return 'Cal.com is operational but needs attention: ' . 
                   implode(', ', array_slice($issues, 0, 2));
        }
        
        return 'Cal.com integration is not functioning: ' . 
               ($issues[0] ?? 'Unknown error');
    }
    
    /**
     * Sync event types from Cal.com
     */
    protected function syncEventTypes(Company $company): bool
    {
        try {
            // Use the sync service if available
            if (class_exists(\App\Services\CalcomEventTypeSyncService::class)) {
                $syncService = app(\App\Services\CalcomEventTypeSyncService::class);
                $result = $syncService->syncForCompany($company);
                
                $this->diagnostics[] = "Synced {$result['created']} new and updated {$result['updated']} event types";
                return true;
            }
            
            // Fallback to direct API call
            $eventTypes = $this->calcomV2Service->getEventTypes();
            $synced = 0;
            
            foreach ($eventTypes as $eventType) {
                CalcomEventType::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'calcom_id' => $eventType['id'],
                    ],
                    [
                        'title' => $eventType['title'],
                        'slug' => $eventType['slug'],
                        'length' => $eventType['length'],
                        'description' => $eventType['description'] ?? null,
                        'is_active' => true,
                        'metadata' => $eventType,
                    ]
                );
                $synced++;
            }
            
            $this->diagnostics[] = "Synced {$synced} event types from Cal.com";
            return $synced > 0;
            
        } catch (\Exception $e) {
            Log::warning('Failed to sync Cal.com event types', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}