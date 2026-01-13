<?php

namespace Tests\Unit\ServiceGateway;

use Tests\TestCase;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use ReflectionMethod;

/**
 * Tests for WebhookOutputHandler::extractExternalId()
 *
 * Tests the extraction of external ticket IDs from various webhook response formats,
 * including VisionaryData, Jira, ServiceNow, and edge cases.
 */
class ExtractExternalIdTest extends TestCase
{
    private WebhookOutputHandler $handler;
    private ReflectionMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new WebhookOutputHandler();

        // Make private method accessible for testing
        $this->method = new ReflectionMethod($this->handler, 'extractExternalId');
        $this->method->setAccessible(true);
    }

    /**
     * Helper to invoke the private extractExternalId method
     */
    private function extractExternalId(?array $response): ?string
    {
        return $this->method->invoke($this->handler, $response);
    }

    // ========================================
    // VisionaryData Format Tests
    // ========================================

    /** @test */
    public function it_extracts_ticket_id_from_visionarydata_response(): void
    {
        $response = [
            'success' => true,
            'ticket_id' => 'VD-2025-00123',
            'status' => 'created',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-2025-00123', $result);
    }

    /** @test */
    public function it_handles_visionarydata_simple_response(): void
    {
        $response = [
            'ticket_id' => 'VD-001',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-001', $result);
    }

    // ========================================
    // Standard Format Tests
    // ========================================

    /** @test */
    public function it_extracts_id_field(): void
    {
        $response = ['id' => '12345'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('12345', $result);
    }

    /** @test */
    public function it_extracts_key_field_jira_format(): void
    {
        $response = ['key' => 'PROJ-123'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('PROJ-123', $result);
    }

    /** @test */
    public function it_extracts_number_field_servicenow_format(): void
    {
        $response = ['number' => 'INC0001234'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('INC0001234', $result);
    }

    /** @test */
    public function it_extracts_case_id_field_salesforce_format(): void
    {
        $response = ['case_id' => '5001234567890ABC'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('5001234567890ABC', $result);
    }

    /** @test */
    public function it_extracts_sys_id_field_servicenow_internal(): void
    {
        $response = ['sys_id' => 'abc123def456'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('abc123def456', $result);
    }

    /** @test */
    public function it_extracts_issue_id_field_github_format(): void
    {
        $response = ['issue_id' => '999'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('999', $result);
    }

    // ========================================
    // Priority Order Tests
    // ========================================

    /** @test */
    public function it_prioritizes_id_over_other_fields(): void
    {
        $response = [
            'id' => 'primary-id',
            'key' => 'secondary-key',
            'ticket_id' => 'tertiary-ticket',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('primary-id', $result);
    }

    /** @test */
    public function it_prioritizes_key_over_ticket_id(): void
    {
        $response = [
            'key' => 'JIRA-123',
            'ticket_id' => 'VD-456',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('JIRA-123', $result);
    }

    // ========================================
    // Edge Cases: Empty/Null Values
    // ========================================

    /** @test */
    public function it_returns_null_for_null_response(): void
    {
        $result = $this->extractExternalId(null);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_empty_response(): void
    {
        $result = $this->extractExternalId([]);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_response_without_known_fields(): void
    {
        $response = [
            'success' => true,
            'message' => 'Ticket created',
            'unknown_field' => 'some-value',
        ];

        $result = $this->extractExternalId($response);

        $this->assertNull($result);
    }

    /** @test */
    public function it_skips_empty_string_values(): void
    {
        $response = [
            'id' => '',
            'ticket_id' => 'VD-123',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-123', $result);
    }

    /** @test */
    public function it_skips_null_values(): void
    {
        $response = [
            'id' => null,
            'ticket_id' => 'VD-456',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-456', $result);
    }

    /** @test */
    public function it_accepts_zero_as_valid_id(): void
    {
        // Edge case: "0" is a valid ID (fixed bug: empty(0) = true)
        $response = ['id' => 0];

        $result = $this->extractExternalId($response);

        $this->assertEquals('0', $result);
    }

    /** @test */
    public function it_accepts_string_zero_as_valid_id(): void
    {
        $response = ['id' => '0'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('0', $result);
    }

    // ========================================
    // Edge Cases: Type Safety
    // ========================================

    /** @test */
    public function it_skips_array_values(): void
    {
        // Bug fix: (string) array = "Array" - should be skipped
        $response = [
            'id' => ['nested' => 'value'],
            'ticket_id' => 'VD-789',
        ];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-789', $result);
    }

    /** @test */
    public function it_skips_nested_ticket_id(): void
    {
        // Nested objects should NOT be extracted
        $response = [
            'data' => ['ticket_id' => 'VD-nested'],
        ];

        $result = $this->extractExternalId($response);

        $this->assertNull($result);
    }

    /** @test */
    public function it_converts_integer_to_string(): void
    {
        $response = ['id' => 12345];

        $result = $this->extractExternalId($response);

        $this->assertIsString($result);
        $this->assertEquals('12345', $result);
    }

    /** @test */
    public function it_converts_float_to_string(): void
    {
        $response = ['id' => 123.45];

        $result = $this->extractExternalId($response);

        $this->assertEquals('123.45', $result);
    }

    // ========================================
    // Edge Cases: Length Validation
    // ========================================

    /** @test */
    public function it_truncates_ids_longer_than_100_characters(): void
    {
        $longId = str_repeat('A', 150);
        $response = ['ticket_id' => $longId];

        $result = $this->extractExternalId($response);

        $this->assertEquals(100, strlen($result));
        $this->assertEquals(str_repeat('A', 100), $result);
    }

    /** @test */
    public function it_accepts_ids_exactly_100_characters(): void
    {
        $exactId = str_repeat('B', 100);
        $response = ['ticket_id' => $exactId];

        $result = $this->extractExternalId($response);

        $this->assertEquals(100, strlen($result));
        $this->assertEquals($exactId, $result);
    }

    /** @test */
    public function it_accepts_short_ids(): void
    {
        $response = ['ticket_id' => 'X'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('X', $result);
    }

    // ========================================
    // Edge Cases: Sanitization
    // ========================================

    /** @test */
    public function it_removes_control_characters(): void
    {
        $response = ['ticket_id' => "VD-\x00123\x1F"];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-123', $result);
    }

    /** @test */
    public function it_trims_whitespace(): void
    {
        $response = ['ticket_id' => '  VD-123  '];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-123', $result);
    }

    /** @test */
    public function it_removes_newlines(): void
    {
        $response = ['ticket_id' => "VD-123\n"];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-123', $result);
    }

    /** @test */
    public function it_preserves_unicode_characters(): void
    {
        // UTF-8 characters should be preserved
        $response = ['ticket_id' => 'VD-🎫-123'];

        $result = $this->extractExternalId($response);

        $this->assertEquals('VD-🎫-123', $result);
    }

    /** @test */
    public function it_returns_null_for_whitespace_only_after_sanitization(): void
    {
        $response = ['ticket_id' => "  \t\n  "];

        $result = $this->extractExternalId($response);

        $this->assertNull($result);
    }

    // ========================================
    // Real-World Response Formats
    // ========================================

    /** @test */
    public function it_handles_full_jira_response(): void
    {
        $response = [
            'id' => '67890',
            'key' => 'SUPPORT-456',
            'self' => 'https://jira.example.com/rest/api/2/issue/67890',
        ];

        $result = $this->extractExternalId($response);

        // Should return 'id' as it has priority
        $this->assertEquals('67890', $result);
    }

    /** @test */
    public function it_handles_servicenow_response(): void
    {
        $response = [
            'result' => [
                'sys_id' => 'abc123',
                'number' => 'INC0012345',
            ],
        ];

        // Note: nested results are NOT extracted
        // VisionaryData/ServiceNow should return flat structure
        $result = $this->extractExternalId($response);

        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_flat_servicenow_response(): void
    {
        $response = [
            'sys_id' => 'abc123def456',
            'number' => 'INC0012345',
        ];

        $result = $this->extractExternalId($response);

        // sys_id comes after number in priority, but number comes first in the fields array
        // Actually looking at the code: number is before sys_id
        // id, key, ticket_id, number, issue_id, case_id, sys_id
        $this->assertEquals('INC0012345', $result);
    }
}
