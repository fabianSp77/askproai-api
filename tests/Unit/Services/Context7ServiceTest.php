<?php

namespace Tests\Unit\Services;

use App\Services\Context7Service;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Context7ServiceTest extends TestCase
{
    protected Context7Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Context7Service();
        Cache::flush();
    }

    public function test_can_search_for_library()
    {
        $results = $this->service->searchLibrary('laravel');
        
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        
        $firstResult = $results[0];
        $this->assertArrayHasKey('name', $firstResult);
        $this->assertArrayHasKey('library_id', $firstResult);
        $this->assertArrayHasKey('description', $firstResult);
        $this->assertArrayHasKey('trust_score', $firstResult);
    }

    public function test_search_returns_exact_match_first()
    {
        $results = $this->service->searchLibrary('filament');
        
        $this->assertNotEmpty($results);
        $this->assertEquals('Filament', $results[0]['name']);
        $this->assertEquals('/filamentphp/filament', $results[0]['library_id']);
    }

    public function test_can_get_library_documentation()
    {
        $docs = $this->service->getLibraryDocs('/context7/laravel', null, 5000);
        
        $this->assertArrayHasKey('library_id', $docs);
        $this->assertArrayHasKey('library_name', $docs);
        $this->assertArrayHasKey('content', $docs);
        $this->assertArrayHasKey('snippets_count', $docs);
        
        $this->assertNotEmpty($docs['content']);
    }

    public function test_can_get_documentation_with_topic()
    {
        $docs = $this->service->getLibraryDocs('/context7/laravel', 'multi-tenancy', 5000);
        
        $this->assertEquals('multi-tenancy', $docs['topic']);
        $this->assertStringContainsString('Multi-Tenancy', $docs['content']);
    }

    public function test_can_search_code_examples()
    {
        $examples = $this->service->searchCodeExamples('/retell/docs', 'webhook');
        
        $this->assertIsArray($examples);
    }

    public function test_library_info_is_cached()
    {
        // First call
        $results1 = $this->service->searchLibrary('laravel');
        
        // Second call should be cached
        $results2 = $this->service->searchLibrary('laravel');
        
        $this->assertEquals($results1, $results2);
        
        // Verify cache exists
        $cacheKey = 'context7:search:' . md5('laravel');
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_retell_documentation_includes_webhook_info()
    {
        $docs = $this->service->getLibraryDocs('/context7/docs_retellai_com', 'webhook-signature', 5000);
        
        $this->assertStringContainsString('webhook', strtolower($docs['content']));
        $this->assertStringContainsString('signature', strtolower($docs['content']));
    }

    public function test_filament_documentation_includes_livewire_info()
    {
        $docs = $this->service->getLibraryDocs('/filamentphp/filament', 'livewire-v3', 5000);
        
        $this->assertStringContainsString('livewire', strtolower($docs['content']));
        $this->assertStringContainsString('component', strtolower($docs['content']));
    }

    public function test_calcom_documentation_includes_api_v2_info()
    {
        $docs = $this->service->getLibraryDocs('/calcom/cal.com', 'api-v2', 5000);
        
        $this->assertStringContainsString('api', strtolower($docs['content']));
        $this->assertStringContainsString('cal.com', strtolower($docs['content']));
    }

    public function test_generates_code_examples_for_webhook()
    {
        // Create test files if they don't exist
        $middlewarePath = app_path('Http/Middleware/VerifyRetellSignature.php');
        $scopePath = app_path('Scopes/TenantScope.php');
        
        if (!file_exists($middlewarePath)) {
            $this->markTestSkipped('VerifyRetellSignature.php does not exist');
        }
        
        $examples = $this->service->searchCodeExamples('retell', 'webhook');
        
        $this->assertIsArray($examples);
        if (!empty($examples)) {
            $firstExample = $examples[0];
            $this->assertArrayHasKey('title', $firstExample);
            $this->assertArrayHasKey('code', $firstExample);
            $this->assertArrayHasKey('language', $firstExample);
        }
    }

    public function test_unknown_library_returns_default_values()
    {
        $results = $this->service->searchLibrary('unknown-library-xyz');
        
        $this->assertIsArray($results);
        // Should return empty or no exact match
    }

    public function test_trust_scores_are_numeric()
    {
        $results = $this->service->searchLibrary('laravel');
        
        foreach ($results as $result) {
            $this->assertIsNumeric($result['trust_score']);
            $this->assertGreaterThanOrEqual(0, $result['trust_score']);
            $this->assertLessThanOrEqual(10, $result['trust_score']);
        }
    }
}