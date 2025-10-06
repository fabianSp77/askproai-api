<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\PolicyConfiguration;
use App\Services\Policies\PolicyConfigurationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConfigurationHierarchyTest extends TestCase
{
    use DatabaseTransactions;

    private PolicyConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PolicyConfigurationService();
        Cache::flush();
    }

    /** @test */
    public function it_resolves_company_level_policy()
    {
        $company = Company::factory()->create();

        $config = ['hours_before' => 24, 'fee' => 10.00];
        $this->service->setPolicy($company, 'cancellation', $config);

        $resolved = $this->service->resolvePolicy($company, 'cancellation');

        $this->assertEquals($config, $resolved);
    }

    /** @test */
    public function it_resolves_branch_inherits_from_company()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $config = ['hours_before' => 48, 'fee' => 15.00];
        $this->service->setPolicy($company, 'cancellation', $config);

        $resolved = $this->service->resolvePolicy($branch, 'cancellation');

        $this->assertEquals($config, $resolved);
    }

    /** @test */
    public function it_resolves_branch_overrides_company()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $companyConfig = ['hours_before' => 24, 'fee' => 10.00];
        $branchConfig = ['hours_before' => 48, 'fee' => 20.00];

        $this->service->setPolicy($company, 'cancellation', $companyConfig);
        $this->service->setPolicy($branch, 'cancellation', $branchConfig, true);

        $resolved = $this->service->resolvePolicy($branch, 'cancellation');

        $this->assertEquals($branchConfig, $resolved);
    }

    /** @test */
    public function it_resolves_service_inherits_from_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['branch_id' => $branch->id]);

        $config = ['hours_before' => 72, 'fee' => 25.00];
        $this->service->setPolicy($branch, 'cancellation', $config);

        $resolved = $this->service->resolvePolicy($service, 'cancellation');

        $this->assertEquals($config, $resolved);
    }

    /** @test */
    public function it_resolves_staff_inherits_from_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);

        $config = ['hours_before' => 96, 'fee' => 30.00];
        $this->service->setPolicy($branch, 'cancellation', $config);

        $resolved = $this->service->resolvePolicy($staff, 'cancellation');

        $this->assertEquals($config, $resolved);
    }

    /** @test */
    public function it_returns_null_when_no_policy_found()
    {
        $company = Company::factory()->create();

        $resolved = $this->service->resolvePolicy($company, 'cancellation');

        $this->assertNull($resolved);
    }

    /** @test */
    public function it_caches_resolved_policies()
    {
        $company = Company::factory()->create();
        $config = ['hours_before' => 24];
        $this->service->setPolicy($company, 'cancellation', $config);

        // First call
        $this->service->resolvePolicy($company, 'cancellation');

        // Cache should exist
        $cacheKey = "policy_config_Company_{$company->id}_cancellation";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($config, Cache::get($cacheKey));
    }

    /** @test */
    public function it_clears_cache_when_policy_updated()
    {
        $company = Company::factory()->create();
        $config1 = ['hours_before' => 24];
        $config2 = ['hours_before' => 48];

        $this->service->setPolicy($company, 'cancellation', $config1);
        $this->service->resolvePolicy($company, 'cancellation');

        // Update policy
        $this->service->setPolicy($company, 'cancellation', $config2);

        // Cache should be cleared and new value returned
        $resolved = $this->service->resolvePolicy($company, 'cancellation');
        $this->assertEquals($config2, $resolved);
    }

    /** @test */
    public function it_batch_resolves_multiple_entities()
    {
        $companies = Company::factory()->count(3)->create();

        foreach ($companies as $company) {
            $this->service->setPolicy($company, 'cancellation', ['hours_before' => 24]);
        }

        $results = $this->service->resolveBatch($companies, 'cancellation');

        $this->assertCount(3, $results);
        foreach ($companies as $company) {
            $this->assertEquals(['hours_before' => 24], $results[$company->id]);
        }
    }

    /** @test */
    public function it_warms_cache_for_entity()
    {
        $company = Company::factory()->create();

        $this->service->setPolicy($company, 'cancellation', ['hours_before' => 24]);
        $this->service->setPolicy($company, 'reschedule', ['max_count' => 2]);

        Cache::flush();

        $warmed = $this->service->warmCache($company);

        $this->assertEquals(3, $warmed); // cancellation, reschedule, recurring
        $this->assertTrue(Cache::has("policy_config_Company_{$company->id}_cancellation"));
    }

    /** @test */
    public function it_deletes_policy()
    {
        $company = Company::factory()->create();
        $this->service->setPolicy($company, 'cancellation', ['hours_before' => 24]);

        $deleted = $this->service->deletePolicy($company, 'cancellation');

        $this->assertTrue($deleted);
        $this->assertNull($this->service->resolvePolicy($company, 'cancellation'));
    }

    /** @test */
    public function it_handles_complex_hierarchy_traversal()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);

        // Only company has policy
        $config = ['hours_before' => 24];
        $this->service->setPolicy($company, 'cancellation', $config);

        // Staff should traverse: Staff → Branch → Company
        $resolved = $this->service->resolvePolicy($staff, 'cancellation');

        $this->assertEquals($config, $resolved);
    }

    /** @test */
    public function it_provides_cache_statistics()
    {
        $company = Company::factory()->create();

        $this->service->setPolicy($company, 'cancellation', ['hours_before' => 24]);
        $this->service->resolvePolicy($company, 'cancellation');

        $stats = $this->service->getCacheStats($company);

        $this->assertEquals(1, $stats['cached']); // cancellation cached
        $this->assertEquals(2, $stats['missing']); // reschedule, recurring not cached
    }
}
