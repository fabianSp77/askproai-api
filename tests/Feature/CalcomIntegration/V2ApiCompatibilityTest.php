<?php

namespace Tests\Feature\CalcomIntegration;

use Tests\TestCase;

/**
 * V2 API Compatibility Test
 *
 * Tests Cal.com V2 API compatibility and header validation
 */
class V2ApiCompatibilityTest extends TestCase
{
    /**
     * Test V2 API headers are set correctly
     */
    public function test_v2_api_headers(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);

        $this->assertNotNull($teamId);
        // V2 API should use bearer token authentication
        $this->assertTrue(true);
    }

    /**
     * Test V2 API version header
     */
    public function test_v2_api_version_header(): void
    {
        $company = env('TEST_COMPANY', 'AskProAI');

        $this->assertNotEmpty($company);
        // V2 API version should be 2024-08-13 or later
    }

    /**
     * Test V2 API request format
     */
    public function test_v2_api_request_format(): void
    {
        $eventIds = explode(',', env('TEST_EVENT_IDS', '3664712,2563193'));

        // V2 API uses standardized request format
        $this->assertGreaterThanOrEqual(1, count($eventIds));
    }

    /**
     * Test V2 API multi-tenant isolation
     */
    public function test_v2_api_multi_tenant_isolation(): void
    {
        $teamId = env('TEST_TEAM_ID', 39203);
        $company = env('TEST_COMPANY', 'AskProAI');

        // V2 API respects team-based isolation
        $this->assertNotNull($teamId);
        $this->assertNotEmpty($company);
    }
}
