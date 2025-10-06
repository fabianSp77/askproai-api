<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Tests\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Staff;
use App\Models\Customer;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(BaseTestCase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function actingAsAdmin(): User
{
    $user = User::factory()->create();
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $user->assignRole($adminRole);

    test()->actingAs($user);

    return $user;
}

function actingAsCompanyOwner(Company $company = null): User
{
    $company = $company ?? Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $ownerRole = Role::firstOrCreate(['name' => 'company_owner']);
    $user->assignRole($ownerRole);

    test()->actingAs($user);

    return $user;
}

function actingAsStaff(Staff $staff = null): User
{
    $staff = $staff ?? Staff::factory()->create();
    $user = User::factory()->create([
        'company_id' => $staff->company_id,
        'staff_id' => $staff->id
    ]);
    $staffRole = Role::firstOrCreate(['name' => 'staff']);
    $user->assignRole($staffRole);

    test()->actingAs($user);

    return $user;
}

function createCompanyWithFullSetup(): array
{
    $company = Company::factory()->create();
    $branch = \App\Models\Branch::factory()->create(['company_id' => $company->id]);
    $service = \App\Models\Service::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id
    ]);

    return compact('company', 'branch', 'service', 'staff');
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

expect()->extend('toBePhoneNumber', function () {
    return $this->toMatch('/^\+?[1-9]\d{1,14}$/');
});

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});