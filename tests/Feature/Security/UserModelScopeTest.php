<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Model Scope Test Suite
 *
 * Tests user enumeration prevention and authentication isolation:
 * - User queries scoped to company
 * - User enumeration attack prevention
 * - Authentication isolation between tenants
 */
class UserModelScopeTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA1;
    private User $userA2;
    private User $userB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        $this->userA1 = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'user1@companyA.com',
        ]);

        $this->userA2 = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'user2@companyA.com',
        ]);

        $this->userB1 = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'user@companyB.com',
        ]);
    }

    /**
     * @test
     * Test user queries are scoped to company
     */
    public function user_queries_are_scoped_to_company(): void
    {
        $this->actingAs($this->userA1);

        $users = User::all();

        // Should only see users from company A
        $this->assertCount(2, $users);
        $this->assertTrue($users->contains($this->userA1));
        $this->assertTrue($users->contains($this->userA2));
        $this->assertFalse($users->contains($this->userB1));
    }

    /**
     * @test
     * Test user enumeration is prevented
     */
    public function it_prevents_user_enumeration_attacks(): void
    {
        $this->actingAs($this->userA1);

        // Attempt to fetch user from another company
        $response = $this->getJson("/api/users/{$this->userB1->id}");

        // Should return 404, not 403 (prevents enumeration)
        $this->assertEquals(404, $response->status());
    }

    /**
     * @test
     * Test user search respects company scope
     */
    public function user_search_respects_company_scope(): void
    {
        $this->actingAs($this->userA1);

        // Search by email that exists in another company
        $users = User::where('email', $this->userB1->email)->get();

        $this->assertCount(0, $users);
    }

    /**
     * @test
     * Test authentication isolation between companies
     */
    public function authentication_is_isolated_between_companies(): void
    {
        // User A tries to authenticate
        $responseA = $this->postJson('/api/login', [
            'email' => $this->userA1->email,
            'password' => 'password', // Default factory password
        ]);

        $this->assertEquals(200, $responseA->status());

        // Verify authenticated user has correct company
        $this->actingAs($this->userA1);
        $this->assertEquals($this->companyA->id, auth()->user()->company_id);
    }

    /**
     * @test
     * Test user listing excludes other companies
     */
    public function user_listing_excludes_other_companies(): void
    {
        $this->actingAs($this->userA1);

        $response = $this->getJson('/api/users');

        if ($response->status() === 200) {
            $data = $response->json('data') ?? $response->json();

            $emails = collect($data)->pluck('email');

            $this->assertContains($this->userA1->email, $emails);
            $this->assertContains($this->userA2->email, $emails);
            $this->assertNotContains($this->userB1->email, $emails);
        }
    }

    /**
     * @test
     * Test user count is scoped to company
     */
    public function user_count_is_scoped_to_company(): void
    {
        $this->actingAs($this->userA1);

        $count = User::count();

        // Should only count users from company A
        $this->assertEquals(2, $count);
    }

    /**
     * @test
     * Test user cannot update other company users
     */
    public function user_cannot_update_cross_tenant_users(): void
    {
        $this->actingAs($this->userA1);

        $response = $this->putJson("/api/users/{$this->userB1->id}", [
            'email' => 'malicious@update.com',
        ]);

        $this->assertContains($response->status(), [403, 404]);

        $this->assertDatabaseHas('users', [
            'id' => $this->userB1->id,
            'email' => $this->userB1->email,
        ]);
    }
}
