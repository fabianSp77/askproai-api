<?php

namespace Tests\Feature\Models;

use App\Models\Service;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ServiceProcessingTimeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company using DB to bypass observers
        $companyId = \DB::table('companies')->insertGetId([
            'name' => 'Test Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->company = Company::find($companyId);
    }

    protected function createService(array $attributes = []): Service
    {
        $serviceId = \DB::table('services')->insertGetId(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'duration_minutes' => 60,
            'price' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Service::find($serviceId);
    }

    /** @test */
    public function service_without_processing_time_returns_false()
    {
        $service = $this->createService([
            'has_processing_time' => false,
        ]);

        $this->assertFalse($service->hasProcessingTime());
    }

    /** @test */
    public function service_with_processing_time_returns_true()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $this->assertTrue($service->hasProcessingTime());
    }

    /** @test */
    public function get_total_duration_returns_service_duration_for_regular_service()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => false,
        ]);

        $this->assertEquals(60, $service->getTotalDuration());
    }

    /** @test */
    public function get_total_duration_sums_all_phases_for_processing_time_service()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $this->assertEquals(60, $service->getTotalDuration());
    }

    /** @test */
    public function get_phases_duration_returns_null_for_regular_service()
    {
        $service = $this->createService([
            'has_processing_time' => false,
        ]);

        $this->assertNull($service->getPhasesDuration());
    }

    /** @test */
    public function get_phases_duration_returns_breakdown_for_processing_time_service()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $phases = $service->getPhasesDuration();

        $this->assertEquals(15, $phases['initial']);
        $this->assertEquals(25, $phases['processing']);
        $this->assertEquals(20, $phases['final']);
        $this->assertEquals(60, $phases['total']);
    }

    /** @test */
    public function generate_phases_returns_empty_array_for_regular_service()
    {
        $service = $this->createService([
            'has_processing_time' => false,
        ]);

        $phases = $service->generatePhases(now());

        $this->assertEmpty($phases);
    }

    /** @test */
    public function generate_phases_creates_correct_phase_structure()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $startTime = Carbon::parse('2025-10-28 10:00:00');
        $phases = $service->generatePhases($startTime);

        $this->assertCount(3, $phases);

        // Initial Phase
        $this->assertEquals('initial', $phases[0]['phase_type']);
        $this->assertEquals(0, $phases[0]['start_offset_minutes']);
        $this->assertEquals(15, $phases[0]['duration_minutes']);
        $this->assertTrue($phases[0]['staff_required']);
        $this->assertEquals('2025-10-28 10:00:00', $phases[0]['start_time']->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-28 10:15:00', $phases[0]['end_time']->format('Y-m-d H:i:s'));

        // Processing Phase
        $this->assertEquals('processing', $phases[1]['phase_type']);
        $this->assertEquals(15, $phases[1]['start_offset_minutes']);
        $this->assertEquals(25, $phases[1]['duration_minutes']);
        $this->assertFalse($phases[1]['staff_required']);
        $this->assertEquals('2025-10-28 10:15:00', $phases[1]['start_time']->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-28 10:40:00', $phases[1]['end_time']->format('Y-m-d H:i:s'));

        // Final Phase
        $this->assertEquals('final', $phases[2]['phase_type']);
        $this->assertEquals(40, $phases[2]['start_offset_minutes']);
        $this->assertEquals(20, $phases[2]['duration_minutes']);
        $this->assertTrue($phases[2]['staff_required']);
        $this->assertEquals('2025-10-28 10:40:00', $phases[2]['start_time']->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-28 11:00:00', $phases[2]['end_time']->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function generate_phases_handles_missing_initial_phase()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 0,
            'processing_duration' => 30,
            'final_duration' => 20,
        ]);

        $phases = $service->generatePhases(now());

        $this->assertCount(2, $phases);
        $this->assertEquals('processing', $phases[0]['phase_type']);
        $this->assertEquals('final', $phases[1]['phase_type']);
    }

    /** @test */
    public function generate_phases_handles_missing_final_phase()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 20,
            'processing_duration' => 30,
            'final_duration' => 0,
        ]);

        $phases = $service->generatePhases(now());

        $this->assertCount(2, $phases);
        $this->assertEquals('initial', $phases[0]['phase_type']);
        $this->assertEquals('processing', $phases[1]['phase_type']);
    }

    /** @test */
    public function validate_processing_time_passes_for_regular_service()
    {
        $service = $this->createService([
            'has_processing_time' => false,
            'duration_minutes' => 60,
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function validate_processing_time_passes_for_valid_configuration()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'duration_minutes' => 60,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function validate_processing_time_fails_when_missing_active_phases()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'duration_minutes' => 30,
            'initial_duration' => 0,
            'processing_duration' => 30,
            'final_duration' => 0,
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertFalse($validation['valid']);
        $this->assertContains('Processing time service must have at least an initial or final phase.', $validation['errors']);
    }

    /** @test */
    public function validate_processing_time_fails_when_missing_processing_duration()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'duration_minutes' => 40,
            'initial_duration' => 20,
            'processing_duration' => 0,
            'final_duration' => 20,
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertFalse($validation['valid']);
        $this->assertContains('Processing time service must have a processing duration (gap time).', $validation['errors']);
    }

    /** @test */
    public function validate_processing_time_fails_when_total_mismatch()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'duration_minutes' => 100, // Mismatch!
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20, // Total = 60, not 100
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('Total phase duration', $validation['errors'][0]);
    }

    /** @test */
    public function validate_processing_time_fails_for_negative_values()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'duration_minutes' => 60,
            'initial_duration' => -5, // Negative!
            'processing_duration' => 40,
            'final_duration' => 25,
        ]);

        $validation = $service->validateProcessingTime();

        $this->assertFalse($validation['valid']);
        $this->assertContains('Phase durations cannot be negative.', $validation['errors']);
    }

    /** @test */
    public function get_processing_time_description_returns_null_for_regular_service()
    {
        $service = $this->createService([
            'has_processing_time' => false,
        ]);

        $this->assertNull($service->getProcessingTimeDescription());
    }

    /** @test */
    public function get_processing_time_description_returns_formatted_string()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 25,
            'final_duration' => 20,
        ]);

        $description = $service->getProcessingTimeDescription();

        $this->assertStringContainsString('Initial phase: 15 min (staff busy)', $description);
        $this->assertStringContainsString('Processing: 25 min (staff available)', $description);
        $this->assertStringContainsString('Final phase: 20 min (staff busy)', $description);
        $this->assertStringContainsString('Total: 60 min', $description);
    }

    /** @test */
    public function get_processing_time_description_handles_missing_phases()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 20,
            'processing_duration' => 30,
            'final_duration' => 0,
        ]);

        $description = $service->getProcessingTimeDescription();

        $this->assertStringContainsString('Initial phase: 20 min', $description);
        $this->assertStringContainsString('Processing: 30 min', $description);
        $this->assertStringNotContainsString('Final phase', $description);
        $this->assertStringContainsString('Total: 50 min', $description);
    }
}
