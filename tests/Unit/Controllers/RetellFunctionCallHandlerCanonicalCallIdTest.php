<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\RetellFunctionCallHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for RetellFunctionCallHandler::getCanonicalCallId()
 *
 * FIX 2025-11-03: P1 Incident (call_bdcc364c) - Empty call_id Resolution
 * Part of 7-point optimization strategy (Task 2: Unit Tests)
 *
 * Test Coverage:
 * - T1.1: Webhook call_id (canonical source)
 * - T1.2: Args call_id (fallback source)
 * - T1.3: Both present (webhook priority)
 * - T1.4: Webhook empty string → null
 * - T1.5: Args empty string → null
 * - T1.6: "None" string → null
 * - T1.7: Mismatch detection + logging
 * - T1.8: Both empty + logging
 */
class RetellFunctionCallHandlerCanonicalCallIdTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // Use Laravel's service container to resolve dependencies
        $this->controller = $this->app->make(RetellFunctionCallHandler::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to access private getCanonicalCallId() method via Reflection
     */
    private function invokeGetCanonicalCallId(Request $request): ?string
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCanonicalCallId');
        $method->setAccessible(true);
        return $method->invoke($this->controller, $request);
    }

    /**
     * T1.1: Webhook call_id present → should return webhook value (canonical source)
     *
     * @test
     */
    public function it_returns_webhook_call_id_when_present()
    {
        // Arrange
        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return $context['call_id'] === 'call_webhook_123' &&
                       $context['source'] === 'webhook';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => 'call_webhook_123'],
            'args' => []
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_webhook_123', $result);
    }

    /**
     * T1.2: Args call_id present (no webhook) → should return args value (fallback)
     *
     * @test
     */
    public function it_returns_args_call_id_when_webhook_missing()
    {
        // Arrange
        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return $context['call_id'] === 'call_args_456' &&
                       $context['source'] === 'args';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => [],
            'args' => ['call_id' => 'call_args_456']
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_args_456', $result);
    }

    /**
     * T1.3: Both webhook and args present → should return webhook (canonical priority)
     *
     * @test
     */
    public function it_prioritizes_webhook_over_args_when_both_present()
    {
        // Arrange
        // Expect mismatch warning (values are different)
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Mismatch detected - using webhook source',
            Mockery::on(function ($context) {
                return $context['metric'] === 'call_id_mismatch_warnings' &&
                       $context['canonical_source'] === 'webhook' &&
                       $context['webhook_call_id'] === 'call_webhook_123' &&
                       $context['args_call_id'] === 'call_args_456';
            })
        );

        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return $context['call_id'] === 'call_webhook_123' &&
                       $context['source'] === 'webhook';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => 'call_webhook_123'],
            'args' => ['call_id' => 'call_args_456']
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_webhook_123', $result);
        $this->assertNotEquals('call_args_456', $result);
    }

    /**
     * T1.4: Webhook call_id is empty string → should normalize to null
     *
     * @test
     */
    public function it_normalizes_empty_string_webhook_to_null()
    {
        // Arrange
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Both sources empty',
            Mockery::on(function ($context) {
                return $context['metric'] === 'empty_call_id_occurrences';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => ''],  // Empty string
            'args' => []
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertNull($result);
    }

    /**
     * T1.5: Args call_id is empty string → should normalize to null
     *
     * @test
     */
    public function it_normalizes_empty_string_args_to_null()
    {
        // Arrange
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Both sources empty',
            Mockery::on(function ($context) {
                return $context['metric'] === 'empty_call_id_occurrences';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => [],
            'args' => ['call_id' => '']  // Empty string
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertNull($result);
    }

    /**
     * T1.6: "None" string value → should normalize to null
     *
     * @test
     */
    public function it_normalizes_none_string_to_null()
    {
        // Arrange
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Both sources empty',
            Mockery::on(function ($context) {
                return $context['metric'] === 'empty_call_id_occurrences';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => 'None'],  // "None" string
            'args' => []
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertNull($result);
    }

    /**
     * T1.7: Mismatch detection → should log warning and return webhook value
     *
     * @test
     */
    public function it_logs_mismatch_warning_and_uses_webhook_when_values_differ()
    {
        // Arrange
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Mismatch detected - using webhook source',
            Mockery::on(function ($context) {
                return $context['metric'] === 'call_id_mismatch_warnings' &&
                       $context['canonical_source'] === 'webhook' &&
                       $context['webhook_call_id'] === 'call_webhook_123' &&
                       $context['args_call_id'] === 'call_args_different';
            })
        );

        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return $context['call_id'] === 'call_webhook_123' &&
                       $context['source'] === 'webhook';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => 'call_webhook_123'],
            'args' => ['call_id' => 'call_args_different']  // Different value
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_webhook_123', $result);
    }

    /**
     * T1.8: Both sources empty → should log warning and return null
     *
     * @test
     */
    public function it_logs_warning_and_returns_null_when_both_sources_empty()
    {
        // Arrange
        Log::shouldReceive('warning')->once()->with(
            '⚠️ CANONICAL_CALL_ID: Both sources empty',
            Mockery::on(function ($context) {
                return isset($context['metric']) &&
                       $context['metric'] === 'empty_call_id_occurrences' &&
                       array_key_exists('webhook_value', $context) &&
                       array_key_exists('args_value', $context);
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => [],
            'args' => []
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Additional: Test that source is correctly logged (webhook)
     *
     * @test
     */
    public function it_logs_correct_source_for_webhook()
    {
        // Arrange
        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return isset($context['source']) &&
                       $context['source'] === 'webhook' &&
                       $context['call_id'] === 'call_test_123';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => ['call_id' => 'call_test_123'],
            'args' => []
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_test_123', $result);
    }

    /**
     * Additional: Test that source is correctly logged (args fallback)
     *
     * @test
     */
    public function it_logs_correct_source_for_args_fallback()
    {
        // Arrange
        Log::shouldReceive('info')->once()->with(
            '✅ CANONICAL_CALL_ID: Resolved',
            Mockery::on(function ($context) {
                return isset($context['source']) &&
                       $context['source'] === 'args' &&
                       $context['call_id'] === 'call_fallback_789';
            })
        );

        $request = Request::create('/', 'POST', [
            'call' => [],
            'args' => ['call_id' => 'call_fallback_789']
        ]);

        // Act
        $result = $this->invokeGetCanonicalCallId($request);

        // Assert
        $this->assertEquals('call_fallback_789', $result);
    }
}
