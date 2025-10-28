<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Service::hasProcessingTime() Feature Flag Tests
 *
 * Tests all combinations of feature flags to ensure correct behavior
 * in every rollout scenario (testing, pilot, general availability)
 */

beforeEach(function () {
    // Create test company and service
    $this->company = Company::factory()->create();
    $this->service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);
});

afterEach(function () {
    // Reset all feature flags to default state
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [],
        'features.processing_time_company_whitelist' => [],
    ]);
});

// ============================================================================
// SCENARIO 1: Service Configuration Tests
// ============================================================================

test('service without processing time returns false', function () {
    $service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => false,
    ]);

    // Should return false regardless of feature flags
    expect($service->hasProcessingTime())->toBeFalse();
});

test('service with has_processing_time set to 0 returns false', function () {
    $service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => 0, // Explicitly set to 0 (false)
    ]);

    expect($service->hasProcessingTime())->toBeFalse();
});

// ============================================================================
// SCENARIO 2: Master Toggle OFF + Service Whitelist
// (Phase 1: Internal Testing)
// ============================================================================

test('master toggle OFF and service NOT in whitelist returns false', function () {
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => ['other-uuid-1', 'other-uuid-2'],
    ]);

    expect($this->service->hasProcessingTime())->toBeFalse();
});

test('master toggle OFF and service IN whitelist returns true', function () {
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [$this->service->id, 'other-uuid'],
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();
});

test('master toggle OFF and empty whitelist returns false', function () {
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [],
    ]);

    expect($this->service->hasProcessingTime())->toBeFalse();
});

// ============================================================================
// SCENARIO 3: Master Toggle ON + Company Whitelist
// (Phase 2: Pilot Rollout)
// ============================================================================

test('master toggle ON and company NOT in whitelist returns false', function () {
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [999, 888],
    ]);

    expect($this->service->hasProcessingTime())->toBeFalse();
});

test('master toggle ON and company IN whitelist returns true', function () {
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [$this->company->id, 999],
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();
});

test('master toggle ON and empty company whitelist returns true (general availability)', function () {
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [],
    ]);

    // Empty whitelist = available to ALL companies
    expect($this->service->hasProcessingTime())->toBeTrue();
});

// ============================================================================
// SCENARIO 4: Edge Cases & Security
// ============================================================================

test('service whitelist takes precedence when master toggle OFF', function () {
    // Even if company is "whitelisted", service whitelist is what matters when master is OFF
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [$this->service->id],
        'features.processing_time_company_whitelist' => [999], // Different company
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();
});

test('company whitelist is ignored when master toggle OFF', function () {
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [], // Not in service whitelist
        'features.processing_time_company_whitelist' => [$this->company->id], // In company whitelist
    ]);

    // Company whitelist is only checked when master is ON
    expect($this->service->hasProcessingTime())->toBeFalse();
});

test('service must have has_processing_time true even if whitelisted', function () {
    $serviceWithoutPT = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => false, // NOT configured for processing time
    ]);

    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [$serviceWithoutPT->id], // In whitelist
    ]);

    // Should still return false because service itself doesn't have processing time configured
    expect($serviceWithoutPT->hasProcessingTime())->toBeFalse();
});

// ============================================================================
// SCENARIO 5: Rollout Progression Tests
// ============================================================================

test('phase 1 rollout scenario (internal testing)', function () {
    // Phase 1: Master OFF, only whitelisted services
    config([
        'features.processing_time_enabled' => false,
        'features.processing_time_service_whitelist' => [$this->service->id],
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();

    // Create another service NOT in whitelist
    $otherService = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
    ]);

    expect($otherService->hasProcessingTime())->toBeFalse();
});

test('phase 2 rollout scenario (pilot companies)', function () {
    // Phase 2: Master ON, only whitelisted companies
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [$this->company->id],
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();

    // Create service in non-pilot company
    $otherCompany = Company::factory()->create();
    $otherService = Service::factory()->create([
        'company_id' => $otherCompany->id,
        'has_processing_time' => true,
    ]);

    expect($otherService->hasProcessingTime())->toBeFalse();
});

test('phase 3 rollout scenario (general availability)', function () {
    // Phase 3: Master ON, empty whitelists = all allowed
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_service_whitelist' => [],
        'features.processing_time_company_whitelist' => [],
    ]);

    expect($this->service->hasProcessingTime())->toBeTrue();

    // Create service in any company - should work
    $otherCompany = Company::factory()->create();
    $otherService = Service::factory()->create([
        'company_id' => $otherCompany->id,
        'has_processing_time' => true,
    ]);

    expect($otherService->hasProcessingTime())->toBeTrue();
});

// ============================================================================
// SCENARIO 6: Multiple Services & Companies
// ============================================================================

test('multiple services with different configurations', function () {
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [$this->company->id],
    ]);

    // Service 1: has_processing_time = true, company whitelisted
    expect($this->service->hasProcessingTime())->toBeTrue();

    // Service 2: has_processing_time = false, same company
    $service2 = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => false,
    ]);
    expect($service2->hasProcessingTime())->toBeFalse();

    // Service 3: has_processing_time = true, different company (not whitelisted)
    $otherCompany = Company::factory()->create();
    $service3 = Service::factory()->create([
        'company_id' => $otherCompany->id,
        'has_processing_time' => true,
    ]);
    expect($service3->hasProcessingTime())->toBeFalse();
});

// ============================================================================
// SCENARIO 7: Performance & Caching
// ============================================================================

test('hasProcessingTime can be called multiple times without side effects', function () {
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [],
    ]);

    // Call multiple times
    expect($this->service->hasProcessingTime())->toBeTrue();
    expect($this->service->hasProcessingTime())->toBeTrue();
    expect($this->service->hasProcessingTime())->toBeTrue();
});

test('changing config mid-execution reflects immediately', function () {
    config(['features.processing_time_enabled' => false]);
    expect($this->service->hasProcessingTime())->toBeFalse();

    // Change config
    config(['features.processing_time_enabled' => true]);
    expect($this->service->hasProcessingTime())->toBeTrue();

    // Change back
    config(['features.processing_time_enabled' => false]);
    expect($this->service->hasProcessingTime())->toBeFalse();
});
