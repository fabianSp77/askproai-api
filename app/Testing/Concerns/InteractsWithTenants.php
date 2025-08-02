<?php

namespace App\Testing\Concerns;

use App\Models\Company;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Testing Trait for Tenant Isolation
 * 
 * Provides helper methods for testing multi-tenant functionality,
 * ensuring proper tenant isolation and context management in tests.
 */
trait InteractsWithTenants
{
    protected ?Company $testCompany = null;
    protected ?Company $otherTestCompany = null;
    protected array $tenantTestData = [];
    
    /**
     * Set up tenant context for testing
     */
    protected function setUpTenantTesting(): void
    {
        // Create test companies if they don't exist
        if (!$this->testCompany) {
            $this->testCompany = Company::factory()->create([
                'name' => 'Test Company',
                'domain' => 'test-company.local'
            ]);
        }
        
        if (!$this->otherTestCompany) {
            $this->otherTestCompany = Company::factory()->create([
                'name' => 'Other Test Company', 
                'domain' => 'other-test-company.local'
            ]);
        }
    }
    
    /**
     * Act as a user from specific company
     */
    protected function actingAsCompanyUser(Company $company, array $userAttributes = []): self
    {
        $user = \App\Models\User::factory()->create(array_merge([
            'company_id' => $company->id
        ], $userAttributes));
        
        return $this->actingAs($user);
    }
    
    /**
     * Act as a user from the test company
     */
    protected function actingAsTestCompanyUser(array $userAttributes = []): self
    {
        $this->setUpTenantTesting();
        return $this->actingAsCompanyUser($this->testCompany, $userAttributes);
    }
    
    /**
     * Act as a user from the other test company
     */
    protected function actingAsOtherCompanyUser(array $userAttributes = []): self
    {
        $this->setUpTenantTesting();
        return $this->actingAsCompanyUser($this->otherTestCompany, $userAttributes);
    }
    
    /**
     * Set tenant context manually (for testing purposes)
     */
    protected function setTenantContext(Company $company): void
    {
        $tenantContext = app(TenantContextService::class);
        $tenantContext->setWebAuthContext($company->id, 'test_context');
    }
    
    /**
     * Clear tenant context
     */
    protected function clearTenantContext(): void
    {
        $tenantContext = app(TenantContextService::class);
        $tenantContext->clearContext();
    }
    
    /**
     * Assert that a model belongs to a specific company
     */
    protected function assertBelongsToCompany($model, Company $company): void
    {
        $this->assertNotNull($model, 'Model should not be null');
        $this->assertEquals(
            $company->id,
            $model->company_id,
            "Model should belong to company {$company->id} but belongs to {$model->company_id}"
        );
    }
    
    /**
     * Assert that a model belongs to the test company
     */
    protected function assertBelongsToTestCompany($model): void
    {
        $this->setUpTenantTesting();
        $this->assertBelongsToCompany($model, $this->testCompany);
    }
    
    /**
     * Assert that a collection only contains models from a specific company
     */
    protected function assertCollectionBelongsToCompany($collection, Company $company): void
    {
        $this->assertNotEmpty($collection, 'Collection should not be empty');
        
        foreach ($collection as $model) {
            $this->assertBelongsToCompany($model, $company);
        }
    }
    
    /**
     * Assert that tenant isolation is working (model not accessible from other tenant)
     */
    protected function assertTenantIsolation(string $modelClass, int $modelId): void
    {
        $this->setUpTenantTesting();
        
        // Create model as test company user
        $this->actingAsTestCompanyUser();
        $model = $modelClass::find($modelId);
        $this->assertNotNull($model, 'Model should be accessible to its own company');
        $this->assertBelongsToTestCompany($model);
        
        // Try to access as other company user
        $this->actingAsOtherCompanyUser();
        $modelFromOtherTenant = $modelClass::find($modelId);
        $this->assertNull($modelFromOtherTenant, 'Model should not be accessible from other tenant');
    }
    
    /**
     * Create a model for testing with proper company assignment
     */
    protected function createTenantModel(string $modelClass, array $attributes = [], ?Company $company = null): \Illuminate\Database\Eloquent\Model
    {
        $this->setUpTenantTesting();
        $targetCompany = $company ?? $this->testCompany;
        
        // Check if model supports tenant scoping
        $model = new $modelClass;
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
            $attributes['company_id'] = $targetCompany->id;
        }
        
        return $modelClass::factory()->create($attributes);
    }
    
    /**
     * Create multiple models for testing tenant isolation
     */
    protected function createTenantModels(string $modelClass, int $count = 3, array $attributes = []): array
    {
        $this->setUpTenantTesting();
        
        $models = [];
        
        // Create models for test company
        for ($i = 0; $i < $count; $i++) {
            $models['test_company'][] = $this->createTenantModel($modelClass, $attributes, $this->testCompany);
        }
        
        // Create models for other company
        for ($i = 0; $i < $count; $i++) {
            $models['other_company'][] = $this->createTenantModel($modelClass, $attributes, $this->otherTestCompany);
        }
        
        return $models;
    }
    
    /**
     * Test that a repository properly isolates tenant data
     */
    protected function assertRepositoryTenantIsolation(string $repositoryClass): void
    {
        $this->setUpTenantTesting();
        
        // Create some test data
        $modelClass = $this->getRepositoryModelClass($repositoryClass);
        $models = $this->createTenantModels($modelClass, 2);
        
        // Test as first company user
        $this->actingAsTestCompanyUser();
        $repository = app($repositoryClass);
        
        $companyModels = $repository->all();
        $this->assertCount(2, $companyModels);
        $this->assertCollectionBelongsToCompany($companyModels, $this->testCompany);
        
        // Test as second company user
        $this->actingAsOtherCompanyUser();
        $repository = app($repositoryClass);
        
        $otherCompanyModels = $repository->all();
        $this->assertCount(2, $otherCompanyModels);
        $this->assertCollectionBelongsToCompany($otherCompanyModels, $this->otherTestCompany);
    }
    
    /**
     * Get the model class for a repository (convention-based)
     */
    protected function getRepositoryModelClass(string $repositoryClass): string
    {
        // Extract model name from repository class name
        // e.g., App\Repositories\CustomerRepository -> App\Models\Customer
        $className = class_basename($repositoryClass);
        $modelName = str_replace('Repository', '', $className);
        
        return "App\\Models\\{$modelName}";
    }
    
    /**
     * Assert that cross-tenant access is properly denied
     */
    protected function assertCrossTenantAccessDenied(callable $operation): void
    {
        $this->expectException(\App\Exceptions\TenantContextException::class);
        $operation();
    }
    
    /**
     * Test job with tenant context
     */
    protected function assertJobHasTenantContext(string $jobClass, array $jobData = [], ?Company $company = null): void
    {
        $this->setUpTenantTesting();
        $targetCompany = $company ?? $this->testCompany;
        
        // Act as company user
        $this->actingAsCompanyUser($targetCompany);
        
        // Dispatch job
        $job = new $jobClass(...$jobData);
        
        // Check that job captured tenant context
        $reflection = new \ReflectionClass($job);
        $tenantProperty = $reflection->getProperty('tenantCompanyId');
        $tenantProperty->setAccessible(true);
        
        $this->assertEquals(
            $targetCompany->id,
            $tenantProperty->getValue($job),
            'Job should capture tenant context during creation'
        );
    }
    
    /**
     * Mock tenant context service for testing
     */
    protected function mockTenantContext(?Company $company = null): void
    {
        $this->setUpTenantTesting();
        $targetCompany = $company ?? $this->testCompany;
        
        $mockTenantContext = \Mockery::mock(TenantContextService::class);
        $mockTenantContext->shouldReceive('getCurrentCompanyId')
            ->andReturn($targetCompany->id);
        $mockTenantContext->shouldReceive('belongsToCompany')
            ->andReturn(true);
        
        $this->app->instance(TenantContextService::class, $mockTenantContext);
    }
    
    /**
     * Get test companies
     */
    protected function getTestCompany(): Company
    {
        $this->setUpTenantTesting();
        return $this->testCompany;
    }
    
    protected function getOtherTestCompany(): Company
    {
        $this->setUpTenantTesting();
        return $this->otherTestCompany;
    }
    
    /**
     * Clean up tenant test data
     */
    protected function tearDownTenantTesting(): void
    {
        $this->clearTenantContext();
        
        if ($this->testCompany) {
            $this->testCompany->delete();
            $this->testCompany = null;
        }
        
        if ($this->otherTestCompany) {
            $this->otherTestCompany->delete();
            $this->otherTestCompany = null;
        }
        
        $this->tenantTestData = [];
    }
}