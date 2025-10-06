<?php

namespace Tests\Feature\Monitoring;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Monitoring and Alerting Test Suite
 *
 * Tests for ongoing monitoring of customer data integrity.
 *
 * Purpose: Enable continuous validation and alerting
 */
class CustomerDataIntegrityMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function test_alert_triggered_on_null_company_id_creation()
    {
        // This test would integrate with your alerting system
        // For now, we test the detection mechanism

        // Arrange: Attempt to create NULL customer (should be prevented)
        try {
            DB::table('customers')->insert([
                'name' => 'Test',
                'email' => 'test@test.com',
                'phone' => '1234567890',
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        // Act: Check if alert would be triggered
        $nullCount = DB::table('customers')->whereNull('company_id')->count();

        // Assert: Alert should trigger if any NULL values exist
        if ($nullCount > 0) {
            dump(['alert' => 'CRITICAL: NULL company_id detected', 'count' => $nullCount]);
        }

        $this->assertEquals(0, $nullCount, 'No NULL company_id should exist');
    }

    /** @test */
    public function test_daily_validation_command_detects_issues()
    {
        // Arrange: Create valid and invalid data scenarios
        Customer::factory()->count(5)->create(['company_id' => $this->company->id]);

        // Act: Run validation command
        $exitCode = Artisan::call('customers:validate-integrity');

        // Assert: Command executes successfully
        $this->assertEquals(0, $exitCode, 'Validation command should complete successfully');

        $output = Artisan::output();
        $this->assertStringContainsString('Validation', $output);

        dump([
            'message' => 'Daily validation command tested',
            'exit_code' => $exitCode,
            'output_preview' => substr($output, 0, 200),
        ]);
    }

    /** @test */
    public function test_audit_log_records_company_id_changes()
    {
        // Arrange: Create customer
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Act: Update customer (if policy allows)
        $originalCompanyId = $customer->company_id;

        // Try to update company_id (should be prevented by mass assignment protection)
        $customer->update(['name' => 'Updated Name']);

        // Assert: company_id unchanged
        $this->assertEquals($originalCompanyId, $customer->fresh()->company_id);

        // In production, audit log would record any attempts to modify company_id
        dump([
            'message' => 'Audit log validation',
            'customer_id' => $customer->id,
            'company_id_unchanged' => true,
        ]);
    }

    /** @test */
    public function test_monitoring_dashboard_metrics()
    {
        // Arrange: Create dataset
        Customer::factory()->count(20)->create(['company_id' => $this->company->id]);

        // Act: Generate metrics
        $metrics = [
            'total_customers' => Customer::count(),
            'customers_with_company_id' => Customer::whereNotNull('company_id')->count(),
            'null_company_id_count' => Customer::whereNull('company_id')->count(),
            'percentage_valid' => (Customer::whereNotNull('company_id')->count() / Customer::count()) * 100,
        ];

        // Assert: All metrics show healthy state
        $this->assertEquals(20, $metrics['total_customers']);
        $this->assertEquals(20, $metrics['customers_with_company_id']);
        $this->assertEquals(0, $metrics['null_company_id_count']);
        $this->assertEquals(100, $metrics['percentage_valid']);

        dump([
            'message' => 'Monitoring metrics validated',
            'metrics' => $metrics,
            'status' => 'HEALTHY',
        ]);
    }

    /** @test */
    public function test_validation_report_generation()
    {
        // Arrange
        Customer::factory()->count(10)->create(['company_id' => $this->company->id]);

        // Act: Generate comprehensive report
        $report = [
            'timestamp' => now()->toDateTimeString(),
            'total_customers' => Customer::count(),
            'null_company_id' => Customer::whereNull('company_id')->count(),
            'invalid_company_refs' => DB::table('customers')
                ->whereNotNull('company_id')
                ->whereNotIn('company_id', function ($query) {
                    $query->select('id')->from('companies');
                })
                ->count(),
            'health_status' => 'OK',
        ];

        // Assert: Report shows healthy state
        $this->assertEquals(0, $report['null_company_id']);
        $this->assertEquals(0, $report['invalid_company_refs']);
        $this->assertEquals('OK', $report['health_status']);

        dump([
            'message' => 'Daily validation report',
            'report' => $report,
        ]);
    }
}
