<?php

namespace Tests\Unit\MultiTenancy;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Services\DeterministicCustomerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Multi-Tenancy Customer Isolation Tests
 *
 * These tests verify that customer data is properly isolated between companies.
 * The DeterministicCustomerMatcher had a cross-company fallback that was removed
 * to prevent data leakage between tenants.
 *
 * @see app/Services/DeterministicCustomerMatcher.php
 */

/**
 * Helper to create a phone number with guarded fields
 */
function createPhoneNumber(int $companyId, string $number, ?string $branchId = null): PhoneNumber
{
    $phone = new PhoneNumber();
    $phone->id = (string) Str::uuid();
    $phone->number = $number;
    $phone->company_id = $companyId;
    $phone->branch_id = $branchId;
    $phone->is_active = true;
    $phone->save();
    return $phone;
}

test('DeterministicCustomerMatcher returns customer only from target company', function () {
    // Setup: Create two companies with branches
    $companyA = Company::factory()->create(['name' => 'Hair Salon']);
    $companyB = Company::factory()->create(['name' => 'IT Provider']);

    $branchA = Branch::factory()->create(['company_id' => $companyA->id]);
    $branchB = Branch::factory()->create(['company_id' => $companyB->id]);

    // Same phone number in both companies (different customers)
    $phone = '+49123456789';

    $customerA = Customer::factory()->create([
        'phone' => $phone,
        'company_id' => $companyA->id,
        'name' => 'Customer A (Hair Salon)',
    ]);

    $customerB = Customer::factory()->create([
        'phone' => $phone,
        'company_id' => $companyB->id,
        'name' => 'Customer B (IT Provider)',
    ]);

    // Register phone numbers for the companies
    $phoneA = createPhoneNumber($companyA->id, '+499001111', $branchA->id);
    $phoneB = createPhoneNumber($companyB->id, '+499002222', $branchB->id);

    // Test: Call to Company A should return Customer A (not Customer B)
    $resultA = DeterministicCustomerMatcher::matchCustomer(
        fromNumber: $phone,
        toNumber: $phoneA->number
    );

    expect($resultA['company_id'])->toBe($companyA->id);
    expect($resultA['customer'])->not->toBeNull();
    expect($resultA['customer']->id)->toBe($customerA->id);
    expect($resultA['customer']->name)->toBe('Customer A (Hair Salon)');
    expect($resultA['match_method'])->toBe('exact_phone_in_company');

    // Test: Call to Company B should return Customer B (not Customer A)
    $resultB = DeterministicCustomerMatcher::matchCustomer(
        fromNumber: $phone,
        toNumber: $phoneB->number
    );

    expect($resultB['company_id'])->toBe($companyB->id);
    expect($resultB['customer'])->not->toBeNull();
    expect($resultB['customer']->id)->toBe($customerB->id);
    expect($resultB['customer']->name)->toBe('Customer B (IT Provider)');
});

test('DeterministicCustomerMatcher returns unknown when customer only exists in different company', function () {
    // Setup: Customer exists only in Company A
    $companyA = Company::factory()->create(['name' => 'Company A']);
    $companyB = Company::factory()->create(['name' => 'Company B']);

    $branchB = Branch::factory()->create(['company_id' => $companyB->id]);

    $phone = '+49999888777';

    $customerA = Customer::factory()->create([
        'phone' => $phone,
        'company_id' => $companyA->id,
        'name' => 'Only in Company A',
    ]);

    // Register phone number for Company B
    $phoneBNumber = createPhoneNumber($companyB->id, '+499003333', $branchB->id);

    // Test: Call to Company B should NOT return Company A's customer
    // It should return as unknown
    $result = DeterministicCustomerMatcher::matchCustomer(
        fromNumber: $phone,
        toNumber: $phoneBNumber->number
    );

    expect($result['company_id'])->toBe($companyB->id);
    expect($result['customer'])->toBeNull();
    expect($result['is_unknown'])->toBeTrue();
    expect($result['unknown_reason'])->toBe('no_match_found');
});

test('Customer::findByPhoneInCompany respects company boundaries', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $phone = '+49111222333';

    $customerA = Customer::factory()->create([
        'phone' => $phone,
        'company_id' => $companyA->id,
        'name' => 'Customer A',
    ]);

    $customerB = Customer::factory()->create([
        'phone' => $phone,
        'company_id' => $companyB->id,
        'name' => 'Customer B',
    ]);

    // Test: findByPhoneInCompany returns correct customer per company
    $foundA = Customer::findByPhoneInCompany($phone, $companyA->id);
    expect($foundA)->not->toBeNull();
    expect($foundA->id)->toBe($customerA->id);

    $foundB = Customer::findByPhoneInCompany($phone, $companyB->id);
    expect($foundB)->not->toBeNull();
    expect($foundB->id)->toBe($customerB->id);

    // Test: Returns null when customer doesn't exist in that company
    $companyC = Company::factory()->create();
    $foundC = Customer::findByPhoneInCompany($phone, $companyC->id);
    expect($foundC)->toBeNull();
});

test('Customer::firstOrCreateInCompany creates company-specific customers', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $phone = '+49444555666';

    // Create customer in Company A
    $customerA = Customer::firstOrCreateInCompany($phone, $companyA->id, [
        'name' => 'New Customer A',
    ]);

    expect($customerA->company_id)->toBe($companyA->id);
    expect($customerA->phone)->toBe($phone);

    // Create customer in Company B with same phone
    $customerB = Customer::firstOrCreateInCompany($phone, $companyB->id, [
        'name' => 'New Customer B',
    ]);

    expect($customerB->company_id)->toBe($companyB->id);
    expect($customerB->id)->not->toBe($customerA->id); // Different customer!

    // Verify both exist
    expect(Customer::where('phone', $phone)->count())->toBe(2);
});

test('Customer::findByEmailInCompany respects company boundaries', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $email = 'test@example.com';

    $customerA = Customer::factory()->create([
        'email' => $email,
        'company_id' => $companyA->id,
        'name' => 'Customer A',
    ]);

    $customerB = Customer::factory()->create([
        'email' => $email,
        'company_id' => $companyB->id,
        'name' => 'Customer B',
    ]);

    $foundA = Customer::findByEmailInCompany($email, $companyA->id);
    expect($foundA)->not->toBeNull();
    expect($foundA->id)->toBe($customerA->id);

    $foundB = Customer::findByEmailInCompany($email, $companyB->id);
    expect($foundB)->not->toBeNull();
    expect($foundB->id)->toBe($customerB->id);
});

test('inCompany scope filters correctly', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    // Create multiple customers
    Customer::factory()->count(3)->create(['company_id' => $companyA->id]);
    Customer::factory()->count(5)->create(['company_id' => $companyB->id]);

    // Test scope
    expect(Customer::inCompany($companyA->id)->count())->toBe(3);
    expect(Customer::inCompany($companyB->id)->count())->toBe(5);
});

test('handleUnknownCustomer creates customer in correct company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $phone = '+49777888999';

    // Create unknown customer placeholder in Company A
    $unknownA = DeterministicCustomerMatcher::handleUnknownCustomer($phone, $companyA->id);

    expect($unknownA)->not->toBeNull();
    expect($unknownA->company_id)->toBe($companyA->id);
    expect($unknownA->customer_type)->toBe('unknown');

    // Create unknown customer placeholder in Company B (should be separate)
    $unknownB = DeterministicCustomerMatcher::handleUnknownCustomer($phone, $companyB->id);

    expect($unknownB)->not->toBeNull();
    expect($unknownB->company_id)->toBe($companyB->id);
    expect($unknownB->id)->not->toBe($unknownA->id); // Different customer!
});
