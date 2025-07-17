<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class BranchRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function branch_belongs_to_company()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        
        $this->assertInstanceOf(Company::class, $branch->company);
        $this->assertEquals($company->id, $branch->company->id);
    }

    #[Test]
    public function branch_does_not_belong_to_customer()
    {
        $branch = Branch::factory()->create();
        
        // Verify customer relationship does not exist
        $this->assertFalse(method_exists($branch, 'customer'));
    }

    #[Test]
    public function company_has_many_branches()
    {
        $company = Company::factory()->create();
        $branches = Branch::factory()->count(3)->create(['company_id' => $company->id]);
        
        $this->assertCount(3, $company->branches);
        $this->assertInstanceOf(Branch::class, $company->branches->first());
    }

    #[Test]
    public function customer_does_not_have_branches()
    {
        $customer = Customer::factory()->create();
        
        // Verify branches relationship does not exist
        $this->assertFalse(method_exists($customer, 'branches'));
    }
}