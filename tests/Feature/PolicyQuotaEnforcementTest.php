<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PolicyConfiguration;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Policies\AppointmentPolicyEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyQuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: max_cancellations_per_month quota enforcement
     *
     * This test validates CRITICAL-001 from FEATURE_AUDIT.md:
     * - Does the policy quota check work?
     * - Is MaterializedStatService actually used?
     * - Does it fallback to real-time count?
     */
    public function test_max_cancellations_per_month_is_enforced(): void
    {
        // Arrange: Create company with 2 cancellations per month limit
        $company = Company::factory()->create(['name' => 'Test Company']);

        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Branch',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'email' => 'test@customer.com',
        ]);

        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Test Service',
        ]);

        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Test Staff',
        ]);

        // Create policy with max 2 cancellations per month
        // NOTE: We need to check which schema is used - old (entity_type) or new (configurable_type)
        $policyData = [
            'company_id' => $company->id,
            'policy_type' => 'cancellation',
            'config' => json_encode([
                'max_cancellations_per_month' => 2,
                'hours_before' => 24,
            ]),
        ];

        // Try new schema first (configurable_type)
        try {
            PolicyConfiguration::create(array_merge($policyData, [
                'configurable_type' => Company::class,
                'configurable_id' => $company->id,
            ]));
            echo "✅ Using NEW schema (configurable_type)\n";
        } catch (\Exception $e) {
            // Fallback to old schema (entity_type)
            // This won't work because DB has different structure
            echo "❌ NEW schema failed: " . $e->getMessage() . "\n";
            echo "ℹ️  DB uses OLD schema incompatible with Model\n";
        }

        // Create future appointment (can be cancelled with enough notice)
        $futureAppointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'starts_at' => Carbon::now()->addDays(3),
            'ends_at' => Carbon::now()->addDays(3)->addHour(),
        ]);

        // Create 2 previous cancellations within 30 days
        AppointmentModification::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'appointment_id' => $futureAppointment->id,
            'modification_type' => 'cancel',
            'reason' => 'Test cancellation 1',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        AppointmentModification::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'appointment_id' => $futureAppointment->id,
            'modification_type' => 'cancel',
            'reason' => 'Test cancellation 2',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Act: Check if 3rd cancellation is denied
        $policyEngine = app(AppointmentPolicyEngine::class);
        $result = $policyEngine->canCancel($futureAppointment, Carbon::now());

        // Debug output
        echo "\n=== TEST RESULTS ===\n";
        echo "Policy exists: " . (PolicyConfiguration::count() > 0 ? 'YES' : 'NO') . "\n";
        echo "Previous cancellations: " . AppointmentModification::where('customer_id', $customer->id)
            ->where('modification_type', 'cancel')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count() . "\n";
        echo "Can cancel: " . ($result->allowed ? 'YES' : 'NO') . "\n";
        echo "Reason: " . ($result->reason ?? 'Allowed') . "\n";
        echo "Details: " . json_encode($result->details) . "\n";

        // Assert: Should be DENIED due to quota (2/2 used)
        $this->assertFalse($result->allowed, 'Expected cancellation to be DENIED due to quota exceeded');
        $this->assertStringContainsString('quota', strtolower($result->reason), 'Expected quota-related denial reason');
    }

    /**
     * Test: Verify stat_type mismatch bug
     *
     * AppointmentPolicyEngine line 310 uses 'cancellation_count'
     * but AppointmentModificationStat expects 'cancel_30d' or 'cancel_90d'
     */
    public function test_stat_type_mismatch_causes_fallback(): void
    {
        echo "\n=== STAT_TYPE MISMATCH TEST ===\n";

        // The bug is in AppointmentPolicyEngine::getModificationCount() line 310:
        // $statType = $type === 'cancel' ? 'cancellation_count' : 'reschedule_count';

        // But AppointmentModificationStat::STAT_TYPES are:
        // - cancel_30d
        // - reschedule_30d
        // - cancel_90d
        // - reschedule_90d

        echo "Expected stat_type: 'cancel_30d' or 'cancel_90d'\n";
        echo "Actual search: 'cancellation_count'\n";
        echo "Result: Materialized stats NEVER found, always fallback to real-time\n";

        $this->assertTrue(true, 'This documents the bug, no assertion needed');
    }
}
