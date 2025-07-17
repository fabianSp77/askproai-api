<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyWhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_reseller_company()
    {
        $reseller = Company::factory()->create([
            'name' => 'Test Reseller',
            'company_type' => 'reseller',
            'is_white_label' => true,
            'commission_rate' => 20.00,
            'white_label_settings' => [
                'brand_name' => 'Test Brand',
                'primary_color' => '#1E40AF',
            ],
        ]);

        $this->assertEquals('reseller', $reseller->company_type);
        $this->assertTrue($reseller->is_white_label);
        $this->assertTrue($reseller->isReseller());
        $this->assertFalse($reseller->isClient());
        $this->assertEquals(20.00, $reseller->commission_rate);
        $this->assertEquals('Test Brand', $reseller->white_label_settings['brand_name']);
    }

    /** @test */
    public function it_can_create_parent_child_relationship()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        $this->assertEquals($reseller->id, $client->parent_company_id);
        $this->assertTrue($client->isClient());
        $this->assertInstanceOf(Company::class, $client->parentCompany);
        $this->assertEquals($reseller->id, $client->parentCompany->id);
    }

    /** @test */
    public function reseller_can_get_child_companies()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client1 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);
        $client2 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        $children = $reseller->childCompanies;

        $this->assertCount(2, $children);
        $this->assertTrue($children->contains($client1));
        $this->assertTrue($children->contains($client2));
    }

    /** @test */
    public function reseller_can_get_all_accessible_companies()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client1 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);
        $client2 = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        $accessible = $reseller->getAccessibleCompanies();

        $this->assertCount(3, $accessible); // Reseller + 2 clients
        $this->assertTrue($accessible->contains($reseller));
        $this->assertTrue($accessible->contains($client1));
        $this->assertTrue($accessible->contains($client2));
    }

    /** @test */
    public function client_can_only_access_itself()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        $accessible = $client->getAccessibleCompanies();

        $this->assertCount(1, $accessible);
        $this->assertTrue($accessible->contains($client));
        $this->assertFalse($accessible->contains($reseller));
    }

    /** @test */
    public function portal_user_with_child_access_can_be_created()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $user = PortalUser::factory()->create([
            'company_id' => $reseller->id,
            'can_access_child_companies' => true,
            'accessible_company_ids' => ['company1', 'company2'],
        ]);

        $this->assertTrue($user->can_access_child_companies);
        $this->assertIsArray($user->accessible_company_ids);
        $this->assertCount(2, $user->accessible_company_ids);
    }

    /** @test */
    public function deleting_parent_company_deletes_children()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        $clientId = $client->id;
        $reseller->delete();

        $this->assertDatabaseMissing('companies', ['id' => $clientId]);
    }

    /** @test */
    public function standalone_company_has_no_parent_or_children()
    {
        $company = Company::factory()->create(['company_type' => 'standalone']);

        $this->assertNull($company->parent_company_id);
        $this->assertNull($company->parentCompany);
        $this->assertCount(0, $company->childCompanies);
        $this->assertFalse($company->isReseller());
        $this->assertFalse($company->isClient());
    }
}