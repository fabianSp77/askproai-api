<?php

use App\Filament\Resources\CallbackRequestResource;
use App\Models\CallbackRequest;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\CallbackEscalation;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Create test data
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    $this->service = Service::factory()->create(['company_id' => $this->company->id]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->staff = Staff::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
    ]);
});

// ============================================================================
// CREATE TESTS
// ============================================================================

it('can render create page', function () {
    actingAsAdmin();

    Livewire::test(CallbackRequestResource\Pages\CreateCallbackRequest::class)
        ->assertSuccessful();
});

it('can create callback request', function () {
    actingAsAdmin();

    $callbackData = [
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'phone_number' => '+49 30 12345678',
        'customer_name' => 'Test Customer',
        'priority' => CallbackRequest::PRIORITY_NORMAL,
        'status' => CallbackRequest::STATUS_PENDING,
        'preferred_time_window' => ['Monday' => '9:00-12:00'],
        'notes' => 'Test callback request',
    ];

    Livewire::test(CallbackRequestResource\Pages\CreateCallbackRequest::class)
        ->fillForm($callbackData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('callback_requests', [
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'phone_number' => '+49 30 12345678',
        'customer_name' => 'Test Customer',
        'priority' => CallbackRequest::PRIORITY_NORMAL,
        'status' => CallbackRequest::STATUS_PENDING,
    ]);
});

it('validates required fields on create', function () {
    actingAsAdmin();

    Livewire::test(CallbackRequestResource\Pages\CreateCallbackRequest::class)
        ->fillForm([
            'customer_name' => '',
            'phone_number' => '',
            'branch_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['customer_name', 'phone_number', 'branch_id']);
});

it('auto populates customer data on select', function () {
    actingAsAdmin();

    $component = Livewire::test(CallbackRequestResource\Pages\CreateCallbackRequest::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
        ])
        ->assertSuccessful();

    // The afterStateUpdated callback should populate phone and name
    $component->assertFormSet([
        'customer_id' => $this->customer->id,
    ]);
});

// ============================================================================
// LIST TESTS
// ============================================================================

it('can render list page', function () {
    actingAsAdmin();

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->assertSuccessful();
});

it('can list callback requests', function () {
    actingAsAdmin();

    CallbackRequest::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(CallbackRequest::all());
});

it('can filter by status', function () {
    actingAsAdmin();

    $pendingCallback = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    $completedCallback = CallbackRequest::factory()->completed()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->filterTable('status', [CallbackRequest::STATUS_PENDING])
        ->assertCanSeeTableRecords([$pendingCallback])
        ->assertCanNotSeeTableRecords([$completedCallback]);
});

it('can filter by priority', function () {
    actingAsAdmin();

    $urgentCallback = CallbackRequest::factory()->urgent()->create([
        'branch_id' => $this->branch->id,
    ]);

    $normalCallback = CallbackRequest::factory()->normalPriority()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->filterTable('priority', [CallbackRequest::PRIORITY_URGENT])
        ->assertCanSeeTableRecords([$urgentCallback])
        ->assertCanNotSeeTableRecords([$normalCallback]);
});

it('can search by customer name', function () {
    actingAsAdmin();

    $callback1 = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'customer_name' => 'John Doe',
    ]);

    $callback2 = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'customer_name' => 'Jane Smith',
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->searchTable('John Doe')
        ->assertCanSeeTableRecords([$callback1])
        ->assertCanNotSeeTableRecords([$callback2]);
});

// ============================================================================
// VIEW TESTS
// ============================================================================

it('can render view page', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ViewCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('displays callback details', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'customer_name' => 'Test Customer',
        'phone_number' => '+49 30 99999999',
        'notes' => 'Important callback',
    ]);

    Livewire::test(CallbackRequestResource\Pages\ViewCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee('Test Customer')
        ->assertSee('+49 30 99999999')
        ->assertSee('Important callback');
});

it('shows escalations if present', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    $escalation = CallbackEscalation::factory()->create([
        'callback_request_id' => $callback->id,
        'escalation_reason' => 'Customer complaint',
        'escalated_from' => $this->staff->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ViewCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee('Customer complaint');
});

// ============================================================================
// EDIT TESTS
// ============================================================================

it('can render edit page', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\EditCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update callback request', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'customer_name' => 'Original Name',
    ]);

    Livewire::test(CallbackRequestResource\Pages\EditCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->fillForm([
            'customer_name' => 'Updated Name',
            'priority' => CallbackRequest::PRIORITY_HIGH,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $callback->refresh();

    expect($callback->customer_name)->toBe('Updated Name');
    expect($callback->priority)->toBe(CallbackRequest::PRIORITY_HIGH);
});

it('validates required fields on update', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\EditCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->fillForm([
            'customer_name' => '',
            'phone_number' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['customer_name', 'phone_number']);
});

// ============================================================================
// ACTION TESTS
// ============================================================================

it('can assign callback to staff', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($callback->assigned_to)->toBeNull();
    expect($callback->status)->toBe(CallbackRequest::STATUS_PENDING);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableAction('assign', $callback, data: [
            'staff_id' => $this->staff->id,
        ]);

    $callback->refresh();

    expect($callback->assigned_to)->toBe($this->staff->id);
    expect($callback->status)->toBe(CallbackRequest::STATUS_ASSIGNED);
    expect($callback->assigned_at)->not->toBeNull();
});

it('can mark callback as contacted', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->assigned()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    expect($callback->status)->toBe(CallbackRequest::STATUS_ASSIGNED);
    expect($callback->contacted_at)->toBeNull();

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableAction('markContacted', $callback);

    $callback->refresh();

    expect($callback->status)->toBe(CallbackRequest::STATUS_CONTACTED);
    expect($callback->contacted_at)->not->toBeNull();
});

it('can mark callback as completed', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->contacted()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    expect($callback->status)->toBe(CallbackRequest::STATUS_CONTACTED);
    expect($callback->completed_at)->toBeNull();

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableAction('markCompleted', $callback, data: [
            'notes' => 'Successfully resolved',
        ]);

    $callback->refresh();

    expect($callback->status)->toBe(CallbackRequest::STATUS_COMPLETED);
    expect($callback->completed_at)->not->toBeNull();
    expect($callback->notes)->toContain('Successfully resolved');
});

it('can escalate callback', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->assigned()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    expect($callback->escalations()->count())->toBe(0);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableAction('escalate', $callback, data: [
            'reason' => 'no_response',
            'details' => 'Customer not responding',
        ]);

    $callback->refresh();

    expect($callback->escalations()->count())->toBe(1);

    $escalation = $callback->escalations()->first();
    expect($escalation->escalation_reason)->toContain('no_response');
    expect($escalation->escalation_reason)->toContain('Customer not responding');
});

it('cannot assign if already assigned', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->assigned()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->assertTableActionHidden('assign', $callback);
});

it('cannot mark contacted if not assigned', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->assertTableActionHidden('markContacted', $callback);
});

// ============================================================================
// DELETE TESTS
// ============================================================================

it('can soft delete callback', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\EditCallbackRequest::class, [
        'record' => $callback->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertSoftDeleted('callback_requests', [
        'id' => $callback->id,
    ]);
});

it('can restore deleted callback', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $callback->delete();

    expect($callback->trashed())->toBeTrue();

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableBulkAction('restore', [$callback]);

    $callback->refresh();

    expect($callback->trashed())->toBeFalse();
});

it('can force delete callback', function () {
    actingAsAdmin();

    $callback = CallbackRequest::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $callback->delete();

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableBulkAction('forceDelete', [$callback]);

    $this->assertDatabaseMissing('callback_requests', [
        'id' => $callback->id,
    ]);
});

// ============================================================================
// BULK ACTION TESTS
// ============================================================================

it('can bulk assign callbacks to staff', function () {
    actingAsAdmin();

    $callback1 = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    $callback2 = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableBulkAction('bulkAssign', [$callback1, $callback2], data: [
            'staff_id' => $this->staff->id,
        ]);

    $callback1->refresh();
    $callback2->refresh();

    expect($callback1->assigned_to)->toBe($this->staff->id);
    expect($callback1->status)->toBe(CallbackRequest::STATUS_ASSIGNED);
    expect($callback2->assigned_to)->toBe($this->staff->id);
    expect($callback2->status)->toBe(CallbackRequest::STATUS_ASSIGNED);
});

it('can bulk complete callbacks', function () {
    actingAsAdmin();

    $callback1 = CallbackRequest::factory()->contacted()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    $callback2 = CallbackRequest::factory()->contacted()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $this->staff->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->callTableBulkAction('bulkComplete', [$callback1, $callback2]);

    $callback1->refresh();
    $callback2->refresh();

    expect($callback1->status)->toBe(CallbackRequest::STATUS_COMPLETED);
    expect($callback1->completed_at)->not->toBeNull();
    expect($callback2->status)->toBe(CallbackRequest::STATUS_COMPLETED);
    expect($callback2->completed_at)->not->toBeNull();
});

// ============================================================================
// NAVIGATION AND BADGE TESTS
// ============================================================================

it('displays navigation badge with pending count', function () {
    actingAsAdmin();

    CallbackRequest::factory()->pending()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    CallbackRequest::factory()->completed()->count(2)->create([
        'branch_id' => $this->branch->id,
    ]);

    $badge = CallbackRequestResource::getNavigationBadge();

    expect($badge)->toBe('3');
});

it('changes badge color based on pending count', function () {
    actingAsAdmin();

    // Test with low count (info)
    CallbackRequest::factory()->pending()->count(2)->create([
        'branch_id' => $this->branch->id,
    ]);
    expect(CallbackRequestResource::getNavigationBadgeColor())->toBe('info');

    // Clean up
    CallbackRequest::truncate();

    // Test with medium count (warning)
    CallbackRequest::factory()->pending()->count(7)->create([
        'branch_id' => $this->branch->id,
    ]);
    expect(CallbackRequestResource::getNavigationBadgeColor())->toBe('warning');

    // Clean up
    CallbackRequest::truncate();

    // Test with high count (danger)
    CallbackRequest::factory()->pending()->count(12)->create([
        'branch_id' => $this->branch->id,
    ]);
    expect(CallbackRequestResource::getNavigationBadgeColor())->toBe('danger');
});

// ============================================================================
// OVERDUE FUNCTIONALITY TESTS
// ============================================================================

it('can filter overdue callbacks', function () {
    actingAsAdmin();

    $overdueCallback = CallbackRequest::factory()->overdue()->create([
        'branch_id' => $this->branch->id,
    ]);

    $normalCallback = CallbackRequest::factory()->pending()->create([
        'branch_id' => $this->branch->id,
        'expires_at' => now()->addDays(2),
    ]);

    Livewire::test(CallbackRequestResource\Pages\ListCallbackRequests::class)
        ->filterTable('overdue', true)
        ->assertCanSeeTableRecords([$overdueCallback])
        ->assertCanNotSeeTableRecords([$normalCallback]);
});

it('displays overdue indicator in view page', function () {
    actingAsAdmin();

    $overdueCallback = CallbackRequest::factory()->overdue()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(CallbackRequestResource\Pages\ViewCallbackRequest::class, [
        'record' => $overdueCallback->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee('Ja'); // German for "Yes" in overdue field
});
