<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\ServiceSelectionService;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit Tests for ServiceSelectionService
 *
 * Verifies service selection, branch isolation, and team ownership validation
 */
class ServiceSelectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceSelectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceSelectionService();
    }

    /** @test */
    public function it_gets_default_service_for_company()
    {
        $company = Company::factory()->create();
        $service1 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'is_default' => true,
            'calcom_event_type_id' => 12345,
        ]);
        $service2 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'is_default' => false,
            'calcom_event_type_id' => 67890,
        ]);

        $defaultService = $this->service->getDefaultService($company->id);

        $this->assertNotNull($defaultService);
        $this->assertEquals($service1->id, $defaultService->id);
    }

    /** @test */
    public function it_falls_back_to_priority_when_no_default_service()
    {
        $company = Company::factory()->create();
        $service1 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'is_default' => false,
            'priority' => 10,
            'calcom_event_type_id' => 12345,
        ]);
        $service2 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'is_default' => false,
            'priority' => 5,
            'calcom_event_type_id' => 67890,
        ]);

        $defaultService = $this->service->getDefaultService($company->id);

        $this->assertNotNull($defaultService);
        $this->assertEquals($service2->id, $defaultService->id, 'Should select service with lowest priority number');
    }

    /** @test */
    public function it_filters_services_by_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $branchService = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
            'calcom_event_type_id' => 11111,
        ]);

        $otherBranchService = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => 999, // Different branch
            'is_active' => true,
            'calcom_event_type_id' => 22222,
        ]);

        $defaultService = $this->service->getDefaultService($company->id, $branch->id);

        $this->assertNotNull($defaultService);
        $this->assertEquals($branchService->id, $defaultService->id);
    }

    /** @test */
    public function it_includes_company_wide_services_for_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $companyWideService = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => null, // Company-wide
            'is_active' => true,
            'calcom_event_type_id' => 33333,
        ]);

        $defaultService = $this->service->getDefaultService($company->id, $branch->id);

        $this->assertNotNull($defaultService);
        $this->assertEquals($companyWideService->id, $defaultService->id);
    }

    /** @test */
    public function it_gets_available_services_for_company()
    {
        $company = Company::factory()->create();
        Service::factory()->count(3)->create([
            'company_id' => $company->id,
            'is_active' => true,
            'calcom_event_type_id' => 12345,
        ]);

        // Inactive service should not be included
        Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => false,
            'calcom_event_type_id' => 99999,
        ]);

        $services = $this->service->getAvailableServices($company->id);

        $this->assertEquals(3, $services->count());
    }

    /** @test */
    public function it_excludes_services_without_calcom_integration()
    {
        $company = Company::factory()->create();
        Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'calcom_event_type_id' => 12345,
        ]);

        Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'calcom_event_type_id' => null, // No Cal.com integration
        ]);

        $services = $this->service->getAvailableServices($company->id);

        $this->assertEquals(1, $services->count());
    }

    /** @test */
    public function it_validates_service_access_for_company()
    {
        $company = Company::factory()->create();
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $hasAccess = $this->service->validateServiceAccess($service->id, $company->id);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_rejects_service_access_for_wrong_company()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $service = Service::factory()->create([
            'company_id' => $company1->id,
            'is_active' => true,
        ]);

        $hasAccess = $this->service->validateServiceAccess($service->id, $company2->id);

        $this->assertFalse($hasAccess, 'Should reject access to service from different company');
    }

    /** @test */
    public function it_validates_service_access_for_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $hasAccess = $this->service->validateServiceAccess($service->id, $company->id, $branch->id);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_rejects_service_access_for_wrong_branch()
    {
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        $hasAccess = $this->service->validateServiceAccess($service->id, $company->id, $branch2->id);

        $this->assertFalse($hasAccess, 'Should reject access to service from different branch');
    }

    /** @test */
    public function it_allows_company_wide_service_for_any_branch()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => null, // Company-wide
            'is_active' => true,
        ]);

        $hasAccess = $this->service->validateServiceAccess($service->id, $company->id, $branch->id);

        $this->assertTrue($hasAccess, 'Company-wide service should be accessible from any branch');
    }

    /** @test */
    public function it_finds_service_by_id_with_validation()
    {
        $company = Company::factory()->create();
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $foundService = $this->service->findServiceById($service->id, $company->id);

        $this->assertNotNull($foundService);
        $this->assertEquals($service->id, $foundService->id);
    }

    /** @test */
    public function it_returns_null_when_finding_service_with_invalid_access()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $service = Service::factory()->create([
            'company_id' => $company1->id,
            'is_active' => true,
        ]);

        $foundService = $this->service->findServiceById($service->id, $company2->id);

        $this->assertNull($foundService, 'Should return null for service from different company');
    }

    /** @test */
    public function it_caches_default_service_lookup()
    {
        $company = Company::factory()->create();
        Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'calcom_event_type_id' => 12345,
        ]);

        // Enable query log
        \DB::enableQueryLog();

        // First lookup - hits database
        $service1 = $this->service->getDefaultService($company->id);
        $queryCountAfterFirst = count(\DB::getQueryLog());

        // Second lookup - should use cache (no new queries)
        $service2 = $this->service->getDefaultService($company->id);
        $queryCountAfterSecond = count(\DB::getQueryLog());

        $this->assertEquals($service1->id, $service2->id);
        $this->assertEquals($queryCountAfterFirst, $queryCountAfterSecond, 'Second lookup should use cache');

        \DB::disableQueryLog();
    }

    /** @test */
    public function it_caches_service_validation()
    {
        $company = Company::factory()->create();
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Enable query log
        \DB::enableQueryLog();

        // First validation - hits database
        $access1 = $this->service->validateServiceAccess($service->id, $company->id);
        $queryCountAfterFirst = count(\DB::getQueryLog());

        // Second validation - should use cache
        $access2 = $this->service->validateServiceAccess($service->id, $company->id);
        $queryCountAfterSecond = count(\DB::getQueryLog());

        $this->assertEquals($access1, $access2);
        $this->assertEquals($queryCountAfterFirst, $queryCountAfterSecond, 'Second validation should use cache');

        \DB::disableQueryLog();
    }

    /** @test */
    public function it_clears_cache_on_demand()
    {
        $company = Company::factory()->create();
        Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'calcom_event_type_id' => 12345,
        ]);

        // First lookup - populates cache
        $this->service->getDefaultService($company->id);

        // Clear cache
        $this->service->clearCache();

        // Enable query log to verify database hit
        \DB::enableQueryLog();

        // Second lookup - should hit database again after cache clear
        $this->service->getDefaultService($company->id);
        $queryCount = count(\DB::getQueryLog());

        $this->assertGreaterThan(0, $queryCount, 'After cache clear, should hit database');

        \DB::disableQueryLog();
    }

    /** @test */
    public function it_returns_null_when_no_services_available()
    {
        $company = Company::factory()->create();

        $defaultService = $this->service->getDefaultService($company->id);

        $this->assertNull($defaultService);
    }

    /** @test */
    public function it_orders_services_by_priority()
    {
        $company = Company::factory()->create();
        $service1 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'priority' => 10,
            'calcom_event_type_id' => 11111,
        ]);
        $service2 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'priority' => 5,
            'calcom_event_type_id' => 22222,
        ]);
        $service3 = Service::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'priority' => 15,
            'calcom_event_type_id' => 33333,
        ]);

        $services = $this->service->getAvailableServices($company->id);

        $this->assertEquals($service2->id, $services->first()->id, 'First service should have lowest priority number');
        $this->assertEquals($service3->id, $services->last()->id, 'Last service should have highest priority number');
    }
}