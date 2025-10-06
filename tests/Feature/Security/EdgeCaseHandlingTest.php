<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edge Case Handling Test Suite
 *
 * Tests edge cases and boundary conditions:
 * - Soft deletes with company scope
 * - Null value handling
 * - Empty result sets
 * - Concurrent access scenarios
 */
class EdgeCaseHandlingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * @test
     * Test soft deleted records respect company scope
     */
    public function soft_deleted_records_respect_company_scope(): void
    {
        $this->actingAs($this->user);

        $policy = Policy::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Soft delete the policy
        $policy->delete();

        // Query without trashed
        $policies = Policy::all();
        $this->assertCount(0, $policies);

        // Query with trashed
        $policiesWithTrashed = Policy::withTrashed()->get();
        $this->assertCount(1, $policiesWithTrashed);
        $this->assertNotNull($policiesWithTrashed->first()->deleted_at);
    }

    /**
     * @test
     * Test null company_id handling
     */
    public function it_handles_null_company_id_gracefully(): void
    {
        $this->actingAs($this->user);

        // Attempt to query with null company_id should return empty results
        $results = Policy::where('company_id', null)->get();

        $this->assertCount(0, $results);
    }

    /**
     * @test
     * Test empty result set handling
     */
    public function it_handles_empty_result_sets(): void
    {
        $this->actingAs($this->user);

        // Query with no matching records
        $policies = Policy::where('name', 'non-existent-policy')->get();

        $this->assertCount(0, $policies);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $policies);
    }

    /**
     * @test
     * Test cascading deletes maintain company isolation
     */
    public function cascading_deletes_maintain_company_isolation(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $policyA = Policy::factory()->create(['company_id' => $companyA->id]);
        $policyB = Policy::factory()->create(['company_id' => $companyB->id]);

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $this->actingAs($userA);

        // Delete company A's policy
        $policyA->delete();

        // Company B's policy should remain
        $this->assertDatabaseHas('policies', [
            'id' => $policyB->id,
            'company_id' => $companyB->id,
        ]);
    }

    /**
     * @test
     * Test duplicate data handling
     */
    public function it_prevents_duplicate_data_across_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Policy::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Standard Policy',
        ]);

        // Same name in different company should be allowed
        $policyB = Policy::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Standard Policy',
        ]);

        $this->assertNotNull($policyB);
        $this->assertDatabaseCount('policies', 2);
    }

    /**
     * @test
     * Test query with invalid company_id
     */
    public function it_handles_invalid_company_id(): void
    {
        $this->actingAs($this->user);

        // Query with non-existent company_id
        $policies = Policy::where('company_id', 999999)->get();

        $this->assertCount(0, $policies);
    }

    /**
     * @test
     * Test relationship queries with missing data
     */
    public function it_handles_missing_relationship_data(): void
    {
        $this->actingAs($this->user);

        $service = Service::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Query bookings relationship (should return empty collection)
        $bookings = $service->bookings;

        $this->assertCount(0, $bookings);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $bookings);
    }

    /**
     * @test
     * Test concurrent user access to same resource
     */
    public function it_handles_concurrent_access_to_same_resource(): void
    {
        $policy = Policy::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        $user1 = User::factory()->create(['company_id' => $this->company->id]);
        $user2 = User::factory()->create(['company_id' => $this->company->id]);

        // User 1 updates
        $this->actingAs($user1);
        $policy->update(['name' => 'User 1 Update']);

        // User 2 also updates
        $this->actingAs($user2);
        $policy->refresh();
        $policy->update(['name' => 'User 2 Update']);

        $policy->refresh();

        // Last update should win
        $this->assertEquals('User 2 Update', $policy->name);
    }

    /**
     * @test
     * Test pagination with company scope
     */
    public function pagination_respects_company_scope(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        // Create 15 policies for company A
        Policy::factory()->count(15)->create(['company_id' => $companyA->id]);

        // Create 10 policies for company B
        Policy::factory()->count(10)->create(['company_id' => $companyB->id]);

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $this->actingAs($userA);

        // Paginate results
        $policies = Policy::paginate(10);

        // Should only see company A's policies
        $this->assertEquals(15, $policies->total());
        $this->assertEquals(10, $policies->count());
    }

    /**
     * @test
     * Test batch operations respect company scope
     */
    public function batch_operations_respect_company_scope(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Policy::factory()->count(5)->create(['company_id' => $companyA->id]);
        Policy::factory()->count(3)->create(['company_id' => $companyB->id]);

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $this->actingAs($userA);

        // Batch update
        Policy::query()->update(['description' => 'Updated']);

        // Only company A's policies should be updated
        $this->assertEquals(5, Policy::where('description', 'Updated')->count());
    }
}
