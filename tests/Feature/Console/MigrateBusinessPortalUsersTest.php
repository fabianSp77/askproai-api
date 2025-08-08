<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\Company;
use App\Models\CompanyPricingTier;
use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class MigrateBusinessPortalUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'reseller_owner']);
        Role::create(['name' => 'reseller_admin']);
        Role::create(['name' => 'reseller_support']);
        Role::create(['name' => 'company_owner']);
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'company_manager']);
        Role::create(['name' => 'company_staff']);
    }

    public function test_dry_run_mode_does_not_make_changes()
    {
        $reseller = Company::factory()->create();
        $client = Company::factory()->create(['parent_company_id' => $reseller->id]);
        
        PortalUser::factory()->create([
            'company_id' => $reseller->id,
            'role' => 'owner'
        ]);

        $initialCompanies = Company::count();
        $initialUsers = User::count();
        $initialPortalUsers = PortalUser::count();
        $initialPricingTiers = CompanyPricingTier::count();

        $this->artisan('portal:migrate-users --dry-run')
            ->expectsOutput('DRY RUN MODE - No changes will be made')
            ->assertExitCode(0);

        // No changes should be made
        $this->assertEquals($initialCompanies, Company::count());
        $this->assertEquals($initialUsers, User::count());
        $this->assertEquals($initialPortalUsers, PortalUser::count());
        $this->assertEquals($initialPricingTiers, CompanyPricingTier::count());
    }

    public function test_updates_company_types_correctly()
    {
        $reseller = Company::factory()->create(['company_type' => null]);
        $client1 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => null
        ]);
        $client2 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => null
        ]);
        $standalone = Company::factory()->create(['company_type' => null]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        $reseller->refresh();
        $client1->refresh();
        $client2->refresh();
        $standalone->refresh();

        $this->assertEquals('reseller', $reseller->company_type);
        $this->assertEquals('client', $client1->company_type);
        $this->assertEquals('client', $client2->company_type);
        $this->assertNull($standalone->company_type); // No children, should remain unchanged
    }

    public function test_migrates_portal_users_with_correct_roles()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        // Create portal users
        $resellerOwner = PortalUser::factory()->create([
            'company_id' => $reseller->id,
            'role' => 'owner',
            'email' => 'reseller@example.com'
        ]);

        $resellerAdmin = PortalUser::factory()->create([
            'company_id' => $reseller->id,
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $clientOwner = PortalUser::factory()->create([
            'company_id' => $client->id,
            'role' => 'owner',
            'email' => 'client@example.com'
        ]);

        $clientManager = PortalUser::factory()->create([
            'company_id' => $client->id,
            'role' => 'manager',
            'email' => 'manager@example.com'
        ]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        // Check migrated users have correct roles
        $migratedResellerOwner = User::where('email', 'reseller@example.com')->first();
        $this->assertNotNull($migratedResellerOwner);
        $this->assertTrue($migratedResellerOwner->hasRole('reseller_owner'));

        $migratedResellerAdmin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($migratedResellerAdmin);
        $this->assertTrue($migratedResellerAdmin->hasRole('reseller_admin'));

        $migratedClientOwner = User::where('email', 'client@example.com')->first();
        $this->assertNotNull($migratedClientOwner);
        $this->assertTrue($migratedClientOwner->hasRole('company_owner'));

        $migratedClientManager = User::where('email', 'manager@example.com')->first();
        $this->assertNotNull($migratedClientManager);
        $this->assertTrue($migratedClientManager->hasRole('company_manager'));
    }

    public function test_skips_existing_users()
    {
        $company = Company::factory()->create();
        
        // Create existing user in admin system
        User::factory()->create(['email' => 'existing@example.com']);
        
        // Create portal user with same email
        PortalUser::factory()->create([
            'company_id' => $company->id,
            'email' => 'existing@example.com',
            'role' => 'owner'
        ]);

        $initialUserCount = User::count();

        $this->artisan('portal:migrate-users')
            ->expectsOutput('Skipped (already exist): 1 users')
            ->assertExitCode(0);

        // User count should remain the same
        $this->assertEquals($initialUserCount, User::count());
    }

    public function test_preserves_portal_user_data()
    {
        $company = Company::factory()->create();
        $settings = ['theme' => 'dark', 'notifications' => true];
        $createdAt = now()->subDays(30);
        
        $portalUser = PortalUser::factory()->create([
            'company_id' => $company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'owner',
            'settings' => $settings,
            'email_verified_at' => now()->subDays(25),
            'created_at' => $createdAt
        ]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        $migratedUser = User::where('email', 'john@example.com')->first();
        
        $this->assertNotNull($migratedUser);
        $this->assertEquals('John Doe', $migratedUser->name);
        $this->assertEquals('john@example.com', $migratedUser->email);
        $this->assertEquals($company->id, $migratedUser->company_id);
        $this->assertEquals($settings, $migratedUser->settings);
        $this->assertNotNull($migratedUser->email_verified_at);
        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $migratedUser->created_at->format('Y-m-d H:i:s'));
    }

    public function test_creates_default_pricing_for_resellers()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client1 = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);
        $client2 = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        // Check that pricing tiers were created
        $inboundPricing1 = CompanyPricingTier::where('company_id', $reseller->id)
            ->where('child_company_id', $client1->id)
            ->where('pricing_type', 'inbound')
            ->first();

        $outboundPricing1 = CompanyPricingTier::where('company_id', $reseller->id)
            ->where('child_company_id', $client1->id)
            ->where('pricing_type', 'outbound')
            ->first();

        $this->assertNotNull($inboundPricing1);
        $this->assertNotNull($outboundPricing1);
        $this->assertEquals(0.30, $inboundPricing1->cost_price);
        $this->assertEquals(0.40, $inboundPricing1->sell_price);
        $this->assertTrue($inboundPricing1->is_active);

        // Check client2 also has pricing
        $inboundPricing2 = CompanyPricingTier::where('company_id', $reseller->id)
            ->where('child_company_id', $client2->id)
            ->where('pricing_type', 'inbound')
            ->first();

        $this->assertNotNull($inboundPricing2);
    }

    public function test_skips_pricing_setup_for_resellers_without_clients()
    {
        $resellerWithoutClients = Company::factory()->create(['company_type' => 'reseller']);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        $pricingCount = CompanyPricingTier::where('company_id', $resellerWithoutClients->id)->count();
        $this->assertEquals(0, $pricingCount);
    }

    public function test_maps_unknown_portal_roles_to_defaults()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create(['company_type' => 'client']);

        // Create portal users with unknown roles
        $unknownResellerRole = PortalUser::factory()->create([
            'company_id' => $reseller->id,
            'role' => 'unknown_role',
            'email' => 'unknown-reseller@example.com'
        ]);

        $unknownClientRole = PortalUser::factory()->create([
            'company_id' => $client->id,
            'role' => 'unknown_role',
            'email' => 'unknown-client@example.com'
        ]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        $migratedResellerUser = User::where('email', 'unknown-reseller@example.com')->first();
        $migratedClientUser = User::where('email', 'unknown-client@example.com')->first();

        $this->assertTrue($migratedResellerUser->hasRole('reseller_support'));
        $this->assertTrue($migratedClientUser->hasRole('company_staff'));
    }

    public function test_command_provides_progress_feedback()
    {
        $company = Company::factory()->create();
        PortalUser::factory()->count(5)->create(['company_id' => $company->id]);

        $this->artisan('portal:migrate-users')
            ->expectsOutput('Starting Business Portal user migration...')
            ->expectsOutput('Updating company types...')
            ->expectsOutput('Migrating portal users...')
            ->expectsOutput('Setting up default pricing for resellers...')
            ->expectsOutput('Migration completed!')
            ->assertExitCode(0);
    }

    public function test_migration_is_transactional_per_user()
    {
        $company = Company::factory()->create();
        
        // Create a portal user that would cause a constraint violation
        $portalUser = PortalUser::factory()->create([
            'company_id' => $company->id,
            'email' => 'test@example.com'
        ]);

        // Create an admin user with the same email to cause a conflict
        User::factory()->create(['email' => 'test@example.com']);

        $initialUserCount = User::count();

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        // The conflicting user should be skipped, count should remain the same
        $this->assertEquals($initialUserCount, User::count());
    }

    public function test_handles_portal_users_without_company()
    {
        // Create portal user without company (orphaned)
        PortalUser::factory()->create([
            'company_id' => null,
            'email' => 'orphaned@example.com'
        ]);

        $this->artisan('portal:migrate-users')
            ->assertExitCode(0);

        // The orphaned user should not be migrated
        $this->assertNull(User::where('email', 'orphaned@example.com')->first());
    }
}