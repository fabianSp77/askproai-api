<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test suite for Cal.com Branch Selector feature
 *
 * @group calcom
 * @group branch-selector
 */
class CalcomBranchSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $adminUser;
    protected User $staffUser;
    protected Branch $branch1;
    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_team_id' => 34209,
            'calcom_team_slug' => 'test-company',
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
        ]);
        $this->adminUser->assignRole('company_admin');

        // Create staff user
        $this->staffUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'staff@test.com',
        ]);
        $this->staffUser->assignRole('company_staff');

        // Create branches
        $this->branch1 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $this->branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Secondary Branch',
        ]);

        // Create services for branches
        Service::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);

        Service::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'is_active' => true,
        ]);

        // Add 2 inactive services (should not be counted)
        Service::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_returns_branches_for_authenticated_admin_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'branches' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'services_count',
                        'is_default',
                        'address',
                    ]
                ],
                'company' => [
                    'id',
                    'name',
                    'calcom_team_id',
                    'calcom_team_slug',
                ]
            ])
            ->assertJson([
                'success' => true,
                'company' => [
                    'id' => $this->company->id,
                    'calcom_team_id' => 34209,
                ],
            ]);

        $branches = $response->json('branches');
        $this->assertCount(2, $branches);
    }

    /** @test */
    public function it_counts_only_active_services()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $mainBranch = collect($branches)->firstWhere('id', $this->branch1->id);

        // Should count 5 active services, not 7 total
        $this->assertEquals(5, $mainBranch['services_count']);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_users()
    {
        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_empty_array_for_user_without_company()
    {
        $userWithoutCompany = User::factory()->create([
            'company_id' => null,
        ]);

        Sanctum::actingAs($userWithoutCompany);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User not authenticated or not associated with a company',
                'branches' => []
            ]);
    }

    /** @test */
    public function it_marks_user_branch_as_default()
    {
        $this->adminUser->update(['branch_id' => $this->branch2->id]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $defaultBranch = collect($branches)->firstWhere('is_default', true);

        $this->assertEquals($this->branch2->id, $defaultBranch['id']);
    }

    /** @test */
    public function it_formats_branch_address_correctly()
    {
        $this->branch1->update([
            'street' => 'Hauptstraße 123',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $mainBranch = collect($branches)->firstWhere('id', $this->branch1->id);

        $this->assertEquals('Hauptstraße 123, 10115, Berlin', $mainBranch['address']);
    }

    /** @test */
    public function it_returns_null_address_for_incomplete_branch_data()
    {
        $this->branch1->update([
            'street' => null,
            'postal_code' => null,
            'city' => null,
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $mainBranch = collect($branches)->firstWhere('id', $this->branch1->id);

        $this->assertNull($mainBranch['address']);
    }

    /** @test */
    public function it_generates_slug_from_branch_name_if_not_present()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $mainBranch = collect($branches)->firstWhere('id', $this->branch1->id);

        // Should generate slug from "Main Branch"
        $this->assertEquals('main-branch', $mainBranch['slug']);
    }

    /** @test */
    public function it_isolates_branches_by_company()
    {
        // Create another company with branches
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Branch',
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200);

        $branches = $response->json('branches');
        $branchIds = collect($branches)->pluck('id')->toArray();

        // Should only include branches from admin user's company
        $this->assertContains($this->branch1->id, $branchIds);
        $this->assertContains($this->branch2->id, $branchIds);
        $this->assertNotContains($otherBranch->id, $branchIds);
    }

    /** @test */
    public function it_handles_company_without_branches()
    {
        // Delete all branches
        Branch::where('company_id', $this->company->id)->delete();

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/calcom/branches');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'branches' => []
            ]);
    }
}
