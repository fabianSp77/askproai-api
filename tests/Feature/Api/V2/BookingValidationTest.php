<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Company;

class BookingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $branch;
    protected $staff;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    }

    /** @test */
    public function it_requires_service_id_for_booking()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_id']);
    }

    /** @test */
    public function it_requires_valid_service_id()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => 99999,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_id']);
    }

    /** @test */
    public function it_validates_customer_email_format()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'invalid-email'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer.email']);
    }

    /** @test */
    public function it_validates_customer_name_format()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => '123@#$%',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer.name']);
    }

    /** @test */
    public function it_validates_timezone()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Invalid/Timezone',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timeZone']);
    }

    /** @test */
    public function it_validates_start_time_format()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => '2024-01-01 10:00:00' // Wrong format
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start']);
    }

    /** @test */
    public function it_validates_start_time_is_in_future()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->subDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start']);
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => 'abc123'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer.phone']);
    }

    /** @test */
    public function it_validates_booking_source()
    {
        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'source' => 'invalid-source'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source']);
    }

    /** @test */
    public function it_sanitizes_customer_email_to_lowercase()
    {
        $request = new \App\Http\Requests\Api\V2\CreateBookingRequest();
        $request->replace([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'JOHN@EXAMPLE.COM'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $request->prepareForValidation();

        $this->assertEquals('john@example.com', $request->customer['email']);
    }

    /** @test */
    public function it_trims_customer_name()
    {
        $request = new \App\Http\Requests\Api\V2\CreateBookingRequest();
        $request->replace([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => '  John Doe  ',
                'email' => 'john@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => now()->addDay()->format('Y-m-d\TH:i:s')
        ]);

        $request->prepareForValidation();

        $this->assertEquals('John Doe', $request->customer['name']);
    }
}