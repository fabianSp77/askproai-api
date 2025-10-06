<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\Sync\DriftDetectionService;
use App\Services\CalcomV2Client;
use App\Models\CalcomEventMap;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class DriftDetectionTest extends TestCase
{
    use RefreshDatabase;

    private DriftDetectionService $driftService;
    private CalcomV2Client $calcomClient;
    private CalcomEventMap $eventMapping;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'calcom_v2_api_key' => 'test_api_key'
        ]);

        $branch = Branch::factory()->create([
            'company_id' => $this->company->id
        ]);

        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'duration_minutes' => 60
        ]);

        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id
        ]);

        $this->eventMapping = CalcomEventMap::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'event_type_id' => 123,
            'event_name_pattern' => 'TEST-BRANCH-SERVICE-STAFF',
            'hidden' => true,
            'sync_status' => 'synced',
            'last_sync_at' => Carbon::now()->subHours(2),
            'external_changes' => 'warn'
        ]);

        $this->calcomClient = new CalcomV2Client($this->company);
        $this->driftService = new DriftDetectionService($this->calcomClient);
    }

    #[Test]
    public function detects_drift_when_event_type_modified(): void
    {
        Http::fake([
            '*/event-types' => Http::response([
                'data' => [[
                    'id' => 123,
                    'title' => 'Modified Name',
                    'slug' => 'modified-name',
                    'hidden' => false,
                    'lengthInMinutes' => 90,
                    'disableGuests' => false,
                ]]
            ], 200)
        ]);

        $drifts = $this->driftService->detectDrift();

        $this->assertNotEmpty($drifts);

        $drift = $drifts->firstWhere('type', 'modified');
        $this->assertNotNull($drift);
        $this->assertArrayHasKey('differences', $drift);

        $differences = $drift['differences'];
        $this->assertArrayHasKey('duration', $differences);
        $this->assertEquals(60, $differences['duration']['expected']);
        $this->assertEquals(90, $differences['duration']['actual']);
        $this->assertArrayHasKey('hidden', $differences);
        $this->assertEquals(true, $differences['hidden']['expected']);
        $this->assertEquals(false, $differences['hidden']['actual']);
    }

    #[Test]
    public function detects_drift_when_event_type_deleted(): void
    {
        Http::fake([
            '*/event-types' => Http::response([
                'data' => []
            ], 200)
        ]);

        $drifts = $this->driftService->detectDrift();

        $this->assertNotEmpty($drifts);
        $this->assertEquals('deleted', $drifts->first()['type']);
        $this->assertEquals('high', $drifts->first()['severity']);
    }

    #[Test]
    public function auto_resolve_with_accept_policy(): void
    {
        $this->eventMapping->update([
            'external_changes' => 'accept',
            'drift_detected_at' => Carbon::now(),
            'drift_data' => [
                'duration' => ['local' => 60, 'remote' => 90]
            ]
        ]);

        $resolved = $this->driftService->autoResolveDrifts();

        $this->assertEquals(1, $resolved);

        $this->eventMapping->refresh();
        $this->assertNull($this->eventMapping->drift_detected_at);
        $this->assertNull($this->eventMapping->drift_data);
    }

    #[Test]
    public function auto_resolve_with_reset_policy(): void
    {
        // Mock successful update
        Http::fake([
            '*/event-types/123' => Http::response([], 200)
        ]);

        $this->eventMapping->update([
            'external_changes' => 'reject',
            'drift_detected_at' => Carbon::now(),
            'drift_data' => [
                'duration' => ['local' => 60, 'remote' => 90]
            ]
        ]);

        $resolved = $this->driftService->autoResolveDrifts();

        $this->assertEquals(1, $resolved);

        $this->eventMapping->refresh();
        $this->assertNull($this->eventMapping->drift_detected_at);
    }

    #[Test]
    public function generates_drift_summary(): void
    {
        // Create multiple mappings with different drift states
        CalcomEventMap::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'drift_detected_at' => Carbon::now(),
            'external_changes' => 'warn'
        ]);

        CalcomEventMap::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'drift_detected_at' => null,
            'external_changes' => 'accept'
        ]);

        $summary = $this->driftService->getDriftSummary();

        $this->assertArrayHasKey('total_mappings', $summary);
        $this->assertArrayHasKey('mappings_with_drift', $summary);
        $this->assertArrayHasKey('drift_percentage', $summary);
        $this->assertArrayHasKey('recent_drifts', $summary);
        $this->assertArrayHasKey('policies', $summary);

        $this->assertEquals(6, $summary['total_mappings']); // 1 from setUp + 5 created
        $this->assertEquals(3, $summary['mappings_with_drift']);
        $this->assertEquals(4, $summary['policies']['warn']);
        $this->assertEquals(2, $summary['policies']['accept']);
    }

    #[Test]
    public function manual_drift_resolution(): void
    {
        $this->eventMapping->update([
            'drift_detected_at' => Carbon::now(),
            'drift_data' => ['test' => 'data']
        ]);

        // Test ignore resolution
        $result = $this->driftService->resolveDrift($this->eventMapping->id, 'ignore');
        $this->assertTrue($result);

        $this->eventMapping->refresh();
        $this->assertNull($this->eventMapping->drift_detected_at);
        $this->assertNull($this->eventMapping->drift_data);
    }

    #[Test]
    public function no_drift_when_data_matches(): void
    {
        // Mock matching data
        Http::fake([
            '*/event-types' => Http::response([
                'data' => [[
                    'id' => 123,
                    'title' => 'TEST-BRANCH-SERVICE-STAFF',
                    'slug' => 'test-branch-service-staff',
                    'hidden' => true,
                    'lengthInMinutes' => 60,
                    'disableGuests' => true,
                ]]
            ], 200)
        ]);

        $drifts = $this->driftService->detectDrift();

        $this->assertEmpty($drifts);
    }
}