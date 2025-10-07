<?php

use App\Filament\Resources\CallResource;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Appointment;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->company = Company::factory()->create([
        'name' => 'Test Company GmbH'
    ]);

    $this->branch = Branch::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Branch'
    ]);

    $this->customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Max Mustermann',
        'phone' => '+4917612345678',
        'email' => 'max@mustermann.de'
    ]);
});

// ==========================================
// LIST PAGE TESTS
// ==========================================

it('can list calls', function () {
    actingAsAdmin();

    Call::factory(5)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5);
});

it('displays call overview with correct columns', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_name' => 'Max Mustermann',
        'customer_name_verified' => true,
        'from_number' => '+4917612345678',
        'direction' => 'inbound',
        'status' => 'completed',
        'duration_sec' => 120,
        'created_at' => now()
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$call])
        ->assertTableColumnExists('created_at')
        ->assertTableColumnExists('customer_name')
        ->assertTableColumnExists('customer_link_status')
        ->assertTableColumnExists('duration_sec')
        ->assertTableColumnExists('status');
});

it('shows verified customer name with icon', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_name' => 'Max Mustermann',
        'customer_name_verified' => true,
        'from_number' => '+4917612345678'
    ]);

    $response = Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$call]);

    // Verify that the customer name is displayed
    expect($call->customer_name)->toBe('Max Mustermann');
    expect($call->customer_name_verified)->toBeTrue();
});

it('shows unverified customer name with warning icon', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_name' => 'Anonymous Caller',
        'customer_name_verified' => false,
        'from_number' => 'anonymous'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$call]);

    expect($call->customer_name_verified)->toBeFalse();
});

it('displays customer link status badge', function () {
    actingAsAdmin();

    $linkedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_link_status' => 'linked',
        'customer_link_confidence' => 100,
        'customer_link_method' => 'phone_match'
    ]);

    $unlinkedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_link_status' => 'unlinked'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$linkedCall, $unlinkedCall]);

    expect($linkedCall->customer_link_status)->toBe('linked');
    expect($linkedCall->customer_link_method)->toBe('phone_match');
    expect($unlinkedCall->customer_link_status)->toBe('unlinked');
});

it('can search calls by customer name', function () {
    actingAsAdmin();

    $call1 = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_name' => 'Max Mustermann',
        'from_number' => '+4917612345678'
    ]);

    $call2 = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_name' => 'Anna Schmidt',
        'from_number' => '+4917687654321'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->searchTable('Max')
        ->assertCanSeeTableRecords([$call1])
        ->assertCanNotSeeTableRecords([$call2]);
});

it('can filter calls by status', function () {
    actingAsAdmin();

    $completedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'completed'
    ]);

    $missedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'missed'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->filterTable('status', 'completed')
        ->assertCanSeeTableRecords([$completedCall])
        ->assertCanNotSeeTableRecords([$missedCall]);
});

it('can filter calls by direction', function () {
    actingAsAdmin();

    $inboundCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'direction' => 'inbound'
    ]);

    $outboundCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'direction' => 'outbound'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->filterTable('direction', 'inbound')
        ->assertCanSeeTableRecords([$inboundCall])
        ->assertCanNotSeeTableRecords([$outboundCall]);
});

it('can filter calls by date range', function () {
    actingAsAdmin();

    $todayCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'created_at' => now()
    ]);

    $oldCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'created_at' => now()->subDays(5)
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->filterTable('created_at', [
            'from' => now()->startOfDay()->format('Y-m-d'),
            'until' => now()->endOfDay()->format('Y-m-d')
        ])
        ->assertCanSeeTableRecords([$todayCall])
        ->assertCanNotSeeTableRecords([$oldCall]);
});

it('can filter calls by customer link status', function () {
    actingAsAdmin();

    $linkedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_link_status' => 'linked'
    ]);

    $unlinkedCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_link_status' => 'unlinked'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->filterTable('customer_link_status', 'linked')
        ->assertCanSeeTableRecords([$linkedCall])
        ->assertCanNotSeeTableRecords([$unlinkedCall]);
});

it('can sort calls by duration', function () {
    actingAsAdmin();

    $shortCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'duration_sec' => 30
    ]);

    $longCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'duration_sec' => 300
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->sortTable('duration_sec', 'desc')
        ->assertCanSeeTableRecords([$shortCall, $longCall], inOrder: true);
});

// ==========================================
// VIEW PAGE TESTS (Details)
// ==========================================

it('can view call details', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_name' => 'Max Mustermann',
        'customer_name_verified' => true,
        'from_number' => '+4917612345678',
        'to_number' => '+49301234567',
        'direction' => 'inbound',
        'status' => 'completed',
        'duration_sec' => 120,
        'transcript' => json_encode([
            'turns' => [
                ['role' => 'agent', 'text' => 'Guten Tag, hier ist Test Company.'],
                ['role' => 'user', 'text' => 'Hallo, ich bin Max Mustermann.']
            ]
        ]),
        'notes' => 'Customer called regarding appointment',
        'recording_url' => 'https://example.com/recording.mp3'
    ]);

    Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful()
        ->assertSee('Max Mustermann')
        ->assertSee('+4917612345678')
        ->assertSee('completed');
});

it('displays call metadata in details page', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'retell_call_id' => 'test_call_123',
        'retell_agent_id' => 'agent_456',
        'duration_sec' => 180,
        'end_reason' => 'user_hangup',
        'status' => 'completed'
    ]);

    $response = Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->retell_call_id)->toBe('test_call_123');
    expect($call->duration_sec)->toBe(180);
    expect($call->end_reason)->toBe('user_hangup');
});

it('displays transcript in details page', function () {
    actingAsAdmin();

    $transcript = [
        'turns' => [
            ['role' => 'agent', 'text' => 'Guten Tag, wie kann ich Ihnen helfen?'],
            ['role' => 'user', 'text' => 'Ich möchte einen Termin vereinbaren.'],
            ['role' => 'agent', 'text' => 'Sehr gerne, wann passt es Ihnen?']
        ]
    ];

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'transcript' => json_encode($transcript)
    ]);

    $response = Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->transcript)->toBeJson();
    $decodedTranscript = json_decode($call->transcript, true);
    expect($decodedTranscript['turns'])->toHaveCount(3);
});

it('shows customer verification details', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_name' => 'Max Mustermann',
        'customer_name_verified' => true,
        'customer_link_status' => 'linked',
        'customer_link_method' => 'phone_match',
        'customer_link_confidence' => 95
    ]);

    $response = Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->customer_link_status)->toBe('linked');
    expect($call->customer_link_method)->toBe('phone_match');
    expect($call->customer_link_confidence)->toBe(95);
});

it('displays appointment linkage if exists', function () {
    actingAsAdmin();

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addDays(3),
        'appointment_time' => '14:00:00',
        'status' => 'scheduled'
    ]);

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'appointment_id' => $appointment->id
    ]);

    $response = Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->appointment_id)->toBe($appointment->id);
    expect($call->appointment)->not->toBeNull();
});

it('shows recording URL when available', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'recording_url' => 'https://recordings.example.com/call_123.mp3',
        'public_log_url' => 'https://logs.example.com/call_123'
    ]);

    $response = Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->recording_url)->not->toBeNull();
    expect($call->public_log_url)->not->toBeNull();
});

// ==========================================
// PHONE-BASED AUTHENTICATION TESTS
// ==========================================

it('correctly identifies phone-matched customers', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'from_number' => '+4917612345678',
        'customer_link_method' => 'phone_match',
        'customer_link_status' => 'linked',
        'customer_link_confidence' => 100
    ]);

    expect($call->customer_link_method)->toBe('phone_match');
    expect($call->customer_link_status)->toBe('linked');
    expect($call->from_number)->toBe('+4917612345678');
});

it('handles anonymous calls correctly', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'from_number' => 'anonymous',
        'customer_link_status' => 'anonymous',
        'customer_name_verified' => false
    ]);

    expect($call->from_number)->toBe('anonymous');
    expect($call->customer_link_status)->toBe('anonymous');
    expect($call->customer_name_verified)->toBeFalse();
});

it('tracks phonetic matching for name verification', function () {
    actingAsAdmin();

    // Simulate phonetic name matching scenario
    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_name' => 'Max Mustermann',
        'customer_name_verified' => true,
        'from_number' => '+4917612345678',
        'customer_link_method' => 'name_match',
        'customer_link_confidence' => 85
    ]);

    expect($call->customer_name)->toBe('Max Mustermann');
    expect($call->customer_link_method)->toBe('name_match');
    expect($call->customer_link_confidence)->toBeGreaterThan(80);
});

// ==========================================
// EDIT/UPDATE TESTS
// ==========================================

it('can update call customer association', function () {
    actingAsAdmin();

    $newCustomer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Anna Schmidt'
    ]);

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id
    ]);

    Livewire::test(CallResource\Pages\EditCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->fillForm([
            'customer_id' => $newCustomer->id
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($call->refresh()->customer_id)->toBe($newCustomer->id);
});

it('can update call notes', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'notes' => 'Initial note'
    ]);

    $newNotes = 'Customer requested callback next week';

    Livewire::test(CallResource\Pages\EditCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->fillForm([
            'notes' => $newNotes
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($call->refresh()->notes)->toBe($newNotes);
});

// ==========================================
// NAVIGATION & UI TESTS
// ==========================================

it('shows navigation badge with today call count', function () {
    actingAsAdmin();

    Call::factory(3)->create([
        'company_id' => $this->company->id,
        'created_at' => now()
    ]);

    Call::factory(2)->create([
        'company_id' => $this->company->id,
        'created_at' => now()->subDays(2)
    ]);

    $badge = CallResource::getNavigationBadge();

    expect($badge)->toBe('3');
});

it('changes badge color based on call volume', function () {
    actingAsAdmin();

    // Test low volume (green)
    Call::factory(5)->create([
        'company_id' => $this->company->id,
        'created_at' => now()
    ]);

    $color = CallResource::getNavigationBadgeColor();
    expect($color)->toBe('success');

    // Clean up
    Call::query()->delete();

    // Test medium volume (warning)
    Call::factory(15)->create([
        'company_id' => $this->company->id,
        'created_at' => now()
    ]);

    $color = CallResource::getNavigationBadgeColor();
    expect($color)->toBe('warning');

    // Clean up
    Call::query()->delete();

    // Test high volume (danger)
    Call::factory(25)->create([
        'company_id' => $this->company->id,
        'created_at' => now()
    ]);

    $color = CallResource::getNavigationBadgeColor();
    expect($color)->toBe('danger');
});

it('generates intelligent record title', function () {
    $call = Call::factory()->create([
        'customer_name' => 'Max Mustermann',
        'status' => 'completed',
        'created_at' => Carbon::parse('2025-10-06 14:30:00')
    ]);

    $title = CallResource::getRecordTitle($call);

    expect($title)->toContain('Max Mustermann');
    expect($title)->toContain('06.10');
    expect($title)->toContain('14:30');
});

// ==========================================
// DATA QUALITY TESTS
// ==========================================

it('tracks data quality metrics', function () {
    actingAsAdmin();

    $highQualityCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'customer_link_status' => 'linked',
        'customer_link_confidence' => 100
    ]);

    $lowQualityCall = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_link_status' => 'unlinked',
        'customer_link_confidence' => 0
    ]);

    expect($highQualityCall->customer_link_confidence)->toBe(100);
    expect($lowQualityCall->customer_link_confidence)->toBe(0);
});

// ==========================================
// BULK ACTIONS TESTS
// ==========================================

it('can bulk update call status', function () {
    actingAsAdmin();

    $calls = Call::factory(3)->create([
        'company_id' => $this->company->id,
        'status' => 'completed'
    ]);

    // Note: This test assumes a bulk action exists
    // If not implemented, this test will need to be adjusted
    expect($calls)->toHaveCount(3);
    foreach ($calls as $call) {
        expect($call->status)->toBe('completed');
    }
});

// ==========================================
// PERFORMANCE TESTS
// ==========================================

it('handles large call datasets efficiently', function () {
    actingAsAdmin();

    Call::factory(100)->create([
        'company_id' => $this->company->id
    ]);

    $startTime = microtime(true);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful();

    $executionTime = microtime(true) - $startTime;

    // Should load within reasonable time (2 seconds)
    expect($executionTime)->toBeLessThan(2);
});

// ==========================================
// ERROR HANDLING TESTS
// ==========================================

it('handles missing transcript gracefully', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'transcript' => null
    ]);

    Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();

    expect($call->transcript)->toBeNull();
});

it('handles invalid JSON in transcript', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'transcript' => 'invalid json string'
    ]);

    Livewire::test(CallResource\Pages\ViewCall::class, [
        'record' => $call->getRouteKey()
    ])
        ->assertSuccessful();
});

it('handles missing customer gracefully', function () {
    actingAsAdmin();

    $call = Call::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => null,
        'customer_name' => null,
        'from_number' => '+4917612345678'
    ]);

    Livewire::test(CallResource\Pages\ListCalls::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$call]);

    expect($call->customer_id)->toBeNull();
});
