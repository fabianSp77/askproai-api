<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ApiKeyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RotateApiKeys extends Command
{
    protected $signature = 'apikeys:rotate 
                           {--tenant= : Specific tenant ID to rotate}
                           {--force : Force rotation even if not due}
                           {--dry-run : Show what would be rotated without executing}
                           {--days=90 : Days threshold for rotation}';

    protected $description = 'Rotate API keys for tenants that are due for rotation';

    protected ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        parent::__construct();
        $this->apiKeyService = $apiKeyService;
    }

    public function handle(): int
    {
        $this->info('🔑 Starting API Key Rotation Process');
        $this->newLine();

        $daysThreshold = (int) $this->option('days');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $specificTenant = $this->option('tenant');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $query = Tenant::query();

        // Filter by specific tenant if provided
        if ($specificTenant) {
            $query->where('id', $specificTenant);
            $this->info("🎯 Targeting specific tenant: {$specificTenant}");
        }

        if (!$force) {
            // Only rotate keys that are due (older than threshold)
            $cutoffDate = Carbon::now()->subDays($daysThreshold);
            $query->where(function ($q) use ($cutoffDate) {
                $q->where('api_key_created_at', '<', $cutoffDate)
                  ->orWhereNull('api_key_created_at');
            });
            $this->info("📅 Rotating keys older than {$daysThreshold} days (before {$cutoffDate->format('Y-m-d H:i:s')})");
        } else {
            $this->warn('⚡ FORCE MODE - All matching tenants will have keys rotated');
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('✅ No tenants found that require API key rotation');
            return Command::SUCCESS;
        }

        $this->info("🔍 Found {$tenants->count()} tenant(s) for API key rotation:");
        $this->newLine();

        $rotationSummary = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($tenants as $tenant) {
            $lastRotated = $tenant->api_key_created_at 
                ? $tenant->api_key_created_at->format('Y-m-d H:i:s')
                : 'Never';

            $this->info("🏢 Tenant: {$tenant->name} (ID: {$tenant->id})");
            $this->line("   Last rotation: {$lastRotated}");

            if ($dryRun) {
                $this->line("   ✨ Would rotate API key");
                $rotationSummary[] = [
                    'tenant' => $tenant->name,
                    'id' => $tenant->id,
                    'last_rotated' => $lastRotated,
                    'status' => 'Would rotate'
                ];
                continue;
            }

            try {
                DB::beginTransaction();

                // Generate new API key
                $oldKeyPrefix = substr($tenant->api_key, 0, 8);
                $newApiKey = $this->apiKeyService->generateApiKey($tenant);

                // Log the rotation
                Log::info('API key rotated', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'old_key_prefix' => $oldKeyPrefix,
                    'new_key_prefix' => substr($newApiKey, 0, 8),
                    'rotated_by' => 'console_command',
                    'forced' => $force
                ]);

                DB::commit();

                $this->line("   ✅ API key rotated successfully");
                $this->line("   🔑 New key prefix: " . substr($newApiKey, 0, 8) . '...');

                $rotationSummary[] = [
                    'tenant' => $tenant->name,
                    'id' => $tenant->id,
                    'last_rotated' => $lastRotated,
                    'old_prefix' => $oldKeyPrefix,
                    'new_prefix' => substr($newApiKey, 0, 8),
                    'status' => 'Success'
                ];

                $successCount++;

            } catch (\Exception $e) {
                DB::rollBack();

                $this->error("   ❌ Failed to rotate API key: " . $e->getMessage());
                
                Log::error('API key rotation failed', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $rotationSummary[] = [
                    'tenant' => $tenant->name,
                    'id' => $tenant->id,
                    'last_rotated' => $lastRotated,
                    'status' => 'Failed: ' . $e->getMessage()
                ];

                $failureCount++;
            }

            $this->newLine();
        }

        // Display summary
        $this->info('📊 ROTATION SUMMARY');
        $this->line('==================');

        if ($dryRun) {
            $this->table(
                ['Tenant', 'ID', 'Last Rotated', 'Status'],
                collect($rotationSummary)->map(function ($item) {
                    return [
                        $item['tenant'],
                        $item['id'],
                        $item['last_rotated'],
                        $item['status']
                    ];
                })
            );
        } else {
            $this->table(
                ['Tenant', 'ID', 'Last Rotated', 'Old Prefix', 'New Prefix', 'Status'],
                collect($rotationSummary)->map(function ($item) {
                    return [
                        $item['tenant'],
                        $item['id'],
                        $item['last_rotated'],
                        $item['old_prefix'] ?? 'N/A',
                        $item['new_prefix'] ?? 'N/A',
                        $item['status']
                    ];
                })
            );

            $this->newLine();
            $this->info("✅ Successfully rotated: {$successCount}");
            if ($failureCount > 0) {
                $this->error("❌ Failed rotations: {$failureCount}");
            }
        }

        // Provide next steps
        if (!$dryRun && $successCount > 0) {
            $this->newLine();
            $this->warn('⚠️  IMPORTANT NEXT STEPS:');
            $this->line('1. Update client applications with new API keys');
            $this->line('2. Test API connectivity with new keys');
            $this->line('3. Monitor logs for authentication failures');
            $this->line('4. Old keys are immediately invalidated');
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Schedule this command in the Laravel scheduler
     */
    public static function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        $schedule->command('apikeys:rotate')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/api-key-rotation.log'));
    }
}