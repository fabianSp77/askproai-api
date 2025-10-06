<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\BookingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-Tenant Data Leakage Test Suite
 *
 * Tests advanced attack vectors for cross-tenant data access:
 * - Relationship-based data leaks
 * - Query builder bypass attempts
 * - Eager loading isolation
 * - Mass assignment vulnerabilities
 */
class CrossTenantDataLeakageTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two isolated companies
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create users for each company
        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'user@companyA.com',
        ]);

        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'user@companyB.com',
        ]);
    }

    /**
     * @test
     * Test that relationship queries respect company scope
     */
    public function it_prevents_data_leakage_through_relationships(): void
    {
        // Create policies for both companies
        $policyA = Policy::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Policy A',
        ]);

        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Policy B',
        ]);

        $this->actingAs($this->userA);

        // Attempt to access Company B's policies through relationship
        $policies = $this->companyA->policies;

        $this->assertCount(1, $policies);
        $this->assertEquals($policyA->id, $policies->first()->id);
        $this->assertNotContains($policyB->id, $policies->pluck('id'));
    }

    /**
     * @test
     * Test query builder with raw where clauses respects scope
     */
    public function it_prevents_query_builder_bypass_with_raw_where(): void
    {
        Policy::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Policy A',
        ]);

        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Policy B',
        ]);

        $this->actingAs($this->userA);

        // Attempt to bypass scope with raw where clause
        $result = Policy::whereRaw("id = ?", [$policyB->id])->first();

        // Should return null because company scope is applied
        $this->assertNull($result);
    }

    /**
     * @test
     * Test eager loading respects company scope
     */
    public function it_prevents_data_leakage_through_eager_loading(): void
    {
        // Create booking types with policies
        $bookingTypeA = BookingType::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $bookingTypeB = BookingType::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        Policy::factory()->create([
            'company_id' => $this->companyA->id,
            'booking_type_id' => $bookingTypeA->id,
        ]);

        Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'booking_type_id' => $bookingTypeB->id,
        ]);

        $this->actingAs($this->userA);

        // Eager load booking types with policies
        $bookingTypes = BookingType::with('policies')->get();

        $this->assertCount(1, $bookingTypes);
        $this->assertEquals($bookingTypeA->id, $bookingTypes->first()->id);
    }

    /**
     * @test
     * Test mass assignment cannot override company_id
     */
    public function it_prevents_company_id_override_through_mass_assignment(): void
    {
        $this->actingAs($this->userA);

        // Attempt to create policy for another company via mass assignment
        $response = $this->postJson('/api/policies', [
            'name' => 'Malicious Policy',
            'company_id' => $this->companyB->id, // Attempt to override
            'booking_type_id' => BookingType::factory()->create([
                'company_id' => $this->companyA->id,
            ])->id,
        ]);

        if ($response->status() === 201) {
            $policy = Policy::latest()->first();
            // Should be forced to user's company
            $this->assertEquals($this->companyA->id, $policy->company_id);
            $this->assertNotEquals($this->companyB->id, $policy->company_id);
        }
    }

    /**
     * @test
     * Test subquery isolation between tenants
     */
    public function it_prevents_data_leakage_through_subqueries(): void
    {
        Policy::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
        ]);

        Policy::factory()->count(2)->create([
            'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->userA);

        // Use subquery to count policies
        $count = Policy::whereIn('id', function ($query) {
            $query->select('id')->from('policies');
        })->count();

        // Should only see company A's policies
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     * Test union queries maintain tenant isolation
     */
    public function it_prevents_data_leakage_through_union_queries(): void
    {
        Policy::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Policy A1',
        ]);

        Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Policy B1',
        ]);

        $this->actingAs($this->userA);

        // Attempt union query
        $query1 = Policy::where('name', 'like', '%A%');
        $query2 = Policy::where('name', 'like', '%B%');

        $results = $query1->union($query2)->get();

        // Should only return company A's policies
        $this->assertCount(1, $results);
        $this->assertEquals('Policy A1', $results->first()->name);
    }

    /**
     * @test
     * Test deletion respects company scope
     */
    public function it_prevents_cross_tenant_deletion(): void
    {
        $policyA = Policy::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->userA);

        // Attempt to delete company B's policy
        $response = $this->deleteJson("/api/policies/{$policyB->id}");

        // Should fail (403 or 404)
        $this->assertContains($response->status(), [403, 404]);

        // Policy B should still exist
        $this->assertDatabaseHas('policies', [
            'id' => $policyB->id,
            'company_id' => $this->companyB->id,
        ]);
    }

    /**
     * @test
     * Test update operations respect company scope
     */
    public function it_prevents_cross_tenant_updates(): void
    {
        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Original Name',
        ]);

        $this->actingAs($this->userA);

        // Attempt to update company B's policy
        $response = $this->putJson("/api/policies/{$policyB->id}", [
            'name' => 'Malicious Update',
        ]);

        // Should fail (403 or 404)
        $this->assertContains($response->status(), [403, 404]);

        // Policy B should remain unchanged
        $this->assertDatabaseHas('policies', [
            'id' => $policyB->id,
            'name' => 'Original Name',
        ]);
    }
}
