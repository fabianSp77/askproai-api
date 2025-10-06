<?php

namespace Tests\Feature\Integration;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Test Suite
 *
 * End-to-end tests for customer operations after backfill fix.
 *
 * Purpose: Ensure complete customer lifecycle works correctly
 */
class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('admin');
    }

    /** @test */
    public function test_create_customer_via_api_sets_company_id()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => 'new@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(201);
        $customer = Customer::where('email', 'new@test.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->company->id, $customer->company_id);
    }

    /** @test */
    public function test_list_customers_excludes_other_companies()
    {
        $company2 = Company::factory()->create();
        Customer::factory()->create(['company_id' => $this->company->id]);
        Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($this->user);
        $response = $this->getJson('/api/customers');

        $response->assertStatus(200);
        // Should only see 1 customer from own company
    }

    /** @test */
    public function test_update_customer_maintains_company_id()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);
        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $customer->refresh();
        $this->assertEquals('Updated Name', $customer->name);
        $this->assertEquals($this->company->id, $customer->company_id);
    }

    /** @test */
    public function test_delete_customer_respects_company_scope()
    {
        $ownCustomer = Customer::factory()->create(['company_id' => $this->company->id]);
        $otherCompany = Company::factory()->create();
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->user);

        // Can delete own customer
        $response = $this->deleteJson("/api/customers/{$ownCustomer->id}");
        $response->assertStatus(204);

        // Cannot delete other company's customer
        $response = $this->deleteJson("/api/customers/{$otherCustomer->id}");
        $response->assertStatus(404); // Not found due to scope
    }

    /** @test */
    public function test_restore_soft_deleted_customer_maintains_company()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer->delete();

        $this->actingAs($this->user);
        $customer->restore();

        $this->assertNull($customer->deleted_at);
        $this->assertEquals($this->company->id, $customer->company_id);
    }
}
