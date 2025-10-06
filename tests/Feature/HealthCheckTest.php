<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test health endpoint returns correct status
     */
    public function test_health_endpoint_returns_correct_status(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy',
        ]);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'redis',
                'storage',
                'queue',
            ],
            'metrics',
        ]);
    }

    /**
     * Test monitoring dashboard endpoint
     */
    public function test_monitoring_dashboard_endpoint(): void
    {
        $response = $this->get('/monitor/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'realtime' => [
                'active_users',
                'requests_per_minute',
                'error_rate',
                'response_time',
            ],
            'alerts',
            'system_status',
        ]);
    }

    /**
     * Test performance headers in debug mode
     */
    public function test_performance_headers_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $response = $this->get('/api/health');

        $response->assertHeader('X-Response-Time');
        $response->assertHeader('X-Memory-Usage');
    }
}