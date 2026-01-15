<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Monitoring\DataConsistencyMonitor;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DataConsistencyMonitor Test Suite
 *
 * Comprehensive tests for real-time data consistency detection and alerting.
 *
 * Coverage:
 * - Detect session_outcome vs appointment_made mismatch
 * - Detect appointment_made=1 but no appointment in DB (phantom bookings)
 * - Detect calls without direction field
 * - Detect orphaned appointments (no call link)
 * - Detect recent creation failures
 * - Alert throttling (prevent spam)
 * - Daily validation report generation
 * - Single call consistency checks
 *
 * @group slow
 * @group requires-database
 *
 * NOTE: These tests use RefreshDatabase and may be slow in CI.
 * Run explicitly with: vendor/bin/pest --group=slow
 * Excluded from default CI run (2026-01-15).
 */
class DataConsistencyMonitorTest extends TestCase
{
    use RefreshDatabase;

    private DataConsistencyMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = new DataConsistencyMonitor();

        // Clear cache before each test
        Cache::flush();
    }

    // ============================================================
    // DETECTION RULES - SESSION OUTCOME MISMATCH
    // ============================================================

    /**
     * @test
     * Rule 1: Detect session_outcome mismatch
     */
    public function it_detects_session_outcome_mismatch()
    {
        // Arrange: Call with mismatched flags
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'retell_call_id' => 'call_mismatch',
            'session_outcome' => 'appointment_booked', // Says booked
            'appointment_made' => false,               // But flag is false
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(30), // Recent
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Mismatch detected
        $this->assertArrayHasKey('session_outcome_mismatch', $summary['inconsistencies']);
        $this->assertCount(1, $summary['inconsistencies']['session_outcome_mismatch']);
        $this->assertEquals(1, $summary['totals']['critical']);

        $mismatch = $summary['inconsistencies']['session_outcome_mismatch'][0];
        $this->assertEquals('call_mismatch', $mismatch['retell_call_id']);
        $this->assertEquals('appointment_booked', $mismatch['session_outcome']);
        $this->assertFalse($mismatch['appointment_made']);
        $this->assertEquals('critical', $mismatch['severity']);
    }

    /**
     * @test
     * Rule 1: No detection for consistent calls
     */
    public function it_does_not_detect_mismatch_when_flags_consistent()
    {
        // Arrange: Call with consistent flags
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'session_outcome' => 'appointment_booked',
            'appointment_made' => true, // Consistent
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: No mismatches
        $this->assertArrayNotHasKey('session_outcome_mismatch', $summary['inconsistencies']);
        $this->assertEquals(0, $summary['totals']['critical']);
    }

    /**
     * @test
     * Rule 1: Only detects recent calls (within 1 hour)
     */
    public function it_only_detects_recent_session_outcome_mismatches()
    {
        // Arrange: Old and recent mismatches
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // Old mismatch (should not be detected)
        Call::factory()->create([
            'session_outcome' => 'appointment_booked',
            'appointment_made' => false,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subHours(2), // Old
        ]);

        // Recent mismatch (should be detected)
        Call::factory()->create([
            'retell_call_id' => 'recent_mismatch',
            'session_outcome' => 'appointment_booked',
            'appointment_made' => false,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(30), // Recent
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Only recent mismatch detected
        $this->assertCount(1, $summary['inconsistencies']['session_outcome_mismatch']);
        $this->assertEquals('recent_mismatch', $summary['inconsistencies']['session_outcome_mismatch'][0]['retell_call_id']);
    }

    // ============================================================
    // DETECTION RULES - MISSING APPOINTMENTS (PHANTOM BOOKINGS)
    // ============================================================

    /**
     * @test
     * Rule 2: Detect phantom bookings (appointment_made=1 but no DB record)
     */
    public function it_detects_phantom_bookings()
    {
        // Arrange: Call claiming appointment made, but no appointment exists
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'retell_call_id' => 'phantom_booking',
            'appointment_made' => true, // Claims appointment made
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(15),
        ]);
        // No appointment created

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Phantom booking detected
        $this->assertArrayHasKey('missing_appointments', $summary['inconsistencies']);
        $this->assertCount(1, $summary['inconsistencies']['missing_appointments']);
        $this->assertEquals(1, $summary['totals']['critical']);

        $phantom = $summary['inconsistencies']['missing_appointments'][0];
        $this->assertEquals('phantom_booking', $phantom['retell_call_id']);
        $this->assertTrue($phantom['appointment_made']);
        $this->assertFalse($phantom['appointment_exists']);
        $this->assertEquals('critical', $phantom['severity']);
    }

    /**
     * @test
     * Rule 2: No detection when appointment exists
     */
    public function it_does_not_detect_phantom_when_appointment_exists()
    {
        // Arrange: Call with actual appointment
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $call = Call::factory()->create([
            'appointment_made' => true,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        Appointment::factory()->create([
            'call_id' => $call->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: No phantom bookings
        $this->assertArrayNotHasKey('missing_appointments', $summary['inconsistencies']);
    }

    /**
     * @test
     * Rule 2: Multiple phantom bookings detected
     */
    public function it_detects_multiple_phantom_bookings()
    {
        // Arrange: Multiple calls without appointments
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        for ($i = 1; $i <= 3; $i++) {
            Call::factory()->create([
                'retell_call_id' => "phantom_{$i}",
                'appointment_made' => true,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'created_at' => now()->subMinutes(10),
            ]);
        }

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: All 3 detected
        $this->assertCount(3, $summary['inconsistencies']['missing_appointments']);
        $this->assertEquals(3, $summary['totals']['critical']);
    }

    // ============================================================
    // DETECTION RULES - MISSING DIRECTIONS
    // ============================================================

    /**
     * @test
     * Rule 3: Detect calls without direction field
     */
    public function it_detects_calls_without_direction()
    {
        // Arrange: Call without direction
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'retell_call_id' => 'no_direction',
            'direction' => null,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(20),
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Missing direction detected
        $this->assertArrayHasKey('missing_directions', $summary['inconsistencies']);
        $this->assertCount(1, $summary['inconsistencies']['missing_directions']);
        $this->assertEquals(1, $summary['totals']['warning']);

        $missing = $summary['inconsistencies']['missing_directions'][0];
        $this->assertEquals('no_direction', $missing['retell_call_id']);
        $this->assertNull($missing['direction']);
        $this->assertEquals('warning', $missing['severity']);
        $this->assertTrue($missing['auto_fixable']);
    }

    /**
     * @test
     * Rule 3: No detection when direction present
     */
    public function it_does_not_detect_when_direction_present()
    {
        // Arrange: Call with direction
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'direction' => 'inbound',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: No missing directions
        $this->assertArrayNotHasKey('missing_directions', $summary['inconsistencies']);
    }

    // ============================================================
    // DETECTION RULES - ORPHANED APPOINTMENTS
    // ============================================================

    /**
     * @test
     * Rule 4: Detect orphaned appointments (no call link)
     */
    public function it_detects_orphaned_appointments()
    {
        // Arrange: Appointment without call_id from retell_webhook
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Appointment::factory()->create([
            'calcom_v2_booking_id' => 'orphan_booking',
            'call_id' => null, // Orphaned
            'source' => 'retell_webhook',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(30),
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Orphaned appointment detected
        $this->assertArrayHasKey('orphaned_appointments', $summary['inconsistencies']);
        $this->assertCount(1, $summary['inconsistencies']['orphaned_appointments']);
        $this->assertEquals(1, $summary['totals']['warning']);

        $orphan = $summary['inconsistencies']['orphaned_appointments'][0];
        $this->assertEquals('orphan_booking', $orphan['calcom_booking_id']);
        $this->assertNull($orphan['call_id']);
        $this->assertEquals('retell_webhook', $orphan['source']);
        $this->assertEquals('warning', $orphan['severity']);
        $this->assertTrue($orphan['auto_fixable']);
    }

    /**
     * @test
     * Rule 4: Only detect retell_webhook source appointments
     */
    public function it_only_detects_orphaned_retell_webhook_appointments()
    {
        // Arrange: Two orphaned appointments, different sources
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // Retell webhook (should be detected)
        Appointment::factory()->create([
            'call_id' => null,
            'source' => 'retell_webhook',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Manual booking (should not be detected)
        Appointment::factory()->create([
            'call_id' => null,
            'source' => 'manual',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Only retell_webhook orphan detected
        $this->assertCount(1, $summary['inconsistencies']['orphaned_appointments']);
    }

    // ============================================================
    // DETECTION RULES - RECENT FAILURES
    // ============================================================

    /**
     * @test
     * Rule 5: Detect recent creation failures
     */
    public function it_detects_recent_creation_failures()
    {
        // Arrange: Call with booking failure
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'retell_call_id' => 'failed_booking',
            'booking_failed' => true,
            'booking_failure_reason' => 'Cal.com API timeout',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(15),
        ]);

        // Act: Run detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: Failure detected
        $this->assertArrayHasKey('recent_failures', $summary['inconsistencies']);
        $this->assertCount(1, $summary['inconsistencies']['recent_failures']);
        $this->assertEquals(1, $summary['totals']['info']);

        $failure = $summary['inconsistencies']['recent_failures'][0];
        $this->assertEquals('failed_booking', $failure['retell_call_id']);
        $this->assertEquals('Cal.com API timeout', $failure['reason']);
        $this->assertEquals('info', $failure['severity']);
    }

    // ============================================================
    // SINGLE CALL CHECKS
    // ============================================================

    /**
     * @test
     * Check single call for all inconsistencies
     */
    public function it_checks_single_call_for_all_inconsistencies()
    {
        // Arrange: Call with multiple issues
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $call = Call::factory()->create([
            'session_outcome' => 'appointment_booked',
            'appointment_made' => false,           // Mismatch
            'direction' => null,                   // Missing direction
            'appointment_link_status' => 'pending', // Inconsistent
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Check single call
        $inconsistencies = $this->monitor->checkCall($call);

        // Assert: Multiple inconsistencies found
        $this->assertGreaterThanOrEqual(2, count($inconsistencies));

        $types = array_column($inconsistencies, 'type');
        $this->assertContains('session_outcome_mismatch', $types);
        $this->assertContains('missing_direction', $types);
    }

    /**
     * @test
     * Check single call with phantom booking
     */
    public function it_detects_phantom_booking_in_single_call_check()
    {
        // Arrange: Call claiming appointment but none exists
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $call = Call::factory()->create([
            'appointment_made' => true,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Check call
        $inconsistencies = $this->monitor->checkCall($call);

        // Assert: Phantom booking detected
        $phantomIssue = collect($inconsistencies)->firstWhere('type', 'missing_appointment');
        $this->assertNotNull($phantomIssue);
        $this->assertEquals('critical', $phantomIssue['severity']);
        $this->assertTrue($phantomIssue['appointment_made']);
    }

    /**
     * @test
     * Check single call returns empty for consistent call
     */
    public function it_returns_empty_for_consistent_call()
    {
        // Arrange: Completely consistent call
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $call = Call::factory()->create([
            'appointment_made' => true,
            'session_outcome' => 'appointment_booked',
            'appointment_link_status' => 'linked',
            'direction' => 'inbound',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        Appointment::factory()->create([
            'call_id' => $call->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Act: Check call
        $inconsistencies = $this->monitor->checkCall($call);

        // Assert: No inconsistencies
        $this->assertEmpty($inconsistencies);
    }

    // ============================================================
    // ALERT THROTTLING
    // ============================================================

    /**
     * @test
     * Alerts are throttled to prevent spam
     */
    public function it_throttles_duplicate_alerts()
    {
        // Arrange: Clear alert table
        DB::table('data_consistency_alerts')->truncate();

        // Act: Send same alert twice
        $this->monitor->alertInconsistency('session_outcome_mismatch', ['call_id' => 123]);
        $this->monitor->alertInconsistency('session_outcome_mismatch', ['call_id' => 123]);

        // Assert: Only one alert created (second was throttled)
        $alertCount = DB::table('data_consistency_alerts')
            ->where('alert_type', 'session_outcome_mismatch')
            ->where('entity_id', 123)
            ->count();

        $this->assertEquals(1, $alertCount, 'Second alert should be throttled');
    }

    /**
     * @test
     * Throttling expires after 5 minutes
     */
    public function it_allows_alert_after_throttle_expires()
    {
        // Arrange: Send first alert
        $this->monitor->alertInconsistency('missing_appointment', ['call_id' => 456]);

        // Simulate cache expiration (travel forward in time)
        Carbon::setTestNow(now()->addMinutes(6));

        // Act: Send same alert after throttle period
        $this->monitor->alertInconsistency('missing_appointment', ['call_id' => 456]);

        // Assert: Both alerts created
        $alertCount = DB::table('data_consistency_alerts')
            ->where('alert_type', 'missing_appointment')
            ->where('entity_id', 456)
            ->count();

        $this->assertEquals(2, $alertCount, 'Alert should be allowed after throttle expires');

        // Cleanup
        Carbon::setTestNow();
    }

    /**
     * @test
     * Different alert types not throttled together
     */
    public function it_does_not_throttle_different_alert_types()
    {
        // Act: Send different alert types for same entity
        $this->monitor->alertInconsistency('session_outcome_mismatch', ['call_id' => 789]);
        $this->monitor->alertInconsistency('missing_appointment', ['call_id' => 789]);
        $this->monitor->alertInconsistency('missing_direction', ['call_id' => 789]);

        // Assert: All alerts created
        $totalAlerts = DB::table('data_consistency_alerts')
            ->where('entity_id', 789)
            ->count();

        $this->assertEquals(3, $totalAlerts, 'Different alert types should not be throttled together');
    }

    // ============================================================
    // DAILY VALIDATION REPORT
    // ============================================================

    /**
     * @test
     * Generate daily validation report
     */
    public function it_generates_daily_validation_report()
    {
        // Arrange: Create test data
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // 10 total calls
        Call::factory()->count(8)->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // 2 inconsistent calls
        Call::factory()->count(2)->create([
            'appointment_made' => true,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // 8 valid appointments
        $validCalls = Call::where('appointment_made', false)->take(8)->get();
        foreach ($validCalls as $call) {
            Appointment::factory()->create([
                'call_id' => $call->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]);
        }

        // Create some alerts
        DB::table('data_consistency_alerts')->insert([
            [
                'alert_type' => 'missing_appointment',
                'entity_type' => 'call',
                'entity_id' => 1,
                'severity' => 'critical',
                'description' => 'Test alert 1',
                'metadata' => json_encode([]),
                'detected_at' => now(),
                'auto_corrected' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'alert_type' => 'missing_appointment',
                'entity_type' => 'call',
                'entity_id' => 2,
                'severity' => 'critical',
                'description' => 'Test alert 2',
                'metadata' => json_encode([]),
                'detected_at' => now(),
                'auto_corrected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act: Generate report
        $report = $this->monitor->generateDailyReport();

        // Assert: Report structure
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('inconsistencies_by_type', $report);
        $this->assertArrayHasKey('resolution', $report);
        $this->assertArrayHasKey('top_issues', $report);

        // Assert: Summary data
        $this->assertEquals(10, $report['summary']['total_calls']);
        $this->assertEquals(8, $report['summary']['total_appointments']);
        $this->assertGreaterThan(0, $report['summary']['consistency_rate_pct']);

        // Assert: Resolution breakdown
        $this->assertEquals(1, $report['resolution']['auto_corrected']);
        $this->assertEquals(1, $report['resolution']['manual_review']);
    }

    /**
     * @test
     * Report calculates consistency rate correctly
     */
    public function it_calculates_consistency_rate_correctly()
    {
        // Arrange: 100 calls, 5 inconsistencies
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->count(100)->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Create 5 inconsistency alerts
        for ($i = 0; $i < 5; $i++) {
            DB::table('data_consistency_alerts')->insert([
                'alert_type' => 'test_inconsistency',
                'entity_type' => 'call',
                'entity_id' => $i,
                'severity' => 'critical',
                'description' => "Test inconsistency {$i}",
                'metadata' => json_encode([]),
                'detected_at' => now(),
                'auto_corrected' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Act: Generate report
        $report = $this->monitor->generateDailyReport();

        // Assert: Consistency rate = (100 - 5) / 100 = 95%
        $this->assertEquals(95.0, $report['summary']['consistency_rate_pct']);
    }

    /**
     * @test
     * Report handles empty data gracefully
     */
    public function it_handles_empty_data_in_report()
    {
        // Act: Generate report with no data
        $report = $this->monitor->generateDailyReport();

        // Assert: Report structure intact
        $this->assertEquals(0, $report['summary']['total_calls']);
        $this->assertEquals(0, $report['summary']['total_appointments']);
        $this->assertEquals(0, $report['summary']['total_inconsistencies']);
        $this->assertEquals(100.0, $report['summary']['consistency_rate_pct']);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * @test
     * Complete detection flow with all rules
     */
    public function it_detects_all_inconsistency_types_in_one_scan()
    {
        // Arrange: Create various inconsistencies
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // Rule 1: Session outcome mismatch
        Call::factory()->create([
            'session_outcome' => 'appointment_booked',
            'appointment_made' => false,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Rule 2: Phantom booking
        Call::factory()->create([
            'appointment_made' => true,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(20),
        ]);

        // Rule 3: Missing direction
        Call::factory()->create([
            'direction' => null,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(15),
        ]);

        // Rule 4: Orphaned appointment
        Appointment::factory()->create([
            'call_id' => null,
            'source' => 'retell_webhook',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(25),
        ]);

        // Rule 5: Recent failure
        Call::factory()->create([
            'booking_failed' => true,
            'booking_failure_reason' => 'API error',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(5),
        ]);

        // Act: Run complete detection
        $summary = $this->monitor->detectInconsistencies();

        // Assert: All rules triggered
        $this->assertArrayHasKey('session_outcome_mismatch', $summary['inconsistencies']);
        $this->assertArrayHasKey('missing_appointments', $summary['inconsistencies']);
        $this->assertArrayHasKey('missing_directions', $summary['inconsistencies']);
        $this->assertArrayHasKey('orphaned_appointments', $summary['inconsistencies']);
        $this->assertArrayHasKey('recent_failures', $summary['inconsistencies']);

        // Assert: Severity counts
        $this->assertEquals(2, $summary['totals']['critical']);
        $this->assertEquals(2, $summary['totals']['warning']);
        $this->assertEquals(1, $summary['totals']['info']);
    }

    /**
     * @test
     * Detection creates database alerts
     */
    public function it_creates_database_alerts_during_detection()
    {
        // Arrange: Inconsistent call
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        Call::factory()->create([
            'retell_call_id' => 'alert_test',
            'session_outcome' => 'appointment_booked',
            'appointment_made' => false,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Act: Run detection
        $this->monitor->detectInconsistencies();

        // Assert: Alert created in database
        $this->assertDatabaseHas('data_consistency_alerts', [
            'alert_type' => 'session_outcome_mismatch',
            'severity' => 'critical',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
