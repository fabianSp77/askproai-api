<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;

class SchemaFixValidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function branches_table_has_correct_structure()
    {
        $this->assertTrue(Schema::hasColumn('branches', 'company_id'));
        $this->assertTrue(Schema::hasColumn('branches', 'customer_id'));
        
        // customer_id should be nullable
        if (DB::getDriverName() !== 'sqlite') {
            $columns = DB::select("SHOW COLUMNS FROM branches WHERE Field = 'customer_id'");
            $this->assertEquals('YES', $columns[0]->Null ?? 'YES');
        }
    }

    #[Test]
    public function can_create_branch_without_customer()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'customer_id' => null
        ]);
        
        $this->assertNull($branch->customer_id);
        $this->assertEquals($company->id, $branch->company_id);
    }

    #[Test]
    public function tenant_scope_isolates_data()
    {
        // Create two companies with branches
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $branch1 = Branch::factory()->create(['company_id' => $company1->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company2->id]);
        
        // Set company context
        app()->instance('current_company_id', $company1->id);
        
        // Should only see company1's branch
        $branches = Branch::all();
        $this->assertCount(1, $branches);
        $this->assertEquals($branch1->id, $branches->first()->id);
        
        // Clear context for next test
        app()->forgetInstance('current_company_id');
    }

    #[Test]
    public function models_have_correct_relationships()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        
        // Test relationships exist
        $this->assertTrue(method_exists($company, 'branches'));
        $this->assertTrue(method_exists($branch, 'company'));
        $this->assertTrue(method_exists($customer, 'company'));
        
        // Test relationships work
        $this->assertInstanceOf(Company::class, $branch->company);
        $this->assertInstanceOf(Company::class, $customer->company);
        $this->assertTrue($company->branches->contains($branch));
    }
}