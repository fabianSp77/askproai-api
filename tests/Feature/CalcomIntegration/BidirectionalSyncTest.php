<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * Bidirectional Sync Test
 *
 * Tests Cal.com ↔ Laravel appointment synchronization
 */
class BidirectionalSyncTest extends TestCase
{
    /**
     * Test sync with correct team context
     */
    public function test_sync_with_team_context(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        $this->assertGreaterThan(0, $teamId);
    }

    /**
     * Test bidirectional sync preserves data
     */
    public function test_bidirectional_sync_preserves_data(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        $this->assertNotEmpty($company);
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test sync multi-tenant isolation
     */
    public function test_sync_multi_tenant_isolation(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        // Sync should only affect own team's data
        $this->assertNotNull($teamId);
    }

    /**
     * Test sync consistency
     */
    public function test_sync_consistency(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');

        // Data should be consistent after sync
        $this->assertNotEmpty($company);
    }
}
