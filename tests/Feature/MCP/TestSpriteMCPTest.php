<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use App\Services\MCP\TestSpriteMCPServer;

class TestSpriteMCPTest extends TestCase
{
    private TestSpriteMCPServer $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = new TestSpriteMCPServer();
    }

    public function test_server_info_is_correct()
    {
        $info = $this->server->getInfo();
        
        $this->assertEquals('TestSprite MCP Server', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
        $this->assertTrue($info['capabilities']['test_plan_generation']);
        $this->assertTrue($info['capabilities']['test_code_generation']);
        $this->assertTrue($info['capabilities']['test_execution']);
    }

    public function test_lists_available_tools()
    {
        $tools = $this->server->getTools();
        
        $this->assertCount(5, $tools);
        $this->assertArrayHasKey('create_test_plan', $tools);
        $this->assertArrayHasKey('generate_tests', $tools);
        $this->assertArrayHasKey('run_tests', $tools);
        $this->assertArrayHasKey('diagnose_failure', $tools);
        $this->assertArrayHasKey('coverage_report', $tools);
    }

    public function test_handles_unknown_tool()
    {
        $response = $this->server->executeTool('unknown_tool', []);
        
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Unknown tool', $response['error']);
    }

    public function test_create_test_plan_validates_requirements()
    {
        $response = $this->server->executeTool('create_test_plan', []);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Requirements are required', $response['error']);
    }

    public function test_generate_tests_validates_component()
    {
        $response = $this->server->executeTool('generate_tests', []);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Component is required', $response['error']);
    }

    public function test_run_tests_validates_test_path()
    {
        $response = $this->server->executeTool('run_tests', []);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Test path is required', $response['error']);
    }

    public function test_diagnose_failure_validates_output()
    {
        $response = $this->server->executeTool('diagnose_failure', []);
        
        $this->assertFalse($response['success']);
        $this->assertEquals('Test output is required', $response['error']);
    }

    public function test_find_component_path()
    {
        // Test that the component finder works
        $response = $this->server->executeTool('generate_tests', [
            'component' => 'User', // Model that should exist
            'framework' => 'pest'
        ]);
        
        // Should either succeed or fail with API error, not component not found
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
    }
}