<?php

use App\Services\Cache\CacheManager;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('caches model queries effectively', function () {
    $company = Company::factory()->create();

    DB::enableQueryLog();

    // First call should hit database
    $result1 = CacheManager::rememberModel(Company::class, $company->id);
    $queries1 = count(DB::getQueryLog());

    DB::flushQueryLog();

    // Second call should use cache
    $result2 = CacheManager::rememberModel(Company::class, $company->id);
    $queries2 = count(DB::getQueryLog());

    expect($result1->id)->toBe($company->id);
    expect($result2->id)->toBe($company->id);
    expect($queries1)->toBeGreaterThan(0);
    expect($queries2)->toBe(0); // No queries for cached result
});

it('invalidates cache on model update', function () {
    $company = Company::factory()->create(['name' => 'Original Name']);

    // Cache the model
    CacheManager::rememberModel(Company::class, $company->id);

    // Update the model
    $company->update(['name' => 'Updated Name']);

    // Invalidate cache
    CacheManager::invalidateModel(Company::class, $company->id);

    // Fetch again should get updated data
    $cached = CacheManager::rememberModel(Company::class, $company->id);

    expect($cached->name)->toBe('Updated Name');
});

it('uses cache tags for efficient invalidation', function () {
    $company = Company::factory()->create();
    $customers = Customer::factory(3)->create(['company_id' => $company->id]);

    // Cache multiple related items with tags
    foreach ($customers as $customer) {
        Cache::tags(['customers', "company:{$company->id}"])
            ->put("customer:{$customer->id}", $customer, 3600);
    }

    // Verify all are cached
    foreach ($customers as $customer) {
        $cached = Cache::tags(['customers', "company:{$company->id}"])
            ->get("customer:{$customer->id}");
        expect($cached)->not->toBeNull();
    }

    // Flush by tag
    Cache::tags(["company:{$company->id}"])->flush();

    // Verify all are invalidated
    foreach ($customers as $customer) {
        $cached = Cache::tags(['customers', "company:{$company->id}"])
            ->get("customer:{$customer->id}");
        expect($cached)->toBeNull();
    }
});

it('caches aggregate queries efficiently', function () {
    $company = Company::factory()->create();
    Customer::factory(50)->create(['company_id' => $company->id]);

    DB::enableQueryLog();

    // First call calculates
    $count1 = CacheManager::rememberStats(
        "customer_count:{$company->id}",
        fn() => Customer::where('company_id', $company->id)->count()
    );
    $queries1 = count(DB::getQueryLog());

    DB::flushQueryLog();

    // Second call uses cache
    $count2 = CacheManager::rememberStats(
        "customer_count:{$company->id}",
        fn() => Customer::where('company_id', $company->id)->count()
    );
    $queries2 = count(DB::getQueryLog());

    expect($count1)->toBe(50);
    expect($count2)->toBe(50);
    expect($queries1)->toBeGreaterThan(0);
    expect($queries2)->toBe(0);
});

it('implements cache warming for frequently accessed data', function () {
    $companies = Company::factory(5)->create();

    // Warm the cache
    CacheManager::warmCache();

    DB::enableQueryLog();

    // Access warmed data should not hit database
    foreach ($companies->take(3) as $company) {
        $cached = Cache::get("company:active:{$company->id}");
        expect($cached)->not->toBeNull();
    }

    expect(count(DB::getQueryLog()))->toBe(0);
});

it('respects cache TTL settings', function () {
    $key = 'test_ttl_key';
    $value = 'test_value';

    // Cache with short TTL
    Cache::put($key, $value, 1); // 1 second

    expect(Cache::get($key))->toBe($value);

    sleep(2);

    expect(Cache::get($key))->toBeNull();
});

it('handles concurrent cache access safely', function () {
    $company = Company::factory()->create();
    $iterations = 10;
    $results = [];

    // Simulate concurrent requests
    for ($i = 0; $i < $iterations; $i++) {
        $results[] = CacheManager::rememberModel(Company::class, $company->id);
    }

    // All results should be identical
    foreach ($results as $result) {
        expect($result->id)->toBe($company->id);
        expect($result->name)->toBe($company->name);
    }
});

it('implements cache bypass for critical operations', function () {
    $customer = Customer::factory()->create(['status' => 'active']);

    // Cache the customer
    CacheManager::rememberModel(Customer::class, $customer->id);

    // Update directly in database
    DB::table('customers')
        ->where('id', $customer->id)
        ->update(['status' => 'inactive']);

    // Bypass cache for critical read
    $fresh = CacheManager::bypassCache(function () use ($customer) {
        return Customer::find($customer->id);
    });

    expect($fresh->status)->toBe('inactive');
});

it('monitors cache hit rate', function () {
    $company = Company::factory()->create();

    // Reset stats
    CacheManager::resetStats();

    // Generate some cache hits and misses
    CacheManager::rememberModel(Company::class, $company->id); // Miss
    CacheManager::rememberModel(Company::class, $company->id); // Hit
    CacheManager::rememberModel(Company::class, $company->id); // Hit

    $stats = CacheManager::getStats();

    expect($stats['hits'])->toBe(2);
    expect($stats['misses'])->toBe(1);
    expect($stats['hit_rate'])->toBe(0.67); // 2/3
});

it('implements selective cache preloading', function () {
    $company = Company::factory()->create();
    $appointments = Appointment::factory(10)->create([
        'company_id' => $company->id,
        'appointment_date' => now()->format('Y-m-d')
    ]);

    // Preload today's appointments
    CacheManager::preloadTodayAppointments($company->id);

    DB::enableQueryLog();

    // Access should use cache
    $cached = Cache::get("appointments:today:{$company->id}");
    expect($cached)->toHaveCount(10);
    expect(count(DB::getQueryLog()))->toBe(0);
});