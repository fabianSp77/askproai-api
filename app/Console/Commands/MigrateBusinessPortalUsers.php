<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PortalUser;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateBusinessPortalUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:migrate-users {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Business Portal users to Admin Portal with appropriate roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting Business Portal user migration...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Step 1: Identify and update company types
        $this->updateCompanyTypes($dryRun);
        
        // Step 2: Migrate portal users
        $this->migratePortalUsers($dryRun);
        
        // Step 3: Setup default pricing for resellers
        $this->setupDefaultPricing($dryRun);
        
        $this->info('Migration completed!');
    }
    
    private function updateCompanyTypes(bool $dryRun): void
    {
        $this->info('Updating company types...');
        
        // Find companies that have child companies (resellers)
        $resellerIds = Company::whereHas('childCompanies')->pluck('id');
        
        if (!$dryRun) {
            Company::whereIn('id', $resellerIds)
                ->update(['company_type' => 'reseller']);
                
            $this->info("Updated {$resellerIds->count()} companies to reseller type");
        } else {
            $this->info("Would update {$resellerIds->count()} companies to reseller type");
        }
        
        // Update child companies to client type
        $clientCount = Company::whereNotNull('parent_company_id')->count();
        
        if (!$dryRun) {
            Company::whereNotNull('parent_company_id')
                ->update(['company_type' => 'client']);
                
            $this->info("Updated {$clientCount} companies to client type");
        } else {
            $this->info("Would update {$clientCount} companies to client type");
        }
    }
    
    private function migratePortalUsers(bool $dryRun): void
    {
        $this->info('Migrating portal users...');
        
        $portalUsers = PortalUser::with('company')->get();
        $migrated = 0;
        $skipped = 0;
        
        $bar = $this->output->createProgressBar($portalUsers->count());
        $bar->start();
        
        foreach ($portalUsers as $portalUser) {
            $bar->advance();
            
            // Skip if user already exists
            if (User::where('email', $portalUser->email)->exists()) {
                $skipped++;
                continue;
            }
            
            if (!$dryRun) {
                DB::transaction(function () use ($portalUser, &$migrated) {
                    // Create admin user
                    $user = User::create([
                        'name' => $portalUser->name,
                        'email' => $portalUser->email,
                        'password' => $portalUser->password, // Already hashed
                        'company_id' => $portalUser->company_id,
                        'email_verified_at' => $portalUser->email_verified_at,
                        'created_at' => $portalUser->created_at,
                        'updated_at' => $portalUser->updated_at,
                    ]);
                    
                    // Assign role based on portal role
                    $role = $this->mapPortalRoleToAdminRole($portalUser->role, $portalUser->company);
                    $user->assignRole($role);
                    
                    // Copy any additional settings
                    if ($portalUser->settings) {
                        $user->settings = $portalUser->settings;
                        $user->save();
                    }
                    
                    $migrated++;
                });
            } else {
                $migrated++;
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Migrated: {$migrated} users");
        $this->info("Skipped (already exist): {$skipped} users");
    }
    
    private function mapPortalRoleToAdminRole(string $portalRole, ?Company $company): string
    {
        // Check if company is a reseller
        if ($company && $company->company_type === 'reseller') {
            return match($portalRole) {
                'owner' => 'reseller_owner',
                'admin' => 'reseller_admin',
                default => 'reseller_support'
            };
        }
        
        // Regular company roles
        return match($portalRole) {
            'owner' => 'company_owner',
            'admin' => 'company_admin',
            'manager' => 'company_manager',
            default => 'company_staff'
        };
    }
    
    private function setupDefaultPricing(bool $dryRun): void
    {
        $this->info('Setting up default pricing for resellers...');
        
        $resellers = Company::where('company_type', 'reseller')
            ->with('childCompanies')
            ->get();
            
        foreach ($resellers as $reseller) {
            if ($reseller->childCompanies->isEmpty()) {
                continue;
            }
            
            $this->info("Setting up pricing for reseller: {$reseller->name}");
            
            foreach ($reseller->childCompanies as $client) {
                if (!$dryRun) {
                    // Create default pricing tiers
                    $pricingTypes = ['inbound', 'outbound'];
                    
                    foreach ($pricingTypes as $type) {
                        \App\Models\CompanyPricingTier::firstOrCreate([
                            'company_id' => $reseller->id,
                            'child_company_id' => $client->id,
                            'pricing_type' => $type,
                        ], [
                            'cost_price' => 0.30, // Default cost
                            'sell_price' => 0.40, // Default sell
                            'included_minutes' => 0,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
        
        if (!$dryRun) {
            $this->info('Default pricing created for all reseller-client relationships');
        } else {
            $this->info('Would create default pricing for all reseller-client relationships');
        }
    }
}