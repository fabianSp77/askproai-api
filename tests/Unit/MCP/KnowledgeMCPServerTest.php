<?php

namespace Tests\Unit\MCP;

use App\Models\Company;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeDocument;
use App\Services\KnowledgeBaseService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\MCP\Servers\KnowledgeMCPServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeMCPServerTest extends TestCase
{
    use RefreshDatabase;

    private KnowledgeMCPServer $server;
    private $mockKnowledgeService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockKnowledgeService = Mockery::mock(KnowledgeBaseService::class);
        $this->server = new KnowledgeMCPServer($this->mockKnowledgeService);
        
        $this->company = Company::factory()->create();
    }

    #[Test]

    public function test_can_handle_knowledge_methods()
    {
        $this->assertTrue($this->server->canHandle('knowledge.search'));
        $this->assertTrue($this->server->canHandle('knowledge.create'));
        $this->assertTrue($this->server->canHandle('knowledge.update'));
        $this->assertTrue($this->server->canHandle('knowledge.categories'));
        $this->assertFalse($this->server->canHandle('database.query'));
    }

    #[Test]

    public function test_search_knowledge_documents()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.search',
            'params' => [
                'query' => 'appointment booking',
                'company_id' => $this->company->id,
                'limit' => 5
            ]
        ]);
        
        $mockResults = [
            [
                'id' => 1,
                'title' => 'How to Book Appointments',
                'content' => 'Guide for booking appointments...',
                'relevance_score' => 0.95,
                'category' => 'Guides'
            ],
            [
                'id' => 2,
                'title' => 'Appointment Policies',
                'content' => 'Our appointment policies...',
                'relevance_score' => 0.87,
                'category' => 'Policies'
            ]
        ];
        
        $this->mockKnowledgeService->shouldReceive('search')
            ->once()
            ->with('appointment booking', Mockery::any())
            ->andReturn(['results' => $mockResults, 'total' => 2]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(2, $response->getData()['results']);
        $this->assertEquals(0.95, $response->getData()['results'][0]['relevance_score']);
    }

    #[Test]

    public function test_create_knowledge_document()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.create',
            'params' => [
                'title' => 'New FAQ: Cancellation Policy',
                'content' => 'Appointments can be cancelled up to 24 hours before...',
                'category_id' => 1,
                'company_id' => $this->company->id,
                'tags' => ['faq', 'cancellation', 'policy'],
                'is_public' => true
            ]
        ]);
        
        $mockDocument = new KnowledgeDocument([
            'id' => 123,
            'title' => 'New FAQ: Cancellation Policy',
            'slug' => 'new-faq-cancellation-policy',
            'content' => 'Appointments can be cancelled up to 24 hours before...',
            'category_id' => 1,
            'company_id' => $this->company->id
        ]);
        
        $this->mockKnowledgeService->shouldReceive('createDocument')
            ->once()
            ->with(Mockery::on(function($data) {
                return $data['title'] === 'New FAQ: Cancellation Policy';
            }))
            ->andReturn($mockDocument);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(123, $response->getData()['id']);
        $this->assertEquals('new-faq-cancellation-policy', $response->getData()['slug']);
    }

    #[Test]

    public function test_update_knowledge_document()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.update',
            'params' => [
                'id' => 123,
                'title' => 'Updated: Cancellation Policy',
                'content' => 'Updated content with new information...'
            ]
        ]);
        
        $this->mockKnowledgeService->shouldReceive('updateDocument')
            ->once()
            ->with(123, Mockery::any())
            ->andReturn([
                'success' => true,
                'document' => ['id' => 123, 'title' => 'Updated: Cancellation Policy']
            ]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->getData()['updated']);
    }

    #[Test]

    public function test_get_knowledge_categories()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.categories',
            'params' => [
                'company_id' => $this->company->id
            ]
        ]);
        
        $mockCategories = [
            ['id' => 1, 'name' => 'FAQs', 'document_count' => 15],
            ['id' => 2, 'name' => 'Guides', 'document_count' => 8],
            ['id' => 3, 'name' => 'Policies', 'document_count' => 5]
        ];
        
        $this->mockKnowledgeService->shouldReceive('getCategories')
            ->once()
            ->with($this->company->id)
            ->andReturn($mockCategories);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(3, $response->getData()['categories']);
        $this->assertEquals('FAQs', $response->getData()['categories'][0]['name']);
    }

    #[Test]

    public function test_get_document_by_slug()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.get',
            'params' => [
                'slug' => 'appointment-booking-guide',
                'company_id' => $this->company->id
            ]
        ]);
        
        $mockDocument = [
            'id' => 456,
            'title' => 'Appointment Booking Guide',
            'content' => 'Complete guide to booking appointments...',
            'views' => 1234,
            'helpful_count' => 89,
            'related_documents' => [
                ['id' => 457, 'title' => 'Cancellation Policy'],
                ['id' => 458, 'title' => 'Service Overview']
            ]
        ];
        
        $this->mockKnowledgeService->shouldReceive('getDocumentBySlug')
            ->once()
            ->with('appointment-booking-guide', $this->company->id)
            ->andReturn($mockDocument);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('Appointment Booking Guide', $response->getData()['title']);
        $this->assertCount(2, $response->getData()['related_documents']);
    }

    #[Test]

    public function test_delete_knowledge_document()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.delete',
            'params' => [
                'id' => 789,
                'company_id' => $this->company->id
            ]
        ]);
        
        $this->mockKnowledgeService->shouldReceive('deleteDocument')
            ->once()
            ->with(789, $this->company->id)
            ->andReturn(['success' => true]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->getData()['deleted']);
    }

    #[Test]

    public function test_get_popular_documents()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.popular',
            'params' => [
                'company_id' => $this->company->id,
                'limit' => 10
            ]
        ]);
        
        $mockDocuments = [
            ['id' => 1, 'title' => 'Most Viewed Guide', 'views' => 5000],
            ['id' => 2, 'title' => 'Popular FAQ', 'views' => 3500],
            ['id' => 3, 'title' => 'Trending Topic', 'views' => 2800]
        ];
        
        $this->mockKnowledgeService->shouldReceive('getPopularDocuments')
            ->once()
            ->with($this->company->id, 10)
            ->andReturn($mockDocuments);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertCount(3, $response->getData()['documents']);
        $this->assertEquals(5000, $response->getData()['documents'][0]['views']);
    }

    #[Test]

    public function test_mark_document_helpful()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.feedback',
            'params' => [
                'document_id' => 123,
                'helpful' => true,
                'user_id' => 456
            ]
        ]);
        
        $this->mockKnowledgeService->shouldReceive('recordFeedback')
            ->once()
            ->with(123, 456, true)
            ->andReturn(['success' => true, 'helpful_count' => 90]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(90, $response->getData()['helpful_count']);
    }

    #[Test]

    public function test_search_with_filters()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.search',
            'params' => [
                'query' => 'payment',
                'company_id' => $this->company->id,
                'filters' => [
                    'category_id' => 2,
                    'tags' => ['billing', 'invoice'],
                    'date_from' => '2025-01-01'
                ]
            ]
        ]);
        
        $this->mockKnowledgeService->shouldReceive('search')
            ->once()
            ->with('payment', Mockery::on(function($options) {
                return $options['filters']['category_id'] === 2 &&
                       in_array('billing', $options['filters']['tags']);
            }))
            ->andReturn(['results' => [], 'total' => 0]);
        
        $response = $this->server->execute($request);
        
        $this->assertTrue($response->isSuccess());
    }

    #[Test]

    public function test_handle_document_not_found()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.get',
            'params' => [
                'slug' => 'non-existent-document',
                'company_id' => $this->company->id
            ]
        ]);
        
        $this->mockKnowledgeService->shouldReceive('getDocumentBySlug')
            ->once()
            ->andReturn(null);
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('NOT_FOUND', $response->getError()->getCode());
    }

    #[Test]

    public function test_validate_required_params()
    {
        $request = new MCPRequest([
            'method' => 'knowledge.create',
            'params' => [
                // Missing required 'title' and 'content'
                'category_id' => 1
            ]
        ]);
        
        $response = $this->server->execute($request);
        
        $this->assertFalse($response->isSuccess());
        $this->assertEquals('MISSING_PARAMS', $response->getError()->getCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}