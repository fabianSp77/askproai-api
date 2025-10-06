<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\Policies\PolicyConfigurationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PolicyConfigurationServiceTest extends TestCase
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
    public function it_generates_unique_cache_keys()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $this->service->setPolicy($company1, 'cancellation', ['test' => 1]);
        $this->service->setPolicy($company2, 'cancellation', ['test' => 2]);

        // Trigger cache by resolving policies
        $this->service->resolvePolicy($company1, 'cancellation');
        $this->service->resolvePolicy($company2, 'cancellation');

        $key1 = "policy_config_Company_{$company1->id}_cancellation";
        $key2 = "policy_config_Company_{$company2->id}_cancellation";

        $this->assertNotEquals($key1, $key2);
        $this->assertTrue(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));
    }

    /** @test */
    public function it_handles_null_parent_gracefully()
    {
        $company = Company::factory()->create();

        // No policy set
        $resolved = $this->service->resolvePolicy($company, 'cancellation');

        $this->assertNull($resolved);
    }

    /** @test */
    public function it_uses_cache_on_second_call()
    {
        $company = Company::factory()->create();
        $config = ['hours_before' => 24];

        $this->service->setPolicy($company, 'cancellation', $config);

        // First call - database query
        $start1 = microtime(true);
        $result1 = $this->service->resolvePolicy($company, 'cancellation');
        $time1 = (microtime(true) - $start1) * 1000;

        // Second call - from cache (should be faster)
        $start2 = microtime(true);
        $result2 = $this->service->resolvePolicy($company, 'cancellation');
        $time2 = (microtime(true) - $start2) * 1000;

        $this->assertEquals($result1, $result2);
        // Cache should be significantly faster (at least 2x)
        $this->assertLessThan($time1 / 2, $time2);
    }

    /** @test */
    public function it_clears_specific_policy_type_only()
    {
        $company = Company::factory()->create();

        $this->service->setPolicy($company, 'cancellation', ['test' => 1]);
        $this->service->setPolicy($company, 'reschedule', ['test' => 2]);

        $this->service->resolvePolicy($company, 'cancellation');
        $this->service->resolvePolicy($company, 'reschedule');

        // Clear only cancellation
        $this->service->clearCache($company, 'cancellation');

        $key1 = "policy_config_Company_{$company->id}_cancellation";
        $key2 = "policy_config_Company_{$company->id}_reschedule";

        $this->assertFalse(Cache::has($key1));
        $this->assertTrue(Cache::has($key2));
    }

    /** @test */
    public function it_clears_all_policies_when_no_type_specified()
    {
        $company = Company::factory()->create();

        $this->service->setPolicy($company, 'cancellation', ['test' => 1]);
        $this->service->setPolicy($company, 'reschedule', ['test' => 2]);
        $this->service->setPolicy($company, 'recurring', ['test' => 3]);

        $this->service->resolvePolicy($company, 'cancellation');
        $this->service->resolvePolicy($company, 'reschedule');
        $this->service->resolvePolicy($company, 'recurring');

        // Clear all
        $this->service->clearCache($company);

        foreach (['cancellation', 'reschedule', 'recurring'] as $type) {
            $key = "policy_config_Company_{$company->id}_{$type}";
            $this->assertFalse(Cache::has($key));
        }
    }

    /** @test */
    public function it_returns_entity_policies_without_hierarchy()
    {
        $company = Company::factory()->create();

        $this->service->setPolicy($company, 'cancellation', ['test' => 1]);
        $this->service->setPolicy($company, 'reschedule', ['test' => 2]);

        $policies = $this->service->getEntityPolicies($company);

        $this->assertCount(2, $policies);
    }

    /** @test */
    public function batch_resolve_uses_cache_efficiently()
    {
        $companies = Company::factory()->count(5)->create();

        foreach ($companies as $company) {
            $this->service->setPolicy($company, 'cancellation', ['hours_before' => 24]);
        }

        // First batch - should cache all
        $results1 = $this->service->resolveBatch($companies, 'cancellation');

        // Second batch - should use cache (verify by checking cache hits)
        $results2 = $this->service->resolveBatch($companies, 'cancellation');

        $this->assertEquals($results1, $results2);

        // All should be cached now
        foreach ($companies as $company) {
            $key = "policy_config_Company_{$company->id}_cancellation";
            $this->assertTrue(Cache::has($key));
        }
    }
}
