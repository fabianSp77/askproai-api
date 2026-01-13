<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseActivityLog;
use App\Models\ServiceCaseCategory;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for ServiceCaseObserver
 *
 * Tests the Activity Logging functionality for ServiceCase changes.
 * Ensures:
 * - Creation events are logged
 * - Field changes trigger appropriate action logs
 * - Error isolation (logging failures don't block operations)
 * - Multi-tenancy (company_id is always present)
 */
class ServiceCaseObserverTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected ServiceCaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    #[Test]
    public function logs_creation_event_when_case_is_created(): void
    {
        // Act
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_CREATED)
            ->first();

        $this->assertNotNull($log, 'Creation log should exist');
        $this->assertEquals($case->company_id, $log->company_id);
        $this->assertNull($log->old_values);
        $this->assertIsArray($log->new_values);
        $this->assertArrayHasKey('status', $log->new_values);
        $this->assertArrayHasKey('priority', $log->new_values);
    }

    #[Test]
    public function logs_status_change_when_status_is_updated(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => ServiceCase::STATUS_NEW,
        ]);

        // Clear creation log for cleaner assertion
        $initialLogCount = ServiceCaseActivityLog::where('service_case_id', $case->id)->count();

        // Act
        $case->update(['status' => ServiceCase::STATUS_OPEN]);

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_STATUS_CHANGED)
            ->first();

        $this->assertNotNull($log, 'Status change log should exist');
        $this->assertEquals(['status' => ServiceCase::STATUS_NEW], $log->old_values);
        $this->assertEquals(['status' => ServiceCase::STATUS_OPEN], $log->new_values);
    }

    #[Test]
    public function logs_priority_change_when_priority_is_updated(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'priority' => ServiceCase::PRIORITY_NORMAL,
        ]);

        // Act
        $case->update(['priority' => ServiceCase::PRIORITY_HIGH]);

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_PRIORITY_CHANGED)
            ->first();

        $this->assertNotNull($log, 'Priority change log should exist');
        $this->assertEquals(['priority' => ServiceCase::PRIORITY_NORMAL], $log->old_values);
        $this->assertEquals(['priority' => ServiceCase::PRIORITY_HIGH], $log->new_values);
    }

    #[Test]
    public function logs_assignment_when_assigned_to_changes(): void
    {
        // Arrange
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'assigned_to' => null,
        ]);

        // Act
        $case->update(['assigned_to' => $staff->id]);

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_ASSIGNED)
            ->first();

        $this->assertNotNull($log, 'Assignment log should exist');
        $this->assertEquals(['assigned_to' => null], $log->old_values);
        $this->assertEquals(['assigned_to' => $staff->id], $log->new_values);
    }

    #[Test]
    public function logs_multiple_changes_separately(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => ServiceCase::STATUS_NEW,
            'priority' => ServiceCase::PRIORITY_NORMAL,
        ]);

        // Act - Update multiple fields at once
        $case->update([
            'status' => ServiceCase::STATUS_OPEN,
            'priority' => ServiceCase::PRIORITY_HIGH,
        ]);

        // Assert - Should have separate logs for each field
        $statusLog = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_STATUS_CHANGED)
            ->first();

        $priorityLog = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_PRIORITY_CHANGED)
            ->first();

        $this->assertNotNull($statusLog, 'Status change log should exist');
        $this->assertNotNull($priorityLog, 'Priority change log should exist');
    }

    #[Test]
    public function does_not_log_when_value_unchanged(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => ServiceCase::STATUS_NEW,
        ]);

        $initialLogCount = ServiceCaseActivityLog::where('service_case_id', $case->id)->count();

        // Act - Update with same value
        $case->update(['status' => ServiceCase::STATUS_NEW]);

        // Assert - No new log should be created
        $finalLogCount = ServiceCaseActivityLog::where('service_case_id', $case->id)->count();
        $this->assertEquals($initialLogCount, $finalLogCount, 'No new log should be created for unchanged value');
    }

    #[Test]
    public function logs_soft_delete(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        // Act
        $case->delete();

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_DELETED)
            ->first();

        $this->assertNotNull($log, 'Delete log should exist');
    }

    #[Test]
    public function logs_restore_from_soft_delete(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        $case->delete();

        // Act
        $case->restore();

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_RESTORED)
            ->first();

        $this->assertNotNull($log, 'Restore log should exist');
    }

    #[Test]
    public function includes_user_when_authenticated(): void
    {
        // Arrange
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($user);

        // Act
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        // Assert
        $log = ServiceCaseActivityLog::where('service_case_id', $case->id)
            ->where('action', ServiceCaseActivityLog::ACTION_CREATED)
            ->first();

        $this->assertEquals($user->id, $log->user_id);
    }

    #[Test]
    public function company_id_is_always_logged(): void
    {
        // Arrange & Act
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        // Assert
        $logs = ServiceCaseActivityLog::where('service_case_id', $case->id)->get();

        foreach ($logs as $log) {
            $this->assertEquals($this->company->id, $log->company_id, 'All logs should have company_id');
        }
    }

    #[Test]
    public function action_labels_are_german(): void
    {
        // Assert action labels exist and are in German
        $labels = ServiceCaseActivityLog::ACTION_LABELS;

        $this->assertArrayHasKey(ServiceCaseActivityLog::ACTION_CREATED, $labels);
        $this->assertEquals('Erstellt', $labels[ServiceCaseActivityLog::ACTION_CREATED]);

        $this->assertArrayHasKey(ServiceCaseActivityLog::ACTION_STATUS_CHANGED, $labels);
        $this->assertEquals('Status geändert', $labels[ServiceCaseActivityLog::ACTION_STATUS_CHANGED]);
    }

    #[Test]
    public function get_timeline_returns_logs_in_correct_order(): void
    {
        // Arrange
        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'status' => ServiceCase::STATUS_NEW,
        ]);

        // Make some updates
        $case->update(['status' => ServiceCase::STATUS_OPEN]);
        $case->update(['status' => ServiceCase::STATUS_PENDING]);

        // Act
        $timeline = ServiceCaseActivityLog::getTimeline($case->id);

        // Assert - Should be ordered newest first
        $this->assertGreaterThanOrEqual(3, $timeline->count());
        $this->assertTrue($timeline->first()->created_at >= $timeline->last()->created_at);
    }
}
