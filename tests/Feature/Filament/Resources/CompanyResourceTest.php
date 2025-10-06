<?php

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Livewire\Livewire;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('can list companies as admin', function () {
    actingAsAdmin();

    Company::factory(3)->create();

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});

it('cannot list companies without permission', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->assertForbidden();
});

it('can create company with valid data', function () {
    actingAsAdmin();

    $companyData = [
        'name' => 'Test Company GmbH',
        'email' => 'info@testcompany.de',
        'phone' => '+49 30 12345678',
        'status' => 'active',
        'type' => 'standard',
        'address' => 'Teststraße 123',
        'city' => 'Berlin',
        'state' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'Germany',
        'timezone' => 'Europe/Berlin',
        'language' => 'de',
        'currency' => 'EUR',
    ];

    Livewire::test(CompanyResource\Pages\CreateCompany::class)
        ->fillForm($companyData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('companies', [
        'name' => 'Test Company GmbH',
        'email' => 'info@testcompany.de'
    ]);
});

it('validates required fields when creating company', function () {
    actingAsAdmin();

    Livewire::test(CompanyResource\Pages\CreateCompany::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required'
        ]);
});

it('can edit existing company', function () {
    actingAsAdmin();

    $company = Company::factory()->create();

    Livewire::test(CompanyResource\Pages\EditCompany::class, [
        'record' => $company->getRouteKey()
    ])
        ->assertFormSet([
            'name' => $company->name,
            'email' => $company->email
        ])
        ->fillForm([
            'name' => 'Updated Company Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->refresh()->name)->toBe('Updated Company Name');
});

it('can view company details', function () {
    actingAsAdmin();

    $company = Company::factory()->create();

    Livewire::test(CompanyResource\Pages\ViewCompany::class, [
        'record' => $company->getRouteKey()
    ])
        ->assertSuccessful()
        ->assertSee($company->name)
        ->assertSee($company->email);
});

it('can delete company', function () {
    actingAsAdmin();

    $company = Company::factory()->create();

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->callTableAction(DeleteAction::class, $company);

    $this->assertDatabaseMissing('companies', [
        'id' => $company->id
    ]);
});

it('can filter companies by status', function () {
    actingAsAdmin();

    Company::factory(2)->create(['status' => 'active']);
    Company::factory(1)->create(['status' => 'inactive']);

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->assertCountTableRecords(3)
        ->filterTable('status', 'active')
        ->assertCountTableRecords(2)
        ->filterTable('status', 'inactive')
        ->assertCountTableRecords(1);
});

it('can search companies by name', function () {
    actingAsAdmin();

    Company::factory()->create(['name' => 'Alpha Corporation']);
    Company::factory()->create(['name' => 'Beta Solutions']);
    Company::factory()->create(['name' => 'Gamma Industries']);

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->searchTable('Beta')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([
            Company::where('name', 'like', '%Beta%')->first()
        ]);
});

it('respects multi-tenancy when listing companies', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $owner1 = actingAsCompanyOwner($company1);

    Livewire::test(CompanyResource\Pages\ListCompanies::class)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$company1])
        ->assertCanNotSeeTableRecords([$company2]);
});

it('can manage company relation managers', function () {
    actingAsAdmin();

    $company = Company::factory()->create();
    $branches = \App\Models\Branch::factory(3)->create(['company_id' => $company->id]);

    Livewire::test(CompanyResource\RelationManagers\BranchesRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => CompanyResource\Pages\EditCompany::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});