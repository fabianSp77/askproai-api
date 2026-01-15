<?php

namespace Tests\Unit\Services\Booking;

use App\Models\Service;
use App\Services\Booking\BookingNoticeValidator;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * BookingNoticeValidatorTest
 *
 * Unit tests for Bug #11 fix - Minimum booking notice validation
 *
 * TRUE UNIT TESTS: No database, using simple test object
 *
 * @package Tests\Unit\Services\Booking
 */
class BookingNoticeValidatorTest extends TestCase
{
    private BookingNoticeValidator $validator;
    private Service|MockInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new BookingNoticeValidator();

        // Create mock Service object (NO DATABASE)
        // Using Mockery to satisfy type hint while keeping test database-free
        $this->service = Mockery::mock(Service::class)->makePartial();
        $this->service->id = 42;
        $this->service->name = 'Test Service';
        $this->service->minimum_booking_notice = null;
        $this->service->calcom_slot_interval = 15;

        // Mock branches() relationship to return null (no branch override)
        $branchesQuery = Mockery::mock();
        $branchesQuery->shouldReceive('where')->andReturnSelf();
        $branchesQuery->shouldReceive('first')->andReturn(null);
        $this->service->shouldReceive('branches')->andReturn($branchesQuery);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * Test 1: Validates against global default when no service config
     */
    public function it_validates_against_global_default()
    {
        // Arrange: Set global config to 15 minutes
        config(['calcom.minimum_booking_notice_minutes' => 15]);

        // Act: Request appointment in 10 minutes (too soon)
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(10);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert: Should be rejected
        $this->assertFalse($result['valid']);
        $this->assertEquals('too_soon', $result['reason']);
        $this->assertEquals(15, $result['minimum_notice_minutes']);
    }

    /**
     * @test
     * Test 2: Validates against service-specific configuration
     */
    public function it_validates_against_service_specific_config()
    {
        // Arrange: Set service-specific notice to 60 minutes (mock object)
        $this->service->minimum_booking_notice = 60;

        // Act: Request appointment in 30 minutes (too soon for this service)
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(30);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert: Should be rejected (30 < 60)
        $this->assertFalse($result['valid']);
        $this->assertEquals('too_soon', $result['reason']);
        $this->assertEquals(60, $result['minimum_notice_minutes']);
    }

    /**
     * @test
     * Test 3: Rejects time that's too soon
     */
    public function it_rejects_booking_too_soon()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 15]);

        // Act: Request appointment in 5 minutes
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(5);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('too_soon', $result['reason']);
        $this->assertArrayHasKey('earliest_bookable', $result);
        $this->assertArrayHasKey('minutes_until_earliest', $result);
    }

    /**
     * @test
     * Test 4: Accepts time exactly at boundary (edge case)
     */
    public function it_accepts_time_at_boundary()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 15]);

        // Act: Request appointment EXACTLY 15 minutes from now
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(15);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert: Should be ACCEPTED (>= not just >)
        $this->assertTrue($result['valid']);
        $this->assertEquals(15, $result['minimum_notice_minutes']);
    }

    /**
     * @test
     * Test 5: Accepts time after boundary
     */
    public function it_accepts_time_after_boundary()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 15]);

        // Act: Request appointment 20 minutes from now
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(20);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert: Should be accepted
        $this->assertTrue($result['valid']);
    }

    /**
     * @test
     * Test 6: Suggests alternative times
     */
    public function it_suggests_alternative_times()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 15]);

        // Act: Request appointment in 5 minutes (too soon)
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(5);
        $alternatives = $this->validator->suggestAlternatives($requestedTime, $this->service);

        // Assert: Should return 3 alternatives
        $this->assertCount(3, $alternatives);
        $this->assertArrayHasKey('datetime', $alternatives[0]);
        $this->assertArrayHasKey('formatted_de', $alternatives[0]);

        // First alternative should be after minimum notice
        $firstAlt = $alternatives[0]['datetime'];
        $now = Carbon::now('Europe/Berlin');
        $this->assertGreaterThanOrEqual(15, $now->diffInMinutes($firstAlt));
    }

    /**
     * @test
     * Test 7: Returns earliest bookable time
     */
    public function it_returns_earliest_bookable_time()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 30]);

        // Act
        $earliest = $this->validator->getEarliestBookableTime($this->service);

        // Assert: Should be 30 minutes from now
        $now = Carbon::now('Europe/Berlin');
        $diffMinutes = $now->diffInMinutes($earliest);

        $this->assertGreaterThanOrEqual(29, $diffMinutes); // Allow 1 min tolerance for test execution time
        $this->assertLessThanOrEqual(31, $diffMinutes);
    }

    /**
     * @test
     * Test 8: Formats error message in German
     */
    public function it_formats_error_message_in_german()
    {
        // Arrange
        config(['calcom.minimum_booking_notice_minutes' => 15]);
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(5);

        // Act
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);
        $alternatives = $this->validator->suggestAlternatives($requestedTime, $this->service);
        $message = $this->validator->formatErrorMessage($result, $alternatives);

        // Assert: Message should be in German
        $this->assertStringContainsString('zu kurzfristig', $message);
        $this->assertStringContainsString('Minuten', $message);
        $this->assertStringContainsString('nächste verfügbare', $message);
    }

    /**
     * @test
     * Test 9: Handles timezone correctly (Europe/Berlin)
     */
    public function it_handles_timezone_correctly()
    {
        // Arrange: Set timezone explicitly
        config(['app.timezone' => 'UTC']); // App timezone different from validation timezone

        // Act: Request in 20 minutes
        $requestedTime = Carbon::now('Europe/Berlin')->addMinutes(20);
        $result = $this->validator->validateBookingNotice($requestedTime, $this->service);

        // Assert: Should use Berlin time for validation
        $this->assertTrue($result['valid']);

        // Earliest bookable should also be in Berlin timezone
        $this->assertEquals('Europe/Berlin', $result['earliest_bookable']->timezone->getName());
    }

    /**
     * @test
     * Test 10: Uses hardcoded fallback when no config
     */
    public function it_uses_hardcoded_fallback_when_no_config()
    {
        // Arrange: Clear all config (mock object)
        config(['calcom.minimum_booking_notice_minutes' => null]);
        $this->service->minimum_booking_notice = null;

        // Act
        $minimumNotice = $this->validator->getMinimumNoticeMinutes($this->service);

        // Assert: Should use hardcoded 15 minutes
        $this->assertEquals(15, $minimumNotice);
    }

    /**
     * @test
     * Test 11: Formats hours correctly in error message
     */
    public function it_formats_hours_correctly_in_error_message()
    {
        // Arrange: 2 hours = 120 minutes
        $validationResult = [
            'minimum_notice_minutes' => 120,
            'earliest_bookable' => Carbon::now()->addMinutes(120),
        ];

        // Act
        $message = $this->validator->formatErrorMessage($validationResult);

        // Assert
        $this->assertStringContainsString('2 Stunden', $message);
    }

    /**
     * @test
     * Test 12: Formats mixed hours and minutes correctly
     */
    public function it_formats_mixed_hours_and_minutes()
    {
        // Arrange: 1.5 hours = 90 minutes
        $validationResult = [
            'minimum_notice_minutes' => 90,
            'earliest_bookable' => Carbon::now()->addMinutes(90),
        ];

        // Act
        $message = $this->validator->formatErrorMessage($validationResult);

        // Assert
        $this->assertStringContainsString('1 Stunden und 30 Minuten', $message);
    }
}
