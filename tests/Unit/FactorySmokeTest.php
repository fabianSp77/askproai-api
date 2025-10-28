<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all factories create valid models with correct schema', function () {
    // Create complete data chain
    $company = Company::factory()->create();
    expect($company->id)->toBeGreaterThan(0);

    $branch = Branch::factory()->create(['company_id' => $company->id]);
    expect($branch->id)->toBeString()->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

    $staff = Staff::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);
    expect($staff->id)->toBeString()->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

    $service = Service::factory()->create(['company_id' => $company->id]);
    expect($service->id)->toBeGreaterThan(0);

    $customer = Customer::factory()->create(['company_id' => $company->id]);
    expect($customer->id)->toBeGreaterThan(0);

    $appointment = Appointment::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
    ]);

    expect($appointment->id)->toBeGreaterThan(0);
    expect($appointment->branch_id)->toBe($branch->id);
    expect($appointment->company_id)->toBe($company->id);
    expect($appointment->staff_id)->toBe($staff->id);
});
