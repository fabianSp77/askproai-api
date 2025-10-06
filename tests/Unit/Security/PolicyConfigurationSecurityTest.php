<?php

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Models\{User, Company, Branch, Service, Staff, PolicyConfiguration};
use App\Filament\Resources\PolicyConfigurationResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Security test suite for SEC-002 & SEC-003 vulnerabilities.
 *
 * Tests multi-tenant isolation in:
 * - Navigation badge counts (IDOR prevention)
 * - Polymorphic relationship authorization
 * - Cross-tenant data leakage prevention
 */
class PolicyConfigurationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $userA;
    protected User $userB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test companies
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create test users
        $this->userA = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->userA->assignRole('admin');

        $this->userB = User::factory()->create(['company_id' => $this->companyB->id]);
        $this->userB->assignRole('admin');

        $this->superAdmin = User::factory()->create(['company_id' => null]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * SEC-002: Test navigation badge shows only tenant's records.
     *
     * @test
     * @group security
     * @group sec-002
     */
    public function navigation_badge_only_shows_tenant_policy_count()
    {
        // Arrange: Create policies for both companies
        PolicyConfiguration::factory()->count(3)->create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'company_id' => $this->companyA->id,
        ]);

        PolicyConfiguration::factory()->count(7)->create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'company_id' => $this->companyB->id,
        ]);

        // Act: Get badge as Company A user
        $this->actingAs($this->userA);
        $badgeA = PolicyConfigurationResource::getNavigationBadge();

        // Act: Get badge as Company B user
        $this->actingAs($this->userB);
        $badgeB = PolicyConfigurationResource::getNavigationBadge();

        // Assert: Each sees only their company's count
        $this->assertEquals('3', $badgeA, 'Company A should see 3 policies');
        $this->assertEquals('7', $badgeB, 'Company B should see 7 policies');

        // Critical: Verify NOT seeing aggregate
        $this->assertNotEquals('10', $badgeA, 'Company A should NOT see total across all tenants');
        $this->assertNotEquals('10', $badgeB, 'Company B should NOT see total across all tenants');
    }

    /**
     * SEC-002: Test super admin sees all badge counts.
     *
     * @test
     * @group security
     * @group sec-002
     */
    public function super_admin_sees_all_policy_counts_in_badge()
    {
        // Arrange
        PolicyConfiguration::factory()->count(3)->create(['company_id' => $this->companyA->id]);
        PolicyConfiguration::factory()->count(7)->create(['company_id' => $this->companyB->id]);

        // Act
        $this->actingAs($this->superAdmin);
        $badge = PolicyConfigurationResource::getNavigationBadge();

        // Assert
        $this->assertEquals('10', $badge, 'Super admin should see all policies across all companies');
    }

    /**
     * SEC-002: Test badge count is zero when no policies exist.
     *
     * @test
     * @group security
     * @group sec-002
     */
    public function navigation_badge_returns_zero_when_no_policies_exist()
    {
        // Act
        $this->actingAs($this->userA);
        $badge = PolicyConfigurationResource::getNavigationBadge();

        // Assert
        $this->assertEquals('0', $badge, 'Should return 0 when no policies exist');
    }

    /**
     * SEC-003: Test polymorphic type whitelist enforcement.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function rejects_invalid_polymorphic_configurable_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configurable type');

        // Act - Attempt to use unauthorized polymorphic type
        $this->actingAs($this->userA);
        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\User', // NOT in whitelist
            'configurable_id' => $this->userA->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);

        // Assert - Should throw exception
    }

    /**
     * SEC-003: Test allowed polymorphic types are accepted.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function accepts_whitelisted_polymorphic_configurable_types()
    {
        $this->actingAs($this->userA);

        // Test Company type
        $policyCompany = PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyCompany->id]);

        // Test Branch type
        $branch = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $policyBranch = PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branch->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 12],
        ]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyBranch->id]);

        // Test Service type
        $service = Service::factory()->create(['company_id' => $this->companyA->id]);
        $policyService = PolicyConfiguration::create([
            'configurable_type' => Service::class,
            'configurable_id' => $service->id,
            'policy_type' => 'recurring',
            'config' => ['max_per_month' => 4],
        ]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyService->id]);

        // Test Staff type
        $staff = Staff::factory()->create(['company_id' => $this->companyA->id]);
        $policyStaff = PolicyConfiguration::create([
            'configurable_type' => Staff::class,
            'configurable_id' => $staff->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 48],
        ]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyStaff->id]);
    }

    /**
     * SEC-003: Test cross-tenant assignment prevention.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function prevents_policy_assignment_to_different_company_entity()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Cannot assign to different company');

        // Arrange: Create branch for Company B
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        // Act: User from Company A attempts to assign policy to Company B's branch
        $this->actingAs($this->userA);
        PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branchB->id, // Company B's entity
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);

        // Assert: Should throw authorization exception
    }

    /**
     * SEC-003: Test super admin can assign across companies.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function super_admin_can_assign_policy_to_any_company_entity()
    {
        // Arrange: Create branch for Company B
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        // Act: Super admin assigns policy to Company B's branch
        $this->actingAs($this->superAdmin);
        $policy = PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branchB->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);

        // Assert: Should succeed for super admin
        $this->assertDatabaseHas('policy_configurations', [
            'id' => $policy->id,
            'configurable_type' => Branch::class,
            'configurable_id' => $branchB->id,
        ]);
    }

    /**
     * SEC-003: Test Company type polymorphic relationship authorization.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function validates_company_type_polymorphic_relationship_correctly()
    {
        $this->expectException(AuthorizationException::class);

        // Act: User from Company A attempts to assign policy to Company B directly
        $this->actingAs($this->userA);
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id, // Different company
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);

        // Assert: Should throw authorization exception
    }

    /**
     * SEC-003: Test user can assign policy to own company.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function allows_policy_assignment_to_own_company_entities()
    {
        // Arrange
        $branch = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $service = Service::factory()->create(['company_id' => $this->companyA->id]);
        $staff = Staff::factory()->create(['company_id' => $this->companyA->id]);

        // Act: User from Company A assigns policies to own entities
        $this->actingAs($this->userA);

        $policyCompany = PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24],
        ]);

        $policyBranch = PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branch->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 12],
        ]);

        $policyService = PolicyConfiguration::create([
            'configurable_type' => Service::class,
            'configurable_id' => $service->id,
            'policy_type' => 'recurring',
            'config' => ['max_per_month' => 4],
        ]);

        $policyStaff = PolicyConfiguration::create([
            'configurable_type' => Staff::class,
            'configurable_id' => $staff->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 48],
        ]);

        // Assert: All should succeed
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyCompany->id]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyBranch->id]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyService->id]);
        $this->assertDatabaseHas('policy_configurations', ['id' => $policyStaff->id]);
    }

    /**
     * Test CompanyScope isolation for policy queries.
     *
     * @test
     * @group security
     */
    public function company_scope_isolates_policy_queries_correctly()
    {
        // Arrange
        PolicyConfiguration::factory()->count(3)->create(['company_id' => $this->companyA->id]);
        PolicyConfiguration::factory()->count(5)->create(['company_id' => $this->companyB->id]);

        // Act: Query as Company A user
        $this->actingAs($this->userA);
        $policiesA = PolicyConfiguration::all();

        // Act: Query as Company B user
        $this->actingAs($this->userB);
        $policiesB = PolicyConfiguration::all();

        // Assert: Scope filters correctly
        $this->assertCount(3, $policiesA, 'Company A should see 3 policies');
        $this->assertCount(5, $policiesB, 'Company B should see 5 policies');

        // Verify correct company_id
        $this->assertTrue(
            $policiesA->every(fn($p) => $p->company_id === $this->companyA->id),
            'All policies should belong to Company A'
        );
        $this->assertTrue(
            $policiesB->every(fn($p) => $p->company_id === $this->companyB->id),
            'All policies should belong to Company B'
        );
    }

    /**
     * Test badge cache isolation by company_id.
     *
     * @test
     * @group security
     * @group sec-002
     */
    public function badge_cache_is_isolated_by_company_id()
    {
        // Arrange
        PolicyConfiguration::factory()->count(3)->create(['company_id' => $this->companyA->id]);
        PolicyConfiguration::factory()->count(7)->create(['company_id' => $this->companyB->id]);

        // Act: Get badge as both users (creates cache entries)
        $this->actingAs($this->userA);
        $badgeA1 = PolicyConfigurationResource::getNavigationBadge();

        $this->actingAs($this->userB);
        $badgeB1 = PolicyConfigurationResource::getNavigationBadge();

        // Create more policies
        PolicyConfiguration::factory()->count(2)->create(['company_id' => $this->companyA->id]);

        // Get cached badges (should still show old values due to cache)
        $this->actingAs($this->userA);
        $badgeA2 = PolicyConfigurationResource::getNavigationBadge();

        $this->actingAs($this->userB);
        $badgeB2 = PolicyConfigurationResource::getNavigationBadge();

        // Assert: Cache is isolated
        $this->assertEquals('3', $badgeA1, 'Initial Company A badge');
        $this->assertEquals('7', $badgeB1, 'Initial Company B badge');
        $this->assertEquals('3', $badgeA2, 'Cached Company A badge (unchanged)');
        $this->assertEquals('7', $badgeB2, 'Cached Company B badge (unchanged)');

        // Clear cache and verify update
        PolicyConfigurationResource::clearBadgeCache();
        $this->actingAs($this->userA);
        $badgeA3 = PolicyConfigurationResource::getNavigationBadge();

        $this->assertEquals('5', $badgeA3, 'Company A badge after cache clear shows updated count');
    }

    /**
     * Test policy type validation.
     *
     * @test
     * @group security
     */
    public function validates_policy_type_enum()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid policy type');

        $this->actingAs($this->userA);
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'invalid_type', // Not in POLICY_TYPES enum
            'config' => ['hours_before' => 24],
        ]);
    }

    /**
     * Test polymorphic relationship loading respects tenant isolation.
     *
     * @test
     * @group security
     * @group sec-003
     */
    public function polymorphic_configurable_relationship_respects_tenant_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        $policyA = PolicyConfiguration::factory()->create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branchA->id,
            'company_id' => $this->companyA->id,
        ]);

        $policyB = PolicyConfiguration::factory()->create([
            'configurable_type' => Branch::class,
            'configurable_id' => $branchB->id,
            'company_id' => $this->companyB->id,
        ]);

        // Act: Load policies as Company A user
        $this->actingAs($this->userA);
        $policies = PolicyConfiguration::with('configurable')->get();

        // Assert: Only see Company A's policies and entities
        $this->assertCount(1, $policies);
        $this->assertEquals($policyA->id, $policies->first()->id);
        $this->assertEquals($branchA->id, $policies->first()->configurable->id);
        $this->assertEquals($this->companyA->id, $policies->first()->configurable->company_id);
    }
}
