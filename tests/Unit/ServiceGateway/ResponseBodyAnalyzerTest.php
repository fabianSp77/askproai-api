<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceGateway;

use App\Services\ServiceGateway\ResponseBodyAnalyzer;
use Tests\TestCase;

/**
 * Tests for ResponseBodyAnalyzer
 *
 * Validates detection of semantic errors in webhook response bodies.
 */
class ResponseBodyAnalyzerTest extends TestCase
{
    private ResponseBodyAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ResponseBodyAnalyzer();
    }

    // =========================================================================
    // Pattern 1: Explicit "error" field
    // =========================================================================

    /** @test */
    public function it_detects_error_field_string(): void
    {
        $response = ['error' => 'Invalid HMAC signature', 'status' => 401];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals('Invalid HMAC signature', $errorMessage);
    }

    /** @test */
    public function it_detects_error_field_with_object(): void
    {
        $response = [
            'error' => [
                'code' => 'AUTH_001',
                'message' => 'Authentication failed',
            ],
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals('Authentication failed', $errorMessage);
    }

    /** @test */
    public function it_skips_empty_error_field(): void
    {
        $response = ['error' => '', 'success' => true];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    // =========================================================================
    // Pattern 2: "errors" array
    // =========================================================================

    /** @test */
    public function it_detects_errors_array(): void
    {
        $response = [
            'errors' => [
                'Field validation failed',
                'Another error',
            ],
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorsArray', $errorClass);
        $this->assertEquals('Field validation failed', $errorMessage);
    }

    /** @test */
    public function it_detects_errors_array_with_objects(): void
    {
        $response = [
            'errors' => [
                ['message' => 'Email is required', 'field' => 'email'],
            ],
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorsArray', $errorClass);
        $this->assertEquals('Email is required', $errorMessage);
    }

    /** @test */
    public function it_skips_empty_errors_array(): void
    {
        $response = ['errors' => [], 'success' => true];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    // =========================================================================
    // Pattern 3: success = false
    // =========================================================================

    /** @test */
    public function it_detects_success_false(): void
    {
        $response = ['success' => false, 'message' => 'Operation failed'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:SuccessFalse', $errorClass);
        $this->assertEquals('Operation failed', $errorMessage);
    }

    /** @test */
    public function it_ignores_success_true(): void
    {
        $response = ['success' => true, 'data' => []];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    /** @test */
    public function it_uses_reason_field_for_success_false(): void
    {
        $response = ['success' => false, 'reason' => 'Quota exceeded'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:SuccessFalse', $errorClass);
        $this->assertEquals('Quota exceeded', $errorMessage);
    }

    // =========================================================================
    // Pattern 4: Numeric status >= 400 in body (VisionaryData)
    // =========================================================================

    /** @test */
    public function it_detects_status_401_in_body(): void
    {
        $response = ['status' => 401, 'error' => 'Invalid signature'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response, 200);

        // Note: Pattern 1 (error field) has higher priority
        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals('Invalid signature', $errorMessage);
    }

    /** @test */
    public function it_detects_status_500_in_body_without_error(): void
    {
        $response = ['status' => 500, 'message' => 'Internal server error'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response, 200);

        $this->assertEquals('SemanticError:StatusInBody', $errorClass);
        $this->assertEquals('Internal server error', $errorMessage);
    }

    /** @test */
    public function it_ignores_status_200_in_body(): void
    {
        $response = ['status' => 200, 'data' => ['id' => 123]];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    // =========================================================================
    // Pattern 5: Status string = "failed" / "error"
    // =========================================================================

    /** @test */
    public function it_detects_status_failed_string(): void
    {
        $response = ['status' => 'failed', 'message' => 'Task failed'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:StatusString', $errorClass);
        $this->assertEquals('Task failed', $errorMessage);
    }

    /** @test */
    public function it_detects_status_error_string(): void
    {
        $response = ['status' => 'error', 'reason' => 'Invalid input'];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:StatusString', $errorClass);
        $this->assertEquals('Invalid input', $errorMessage);
    }

    /** @test */
    public function it_ignores_status_success_string(): void
    {
        $response = ['status' => 'success', 'ticket_id' => 'VD-123'];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    /** @test */
    public function it_returns_null_for_null_response(): void
    {
        [$errorClass, $errorMessage] = $this->analyzer->analyze(null);

        $this->assertNull($errorClass);
        $this->assertNull($errorMessage);
    }

    /** @test */
    public function it_returns_null_for_empty_response(): void
    {
        [$errorClass, ] = $this->analyzer->analyze([]);

        $this->assertNull($errorClass);
    }

    /** @test */
    public function it_returns_null_for_successful_response(): void
    {
        $response = [
            'ticket_id' => 'VD-2025-00123',
            'success' => true,
            'status' => 'created',
        ];

        [$errorClass, ] = $this->analyzer->analyze($response);

        $this->assertNull($errorClass);
    }

    /** @test */
    public function it_truncates_long_error_messages(): void
    {
        $longMessage = str_repeat('A', 600);
        $response = ['error' => $longMessage];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals(500, strlen($errorMessage)); // Truncated with "..."
        $this->assertTrue(str_ends_with($errorMessage, '...'));
    }

    /** @test */
    public function it_removes_control_characters(): void
    {
        $response = ['error' => "Invalid\x00input\x1Fdata"];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals('Invalidinputdata', $errorMessage);
    }

    /** @test */
    public function has_error_returns_true_for_semantic_error(): void
    {
        $response = ['error' => 'Something went wrong'];

        $this->assertTrue($this->analyzer->hasError($response));
    }

    /** @test */
    public function has_error_returns_false_for_success(): void
    {
        $response = ['success' => true, 'ticket_id' => 'VD-123'];

        $this->assertFalse($this->analyzer->hasError($response));
    }

    // =========================================================================
    // Real-world response formats
    // =========================================================================

    /** @test */
    public function it_handles_visionarydata_response(): void
    {
        // VisionaryData returns HTTP 200 with error in body
        $response = [
            'error' => 'Invalid HMAC signature',
            'status' => 401,
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response, 200);

        $this->assertEquals('SemanticError:ErrorField', $errorClass);
        $this->assertEquals('Invalid HMAC signature', $errorMessage);
    }

    /** @test */
    public function it_handles_graphql_errors(): void
    {
        $response = [
            'data' => null,
            'errors' => [
                [
                    'message' => 'User not found',
                    'extensions' => ['code' => 'USER_NOT_FOUND'],
                ],
            ],
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:ErrorsArray', $errorClass);
        $this->assertEquals('User not found', $errorMessage);
    }

    /** @test */
    public function it_handles_exception_pattern(): void
    {
        $response = [
            'exception' => 'java.lang.NullPointerException',
            'message' => 'Unexpected error',
        ];

        [$errorClass, $errorMessage] = $this->analyzer->analyze($response);

        $this->assertEquals('SemanticError:Exception', $errorClass);
        $this->assertEquals('java.lang.NullPointerException', $errorMessage);
    }
}
