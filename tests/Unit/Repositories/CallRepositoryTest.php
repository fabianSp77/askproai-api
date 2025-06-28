<?php

namespace Tests\Unit\Repositories;

use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Repositories\CallRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SimplifiedMigrations;

class CallRepositoryTest extends TestCase
{
    use SimplifiedMigrations;

    protected CallRepository $repository;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new CallRepository();
        
        // Create test company for tenant scoping
        $this->company = Company::factory()->create();
        
        // Set up tenant context
        app()->instance('current_company', $this->company);
    }

    /** @test */
    public function it_returns_correct_model_class_name()
    {
        $this->assertEquals(Call::class, $this->repository->model());
    }

    /** @test */
    #[Test]
    public function it_can_create_call()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $data = [
            'from_number' => '+1234567890',
            'to_number' => '+0987654321',
            'call_id' => 'call_123',
            'retell_call_id' => 'retell_123',
            'status' => 'completed',
            'duration_seconds' => 120,
            'customer_id' => $customer->id,
            'company_id' => $this->company->id,
            'cost_cents' => 50,
            'agent_id' => 'agent_123'
        ];

        $call = $this->repository->create($data);

        $this->assertInstanceOf(Call::class, $call);
        $this->assertEquals('+1234567890', $call->from_number);
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(120, $call->duration_seconds);
        $this->assertEquals(50, $call->cost_cents);
    }

    /** @test */
    public function it_can_update_call()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'initiated'
        ]);

        $result = $this->repository->update($call->id, [
            'status' => 'completed',
            'duration_seconds' => 180,
            'transcript' => 'Call transcript here'
        ]);

        $this->assertTrue($result);
        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(180, $call->duration_seconds);
        $this->assertEquals('Call transcript here', $call->transcript);
    }

    /** @test */
    #[Test]
    public function it_can_delete_call()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $result = $this->repository->delete($call->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('calls', ['id' => $call->id]);
    }

    /** @test */
    public function it_can_get_calls_by_status()
    {
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'failed'
        ]);

        $completedCalls = $this->repository->getByStatus('completed');
        $failedCalls = $this->repository->getByStatus('failed');

        $this->assertCount(3, $completedCalls);
        $this->assertCount(2, $failedCalls);
        
        // Check relationships are loaded
        $this->assertTrue($completedCalls->first()->relationLoaded('customer'));
        $this->assertTrue($completedCalls->first()->relationLoaded('appointment'));
        $this->assertTrue($completedCalls->first()->relationLoaded('agent'));
    }

    /** @test */
    #[Test]
    public function it_can_get_recent_calls()
    {
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(2)
        ]);

        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => now()
        ]);

        $recentCalls = $this->repository->getRecent(5);

        $this->assertCount(5, $recentCalls);
        
        // Verify ordered by most recent first
        $this->assertTrue($recentCalls->first()->created_at->isToday());
    }

    /** @test */
    public function it_can_get_calls_by_phone_number()
    {
        $phoneNumber = '+1234567890';

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'from_number' => $phoneNumber
        ]);

        Call::factory()->create([
            'company_id' => $this->company->id,
            'to_number' => $phoneNumber
        ]);

        Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+9999999999'
        ]);

        $calls = $this->repository->getByPhoneNumber($phoneNumber);

        $this->assertCount(3, $calls);
        $calls->each(function ($call) use ($phoneNumber) {
            $this->assertTrue(
                $call->from_number === $phoneNumber || 
                $call->to_number === $phoneNumber
            );
        });
    }

    /** @test */
    #[Test]
    public function it_can_get_calls_by_date_range()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->addDays(5)
        ]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMonth()
        ]);

        $calls = $this->repository->getByDateRange($startDate, $endDate);

        $this->assertCount(5, $calls);
        $calls->each(function ($call) use ($startDate, $endDate) {
            $this->assertTrue($call->created_at->between($startDate, $endDate));
        });
    }

    /** @test */
    public function it_can_get_calls_with_appointments()
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'appointment_id' => $appointment->id
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'appointment_id' => null
        ]);

        $calls = $this->repository->getWithAppointments();

        $this->assertCount(3, $calls);
        $calls->each(function ($call) {
            $this->assertNotNull($call->appointment_id);
            $this->assertTrue($call->relationLoaded('appointment'));
            $this->assertTrue($call->appointment->relationLoaded('customer'));
            $this->assertTrue($call->appointment->relationLoaded('staff'));
            $this->assertTrue($call->appointment->relationLoaded('service'));
        });
    }

    /** @test */
    #[Test]
    public function it_can_get_failed_calls()
    {
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'failed',
            'created_at' => now()->subHours(2)
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'failed',
            'created_at' => now()->subDays(2)
        ]);

        Call::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);

        // Get all failed calls
        $allFailed = $this->repository->getFailed();
        $this->assertCount(5, $allFailed);

        // Get failed calls since specific time
        $recentFailed = $this->repository->getFailed(now()->subDay());
        $this->assertCount(3, $recentFailed);
    }

    /** @test */
    public function it_can_get_call_statistics()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);

        // Create calls with different statuses
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'duration_seconds' => 120,
            'appointment_id' => $appointment->id,
            'created_at' => now()
        ]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'duration_seconds' => 180,
            'appointment_id' => null,
            'created_at' => now()
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'failed',
            'duration_seconds' => 0,
            'created_at' => now()
        ]);

        $stats = $this->repository->getStatistics($startDate, $endDate);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(8, $stats['completed']);
        $this->assertEquals(2, $stats['failed']);
        $this->assertEquals(135, $stats['average_duration']); // (5*120 + 3*180) / 8
        $this->assertEquals(1140, $stats['total_duration']); // 5*120 + 3*180
        $this->assertEquals(5, $stats['appointments_booked']);
        $this->assertEquals(50.0, $stats['conversion_rate']); // 5/10 * 100
    }

    /** @test */
    #[Test]
    public function it_handles_empty_statistics_gracefully()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $stats = $this->repository->getStatistics($startDate, $endDate);

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['average_duration']);
        $this->assertEquals(0, $stats['total_duration']);
        $this->assertEquals(0, $stats['appointments_booked']);
        $this->assertEquals(0, $stats['conversion_rate']);
    }

    /** @test */
    public function it_can_get_calls_by_agent()
    {
        $agentId = 'agent_123';

        Call::factory()->count(4)->create([
            'company_id' => $this->company->id,
            'agent_id' => $agentId
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'agent_id' => 'agent_456'
        ]);

        $calls = $this->repository->getByAgent($agentId);

        $this->assertCount(4, $calls);
        $calls->each(function ($call) use ($agentId) {
            $this->assertEquals($agentId, $call->agent_id);
        });
    }

    /** @test */
    #[Test]
    public function it_can_search_calls()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $call1 = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+1234567890',
            'customer_id' => $customer->id
        ]);

        $call2 = Call::factory()->create([
            'company_id' => $this->company->id,
            'to_number' => '+0987654321'
        ]);

        $call3 = Call::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => 'special_call_123',
            'retell_call_id' => 'retell_special_456'
        ]);

        // Search by phone number
        $results = $this->repository->search('123456');
        $this->assertTrue($results->contains($call1));

        // Search by to number
        $results = $this->repository->search('098765');
        $this->assertTrue($results->contains($call2));

        // Search by call ID
        $results = $this->repository->search('special_call');
        $this->assertTrue($results->contains($call3));

        // Search by retell call ID
        $results = $this->repository->search('retell_special');
        $this->assertTrue($results->contains($call3));

        // Search by customer name
        $results = $this->repository->search('John');
        $this->assertTrue($results->contains($call1));

        // Search by customer email
        $results = $this->repository->search('john@example');
        $this->assertTrue($results->contains($call1));
    }

    /** @test */
    public function it_can_get_calls_with_transcripts()
    {
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'transcript' => 'This is a transcript'
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'transcript' => null
        ]);

        $calls = $this->repository->getWithTranscripts(10);

        $this->assertCount(3, $calls);
        $calls->each(function ($call) {
            $this->assertNotNull($call->transcript);
        });
    }

    /** @test */
    #[Test]
    public function it_can_update_call_from_webhook()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_123',
            'status' => 'initiated',
            'duration_seconds' => 0
        ]);

        $webhookData = [
            'status' => 'completed',
            'duration' => 240,
            'transcript' => 'Customer: Hello, I need an appointment...',
            'analysis' => ['sentiment' => 'positive'],
            'cost' => 75,
            'extra_field' => 'extra_value'
        ];

        $result = $this->repository->updateFromWebhook('retell_123', $webhookData);

        $this->assertTrue($result);
        
        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(240, $call->duration_seconds);
        $this->assertEquals('Customer: Hello, I need an appointment...', $call->transcript);
        $this->assertEquals(['sentiment' => 'positive'], $call->analysis);
        $this->assertEquals(75, $call->cost_cents);
        $this->assertArrayHasKey('extra_field', $call->webhook_data);
    }

    /** @test */
    public function it_returns_false_when_updating_non_existent_call_from_webhook()
    {
        $result = $this->repository->updateFromWebhook('non_existent_id', [
            'status' => 'completed'
        ]);

        $this->assertFalse($result);
    }

    /** @test */
    #[Test]
    public function it_merges_webhook_data_correctly()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'retell_123',
            'webhook_data' => ['existing' => 'data']
        ]);

        $webhookData = [
            'new_field' => 'new_value',
            'status' => 'completed'
        ];

        $this->repository->updateFromWebhook('retell_123', $webhookData);

        $call->refresh();
        $this->assertEquals('data', $call->webhook_data['existing']);
        $this->assertEquals('new_value', $call->webhook_data['new_field']);
    }

    /** @test */
    public function it_can_paginate_calls()
    {
        Call::factory()->count(25)->create(['company_id' => $this->company->id]);

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(25, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    /** @test */
    #[Test]
    public function it_can_apply_criteria_to_queries()
    {
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'duration_seconds' => 300
        ]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'duration_seconds' => 60
        ]);

        // Apply criteria for long calls
        $longCalls = $this->repository
            ->pushCriteria(function ($query) {
                $query->where('duration_seconds', '>', 120);
            })
            ->all();

        $this->assertCount(5, $longCalls);
    }

    /** @test */
    public function it_can_order_results()
    {
        $call1 = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_seconds' => 300
        ]);

        $call2 = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_seconds' => 100
        ]);

        $call3 = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration_seconds' => 200
        ]);

        $calls = $this->repository->orderBy('duration_seconds', 'asc')->all();

        $this->assertEquals($call2->id, $calls[0]->id);
        $this->assertEquals($call3->id, $calls[1]->id);
        $this->assertEquals($call1->id, $calls[2]->id);
    }

    /** @test */
    #[Test]
    public function it_can_count_calls()
    {
        Call::factory()->count(7)->create(['company_id' => $this->company->id]);

        $count = $this->repository->count();
        $this->assertEquals(7, $count);

        // Count with criteria
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'failed'
        ]);

        $failedCount = $this->repository->count(['status' => 'failed']);
        $this->assertEquals(3, $failedCount);
    }

    /** @test */
    public function it_can_check_if_call_exists()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'unique_123'
        ]);

        $exists = $this->repository->exists(['retell_call_id' => 'unique_123']);
        $this->assertTrue($exists);

        $exists = $this->repository->exists(['retell_call_id' => 'non_existent']);
        $this->assertFalse($exists);
    }

    /** @test */
    #[Test]
    public function it_can_load_relationships()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);
        
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'appointment_id' => $appointment->id
        ]);

        $result = $this->repository->with(['customer', 'appointment'])->find($call->id);

        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->relationLoaded('appointment'));
        $this->assertEquals($customer->id, $result->customer->id);
        $this->assertEquals($appointment->id, $result->appointment->id);
    }

    /** @test */
    public function it_handles_null_values_in_statistics()
    {
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'duration_seconds' => null,
            'appointment_id' => null
        ]);

        $stats = $this->repository->getStatistics(now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(0, $stats['average_duration']);
        $this->assertEquals(0, $stats['total_duration']);
        $this->assertEquals(0, $stats['appointments_booked']);
    }

    /** @test */
    #[Test]
    public function it_can_handle_complex_search_queries()
    {
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Another Customer',
            'email' => 'another@test.com'
        ]);

        Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
            'from_number' => '+1234567890'
        ]);

        Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id,
            'to_number' => '+1234567890'
        ]);

        // Search should find both calls with the same phone number
        $results = $this->repository->search('1234567890');
        $this->assertCount(2, $results);

        // Search should limit results
        $this->assertLessThanOrEqual(50, $results->count());
    }
}