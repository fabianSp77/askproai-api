<?php

namespace Tests\Unit\Repositories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Repositories\AppointmentRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SimplifiedMigrations;

class AppointmentRepositoryTest extends TestCase
{
    use SimplifiedMigrations;

    protected AppointmentRepository $repository;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new AppointmentRepository();
        
        // Create test company for tenant scoping
        $this->company = Company::factory()->create();
        
        // Set up tenant context
        app()->instance('current_company', $this->company);
    }

    /** @test */
    public function it_returns_correct_model_class_name()
    {
        $this->assertEquals(Appointment::class, $this->repository->model());
    }

    /** @test */
    #[Test]
    public function it_can_create_appointment()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'company_id' => $this->company->id,
            'notes' => 'Test appointment'
        ];

        $appointment = $this->repository->create($data);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($staff->id, $appointment->staff_id);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals('Test appointment', $appointment->notes);
    }

    /** @test */
    public function it_can_update_appointment()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);

        $result = $this->repository->update($appointment->id, [
            'status' => 'confirmed',
            'notes' => 'Updated notes'
        ]);

        $this->assertTrue($result);
        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals('Updated notes', $appointment->notes);
    }

    /** @test */
    #[Test]
    public function it_can_delete_appointment()
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);

        $result = $this->repository->delete($appointment->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    /** @test */
    public function it_can_find_appointment_by_id()
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);

        $found = $this->repository->find($appointment->id);

        $this->assertInstanceOf(Appointment::class, $found);
        $this->assertEquals($appointment->id, $found->id);
    }

    /** @test */
    #[Test]
    public function it_returns_null_when_appointment_not_found()
    {
        $found = $this->repository->find(999999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_get_all_appointments()
    {
        Appointment::factory()->count(5)->create(['company_id' => $this->company->id]);

        $appointments = $this->repository->all();

        $this->assertInstanceOf(Collection::class, $appointments);
        $this->assertCount(5, $appointments);
    }

    /** @test */
    #[Test]
    public function it_can_paginate_appointments()
    {
        Appointment::factory()->count(25)->create(['company_id' => $this->company->id]);

        $paginated = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(25, $paginated->total());
    }

    /** @test */
    public function it_can_get_appointments_by_date_range()
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        // Create appointments within range
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        // Create appointments outside range
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addMonth(),
        ]);

        $appointments = $this->repository->getByDateRange($startDate, $endDate);

        $this->assertCount(3, $appointments);
        $appointments->each(function ($appointment) use ($startDate, $endDate) {
            $this->assertTrue($appointment->starts_at->between($startDate, $endDate));
        });
    }

    /** @test */
    #[Test]
    public function it_can_get_appointments_by_staff()
    {
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $otherStaff = Staff::factory()->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'staff_id' => $otherStaff->id,
        ]);

        $appointments = $this->repository->getByStaff($staff->id);

        $this->assertCount(3, $appointments);
        $appointments->each(function ($appointment) use ($staff) {
            $this->assertEquals($staff->id, $appointment->staff_id);
        });
    }

    /** @test */
    public function it_can_get_appointments_by_staff_for_specific_date()
    {
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $date = Carbon::now()->addDay();

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $date,
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $date->copy()->addWeek(),
        ]);

        $appointments = $this->repository->getByStaff($staff->id, $date);

        $this->assertCount(2, $appointments);
    }

    /** @test */
    #[Test]
    public function it_can_get_appointments_by_customer()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'starts_at' => now()->addDay(),
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'starts_at' => now()->subDay(),
        ]);

        $appointments = $this->repository->getByCustomer($customer->id);

        $this->assertCount(2, $appointments); // Only future appointments by default
    }

    /** @test */
    public function it_can_get_all_appointments_for_customer_including_past()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'starts_at' => now()->addDay(),
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'starts_at' => now()->subDay(),
        ]);

        $appointments = $this->repository->getByCustomer($customer->id, false);

        $this->assertCount(3, $appointments);
    }

    /** @test */
    #[Test]
    public function it_can_get_overlapping_appointments()
    {
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $startTime = Carbon::now()->addDay()->setTime(10, 0);
        $endTime = $startTime->copy()->addHour();

        // Create overlapping appointments
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime->copy()->subMinutes(30),
            'ends_at' => $startTime->copy()->addMinutes(30),
            'status' => 'scheduled'
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime->copy()->addMinutes(30),
            'ends_at' => $endTime->copy()->addMinutes(30),
            'status' => 'scheduled'
        ]);

        // Create non-overlapping appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $endTime->copy()->addHours(2),
            'ends_at' => $endTime->copy()->addHours(3),
            'status' => 'scheduled'
        ]);

        // Create cancelled appointment (should be excluded)
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'status' => 'cancelled'
        ]);

        $overlapping = $this->repository->getOverlapping($staff->id, $startTime, $endTime);

        $this->assertCount(2, $overlapping);
    }

    /** @test */
    public function it_can_check_if_time_slot_is_available()
    {
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $startTime = Carbon::now()->addDay()->setTime(10, 0);
        $endTime = $startTime->copy()->addHour();

        // Time slot should be available initially
        $this->assertTrue($this->repository->isTimeSlotAvailable($staff->id, $startTime, $endTime));

        // Create conflicting appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'status' => 'scheduled'
        ]);

        // Time slot should no longer be available
        $this->assertFalse($this->repository->isTimeSlotAvailable($staff->id, $startTime, $endTime));
    }

    /** @test */
    #[Test]
    public function it_can_get_upcoming_appointments()
    {
        // Create future appointments
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->addDays(2),
            'status' => 'scheduled'
        ]);

        // Create past appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->subDay(),
            'status' => 'scheduled'
        ]);

        // Create cancelled future appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->addDay(),
            'status' => 'cancelled'
        ]);

        $upcoming = $this->repository->getUpcoming(5);

        $this->assertCount(3, $upcoming);
    }

    /** @test */
    public function it_can_get_appointments_by_status()
    {
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);

        $scheduled = $this->repository->getByStatus('scheduled');
        $completed = $this->repository->getByStatus('completed');

        $this->assertCount(3, $scheduled);
        $this->assertCount(2, $completed);
    }

    /** @test */
    #[Test]
    public function it_can_mark_appointments_as_no_show()
    {
        $beforeTime = Carbon::now()->subHours(2);

        // Create appointments that should be marked as no-show
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->subHours(3),
            'status' => 'scheduled'
        ]);

        // Create appointment that should not be marked
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->subHour(),
            'status' => 'scheduled'
        ]);

        $count = $this->repository->markAsNoShow($beforeTime);

        $this->assertEquals(2, $count);
        
        $noShows = Appointment::where('status', 'no_show')->count();
        $this->assertEquals(2, $noShows);
    }

    /** @test */
    public function it_can_get_appointment_statistics()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(5),
            'status' => 'completed',
            'price' => 100.00
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(3),
            'status' => 'cancelled'
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(2),
            'status' => 'no_show'
        ]);

        $stats = $this->repository->getStatistics($startDate, $endDate);

        $this->assertEquals(8, $stats['total']);
        $this->assertEquals(5, $stats['completed']);
        $this->assertEquals(2, $stats['cancelled']);
        $this->assertEquals(1, $stats['no_show']);
        $this->assertEquals(500.00, $stats['revenue']);
    }

    /** @test */
    #[Test]
    public function it_can_search_appointments()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith'
        ]);

        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'notes' => 'Regular checkup'
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $staff->id
        ]);

        // Search by customer name
        $results = $this->repository->search('John');
        $this->assertTrue($results->contains($appointment1));

        // Search by customer email
        $results = $this->repository->search('john@example');
        $this->assertTrue($results->contains($appointment1));

        // Search by staff name
        $results = $this->repository->search('Jane');
        $this->assertTrue($results->contains($appointment2));

        // Search by notes
        $results = $this->repository->search('checkup');
        $this->assertTrue($results->contains($appointment1));
    }

    /** @test */
    public function it_can_load_relationships_eagerly()
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id]);

        $result = $this->repository->with(['customer', 'staff', 'branch'])->find($appointment->id);

        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->relationLoaded('staff'));
        $this->assertTrue($result->relationLoaded('branch'));
    }

    /** @test */
    #[Test]
    public function it_can_apply_criteria()
    {
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled'
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'completed'
        ]);

        $appointments = $this->repository
            ->pushCriteria(function ($query) {
                $query->where('status', 'scheduled');
            })
            ->all();

        $this->assertCount(3, $appointments);
    }

    /** @test */
    public function it_can_order_results()
    {
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->addDays(3)
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->addDay()
        ]);

        $appointment3 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'starts_at' => now()->addDays(2)
        ]);

        $appointments = $this->repository->orderBy('starts_at', 'asc')->all();

        $this->assertEquals($appointment2->id, $appointments[0]->id);
        $this->assertEquals($appointment3->id, $appointments[1]->id);
        $this->assertEquals($appointment1->id, $appointments[2]->id);
    }

    /** @test */
    #[Test]
    public function it_can_update_or_create_appointment()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);

        // First call creates new appointment
        $appointment = $this->repository->updateOrCreate(
            ['customer_id' => $customer->id, 'staff_id' => $staff->id, 'starts_at' => now()->addDay()],
            ['status' => 'scheduled', 'company_id' => $this->company->id]
        );

        $this->assertEquals('scheduled', $appointment->status);

        // Second call updates existing appointment
        $updated = $this->repository->updateOrCreate(
            ['customer_id' => $customer->id, 'staff_id' => $staff->id, 'starts_at' => now()->addDay()],
            ['status' => 'confirmed']
        );

        $this->assertEquals($appointment->id, $updated->id);
        $this->assertEquals('confirmed', $updated->status);
    }

    /** @test */
    public function it_handles_database_transactions()
    {
        $this->expectException(\Exception::class);

        try {
            $this->repository->create([
                'invalid_field' => 'value' // This should cause an error
            ]);
        } catch (\Exception $e) {
            // Verify no partial data was saved
            $this->assertEquals(0, Appointment::count());
            throw $e;
        }
    }
}