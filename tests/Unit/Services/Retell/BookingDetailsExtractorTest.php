<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Services\Retell\BookingDetailsExtractor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingDetailsExtractorTest extends TestCase
{
    use RefreshDatabase;

    private BookingDetailsExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new BookingDetailsExtractor();

        // Set a fixed "now" for testing
        Carbon::setTestNow(Carbon::create(2025, 9, 30, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    // ==========================================
    // Main Extraction Tests (5 tests)
    // ==========================================

    /** @test */
    public function it_extracts_from_retell_data_when_available()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $call = Call::create([
            'retell_call_id' => 'test_call_1',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'completed',
            'analysis' => [
                'custom_analysis_data' => [
                    'appointment_date_time' => '2025-10-01 14:00',
                    'reason_for_visit' => 'Haircut',
                    'patient_full_name' => 'John Doe'
                ]
            ],
            'transcript' => 'some transcript that should be ignored'
        ]);

        $result = $this->extractor->extract($call);

        $this->assertNotNull($result);
        $this->assertEquals('2025-10-01 14:00:00', $result['starts_at']);
        $this->assertEquals(100, $result['confidence']); // Retell data = high confidence
        $this->assertEquals('Haircut', $result['service']);
    }

    /** @test */
    public function it_falls_back_to_transcript_when_no_retell_data()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $call = Call::create([
            'retell_call_id' => 'test_call_2',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'transcript' => 'Ich möchte einen Termin morgen um vierzehn uhr für einen Haarschnitt'
        ]);

        $result = $this->extractor->extract($call);

        $this->assertNotNull($result);
        $this->assertStringContainsString('14:00:00', $result['starts_at']);
        $this->assertEquals('Haircut', $result['service']);
    }

    /** @test */
    public function it_returns_null_when_no_extraction_possible()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $call = Call::create([
            'retell_call_id' => 'test_call_3',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'transcript' => 'Hello, this is just a general call with no appointment information'
        ]);

        $result = $this->extractor->extract($call);

        $this->assertNull($result);
    }

    /** @test */
    public function it_extracts_from_retell_data_successfully()
    {
        $customData = [
            'appointment_date_time' => '2025-10-05 16:00',
            'reason_for_visit' => 'Consultation',
            'patient_full_name' => 'Jane Smith',
            'appointment_made' => true
        ];

        $result = $this->extractor->extractFromRetellData($customData);

        $this->assertNotNull($result);
        $this->assertEquals('2025-10-05 16:00:00', $result['starts_at']);
        $this->assertEquals('2025-10-05 16:45:00', $result['ends_at']);
        $this->assertEquals('Consultation', $result['service']);
        $this->assertEquals(100, $result['confidence']);
        $this->assertEquals('Jane Smith', $result['patient_name']);
    }

    /** @test */
    public function it_returns_null_when_no_appointment_date_time_in_retell_data()
    {
        $customData = [
            'reason_for_visit' => 'Checkup',
            'patient_full_name' => 'Test Patient'
        ];

        $result = $this->extractor->extractFromRetellData($customData);

        $this->assertNull($result);
    }

    // ==========================================
    // German Date Parsing Tests (6 tests)
    // ==========================================

    /** @test */
    public function it_parses_german_ordinal_date_with_two_ordinals()
    {
        $result = $this->extractor->parseGermanOrdinalDate('erster zehnter');

        $this->assertNotNull($result);
        $this->assertEquals(1, $result->day);
        $this->assertEquals(10, $result->month);
    }

    /** @test */
    public function it_parses_german_ordinal_with_month_name()
    {
        $result = $this->extractor->parseGermanOrdinalDate('fünften oktober');

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->day);
        $this->assertEquals(10, $result->month);
    }

    /** @test */
    public function it_parses_numeric_day_with_german_month()
    {
        $result = $this->extractor->parseGermanOrdinalDate('15. november');

        $this->assertNotNull($result);
        $this->assertEquals(15, $result->day);
        $this->assertEquals(11, $result->month);
    }

    /** @test */
    public function it_adjusts_past_dates_to_next_year()
    {
        // Current test time is 2025-09-30
        $result = $this->extractor->parseGermanOrdinalDate('ersten september');

        $this->assertNotNull($result);
        $this->assertEquals(2026, $result->year); // Should be next year since Sept 1 has passed
    }

    /** @test */
    public function it_keeps_future_dates_in_current_year()
    {
        // Current test time is 2025-09-30
        $result = $this->extractor->parseGermanOrdinalDate('ersten oktober');

        $this->assertNotNull($result);
        $this->assertEquals(2025, $result->year); // October 1st is still in future
    }

    /** @test */
    public function it_parses_full_date_format()
    {
        $result = $this->extractor->parseDateTime('27. September 2025');

        $this->assertNotNull($result);
        $this->assertEquals(27, $result->day);
        $this->assertEquals(9, $result->month);
        $this->assertEquals(2025, $result->year);
    }

    // ==========================================
    // German Time Parsing Tests (6 tests)
    // ==========================================

    /** @test */
    public function it_parses_german_time_words_with_hour_and_minutes()
    {
        $transcript = 'Ich möchte einen Termin um vierzehn uhr dreißig';
        $date = Carbon::create(2025, 10, 1, 10, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        $this->assertEquals(14, $result->hour);
        $this->assertEquals(30, $result->minute);
    }

    /** @test */
    public function it_parses_german_hour_word_only()
    {
        $transcript = 'Termin am sechzehn uhr bitte';
        $date = Carbon::create(2025, 10, 1, 10, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        $this->assertEquals(16, $result->hour);
        $this->assertEquals(0, $result->minute);
    }

    /** @test */
    public function it_parses_numeric_time_in_appointment_context()
    {
        $transcript = 'Ich möchte einen Termin buchen um 15:30';
        $date = Carbon::create(2025, 10, 1, 10, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        $this->assertEquals(15, $result->hour);
        $this->assertEquals(30, $result->minute);
    }

    /** @test */
    public function it_parses_hour_only_numeric_in_context()
    {
        $transcript = 'Termin vereinbaren um 17 uhr';
        $date = Carbon::create(2025, 10, 1, 10, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        $this->assertEquals(17, $result->hour);
        $this->assertEquals(0, $result->minute);
    }

    /** @test */
    public function it_defaults_to_10am_when_no_time_found()
    {
        $transcript = 'Ich brauche einen Termin bitte';
        $date = Carbon::create(2025, 10, 1, 0, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        $this->assertEquals(10, $result->hour);
        $this->assertEquals(0, $result->minute);
    }

    /** @test */
    public function it_rejects_times_outside_business_hours()
    {
        $transcript = 'Termin um 6 uhr'; // 6 AM is before business hours
        $date = Carbon::create(2025, 10, 1, 10, 0, 0);

        $result = $this->extractor->parseGermanTime($transcript, $date);

        // Should default to 10:00 instead of using 6:00
        $this->assertEquals(10, $result->hour);
    }

    // ==========================================
    // Service Extraction Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_extracts_service_name_from_transcript()
    {
        $transcript = 'ich brauche einen haarschnitt termin';
        $result = $this->extractor->extractServiceName($transcript);

        $this->assertEquals('Haircut', $result);
    }

    /** @test */
    public function it_recognizes_multiple_german_services()
    {
        $this->assertEquals('Coloring', $this->extractor->extractServiceName('ich möchte färben'));
        $this->assertEquals('Tinting', $this->extractor->extractServiceName('eine tönung bitte'));
        $this->assertEquals('Styling', $this->extractor->extractServiceName('styling termin'));
        $this->assertEquals('Consultation', $this->extractor->extractServiceName('beratung gewünscht'));
    }

    /** @test */
    public function it_returns_null_when_no_service_found()
    {
        $transcript = 'hello this is just a general call';
        $result = $this->extractor->extractServiceName($transcript);

        $this->assertNull($result);
    }

    // ==========================================
    // Confidence Calculation Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_gives_100_confidence_for_retell_source()
    {
        $bookingDetails = ['some' => 'data'];
        $confidence = $this->extractor->calculateConfidence($bookingDetails, 'retell');

        $this->assertEquals(100, $confidence);
    }

    /** @test */
    public function it_calculates_confidence_based_on_pattern_count()
    {
        $bookingDetails = [
            'weekday' => 'montag',
            'time' => '14 uhr',
            'service' => 'Haircut'
        ];
        $confidence = $this->extractor->calculateConfidence($bookingDetails, 'transcript');

        // Base 50 + (3 * 10) + 10 (weekday) + 15 (time) + 10 (service) = 115, capped at 100
        $this->assertEquals(100, $confidence);
    }

    /** @test */
    public function it_returns_base_confidence_for_empty_details()
    {
        $confidence = $this->extractor->calculateConfidence([], 'transcript');

        $this->assertEquals(50, $confidence);
    }

    // ==========================================
    // Validation Tests (4 tests)
    // ==========================================

    /** @test */
    public function it_validates_correct_booking_details()
    {
        $bookingDetails = [
            'starts_at' => '2025-10-01 14:00:00',
            'ends_at' => '2025-10-01 14:45:00',
            'confidence' => 85
        ];

        $result = $this->extractor->validateBookingDetails($bookingDetails);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_booking_outside_business_hours()
    {
        $bookingDetails = [
            'starts_at' => '2025-10-01 06:00:00', // Too early
            'ends_at' => '2025-10-01 06:45:00',
            'confidence' => 85
        ];

        $result = $this->extractor->validateBookingDetails($bookingDetails);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_booking_with_end_before_start()
    {
        $bookingDetails = [
            'starts_at' => '2025-10-01 15:00:00',
            'ends_at' => '2025-10-01 14:00:00', // Before start
            'confidence' => 85
        ];

        $result = $this->extractor->validateBookingDetails($bookingDetails);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_booking_with_low_confidence()
    {
        $bookingDetails = [
            'starts_at' => '2025-10-01 14:00:00',
            'ends_at' => '2025-10-01 14:45:00',
            'confidence' => 30 // Too low
        ];

        $result = $this->extractor->validateBookingDetails($bookingDetails);

        $this->assertFalse($result);
    }

    // ==========================================
    // Helper Method Tests (4 tests)
    // ==========================================

    /** @test */
    public function it_fixes_past_dates_by_adding_year()
    {
        $pastDate = Carbon::create(2024, 6, 15, 14, 0, 0);
        $result = $this->extractor->fixPastDate($pastDate);

        $this->assertTrue($result->isFuture());
        $this->assertEquals(2025, $result->year);
    }

    /** @test */
    public function it_keeps_future_dates_unchanged()
    {
        $futureDate = Carbon::create(2025, 11, 15, 14, 0, 0);
        $result = $this->extractor->fixPastDate($futureDate);

        $this->assertEquals(2025, $result->year);
        $this->assertEquals(11, $result->month);
    }

    /** @test */
    public function it_extracts_date_patterns_from_transcript()
    {
        $transcript = 'ich möchte einen termin am montag um vierzehn uhr für einen haarschnitt';
        $patterns = $this->extractor->extractDatePatterns($transcript);

        $this->assertArrayHasKey('weekday', $patterns);
        $this->assertArrayHasKey('time_fourteen', $patterns);
        $this->assertEquals('montag', $patterns['weekday']);
    }

    /** @test */
    public function it_formats_booking_details_correctly()
    {
        $startTime = Carbon::create(2025, 10, 1, 14, 30, 0);
        $result = $this->extractor->formatBookingDetails(
            $startTime,
            60,
            'Haircut',
            ['test' => 'data'],
            85
        );

        $this->assertEquals('2025-10-01 14:30:00', $result['starts_at']);
        $this->assertEquals('2025-10-01 15:30:00', $result['ends_at']);
        $this->assertEquals(60, $result['duration_minutes']);
        $this->assertEquals('Haircut', $result['service']);
        $this->assertEquals(85, $result['confidence']);
    }

    // ==========================================
    // Complex Integration Tests (3 tests)
    // ==========================================

    /** @test */
    public function it_extracts_complete_appointment_from_german_transcript()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $call = Call::create([
            'retell_call_id' => 'test_call_complex',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'transcript' => 'Guten Tag, ich möchte einen Termin am ersten Oktober um vierzehn uhr dreißig für einen Haarschnitt buchen'
        ]);

        $result = $this->extractor->extractFromTranscript($call);

        $this->assertNotNull($result);
        $this->assertStringContainsString('2025-10-01', $result['starts_at']);
        $this->assertStringContainsString('14:30', $result['starts_at']);
        $this->assertEquals('Haircut', $result['service']);
        $this->assertGreaterThan(70, $result['confidence']);
    }

    /** @test */
    public function it_handles_relative_day_mentions()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $call = Call::create([
            'retell_call_id' => 'test_call_relative',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'transcript' => 'Ich brauche einen Termin morgen um sechzehn uhr'
        ]);

        $result = $this->extractor->extractFromTranscript($call);

        $this->assertNotNull($result);
        $expectedDate = Carbon::tomorrow()->format('Y-m-d');
        $this->assertStringContainsString($expectedDate, $result['starts_at']);
        $this->assertStringContainsString('16:00', $result['starts_at']);
    }

    /** @test */
    public function it_handles_weekday_mentions()
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $call = Call::create([
            'retell_call_id' => 'test_call_weekday',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'status' => 'completed',
            'transcript' => 'Termin am Montag um siebzehn uhr bitte'
        ]);

        $result = $this->extractor->extractFromTranscript($call);

        $this->assertNotNull($result);
        $this->assertStringContainsString('17:00', $result['starts_at']);
        // Check that it's a Monday
        $startTime = Carbon::parse($result['starts_at']);
        $this->assertEquals(Carbon::MONDAY, $startTime->dayOfWeek);
    }
}