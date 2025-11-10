<?php

namespace Tests\Feature\CalcomAtoms;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Cal.com Atoms Branch Access Control
 */
class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_company_membership()
    {
        // User without company
        $user = User::factory()->create(['company_id' => null]);

        $this->actingAs($user);

        $canAccess = \App\Filament\Pages\CalcomBooking::canAccess();

        $this->assertFalse($canAccess, 'User without company should not access Cal.com booking');
    }

    public function test_page_accessible_with_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $canAccess = \App\Filament\Pages\CalcomBooking::canAccess();

        $this->assertTrue($canAccess, 'User with company should access Cal.com booking');
    }

    public function test_user_with_branch_gets_only_their_branch()
    {
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 2']);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);

        $service = app(\App\Services\Calcom\BranchCalcomConfigService::class);
        $branches = $service->getUserBranches($user);

        $this->assertCount(1, $branches);
        $this->assertEquals($branch1->id, $branches->first()['id']);
    }

    public function test_company_admin_gets_all_branches()
    {
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 2']);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => null, // Company admin
        ]);

        $service = app(\App\Services\Calcom\BranchCalcomConfigService::class);
        $branches = $service->getUserBranches($user);

        $this->assertCount(2, $branches);
    }

    public function test_user_cannot_access_other_company_branch()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company1->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company2->id]);

        $user = User::factory()->create(['company_id' => $company1->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/calcom-atoms/branch/{$branch2->id}/config");

        $response->assertForbidden();
    }
}
