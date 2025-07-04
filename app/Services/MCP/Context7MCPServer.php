<?php

namespace App\Services\MCP;

use App\Services\Context7Service;
use Exception;

/**
 * Context7 MCP Server
 * 
 * Provides access to Context7 documentation and code snippets
 * for Laravel, Filament, Retell.ai, Cal.com and other libraries
 * used in the AskProAI project.
 */
class Context7MCPServer extends BaseMCPServer
{
    protected Context7Service $context7Service;

    public function __construct()
    {
        parent::__construct();
        $this->context7Service = app(Context7Service::class);
    }

    public function getName(): string
    {
        return 'context7-docs';
    }

    public function getDescription(): string
    {
        return 'Access documentation and code snippets from Context7 for project libraries';
    }

    public function getTools(): array
    {
        return [
            [
                'name' => 'search_library',
                'description' => 'Search for a library in Context7 and get available documentation',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'library_name' => [
                            'type' => 'string',
                            'description' => 'Name of the library to search for (e.g., Laravel, Filament, Retell.ai)'
                        ]
                    ],
                    'required' => ['library_name']
                ]
            ],
            [
                'name' => 'get_documentation',
                'description' => 'Get documentation for a specific library from Context7',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'library_id' => [
                            'type' => 'string',
                            'description' => 'Context7 library ID (e.g., /laravel/docs)'
                        ],
                        'topic' => [
                            'type' => 'string',
                            'description' => 'Specific topic to focus on (optional)'
                        ],
                        'max_tokens' => [
                            'type' => 'integer',
                            'description' => 'Maximum tokens to retrieve (default: 5000)'
                        ]
                    ],
                    'required' => ['library_id']
                ]
            ],
            [
                'name' => 'search_code_examples',
                'description' => 'Search for code examples in a library',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'library_id' => [
                            'type' => 'string',
                            'description' => 'Context7 library ID'
                        ],
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query for code examples'
                        ]
                    ],
                    'required' => ['library_id', 'query']
                ]
            ],
            [
                'name' => 'get_project_libraries',
                'description' => 'Get a list of libraries relevant to the AskProAI project',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ]
        ];
    }

    public function handleToolCall(string $toolName, array $arguments): array
    {
        try {
            switch ($toolName) {
                case 'search_library':
                    return $this->searchLibrary($arguments['library_name']);
                
                case 'get_documentation':
                    return $this->getDocumentation(
                        $arguments['library_id'],
                        $arguments['topic'] ?? null,
                        $arguments['max_tokens'] ?? 5000
                    );
                
                case 'search_code_examples':
                    return $this->searchCodeExamples(
                        $arguments['library_id'],
                        $arguments['query']
                    );
                
                case 'get_project_libraries':
                    return $this->getProjectLibraries();
                
                default:
                    throw new Exception("Unknown tool: $toolName");
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search for a library in Context7
     */
    protected function searchLibrary(string $libraryName): array
    {
        $results = $this->context7Service->searchLibrary($libraryName);
        
        return [
            'success' => true,
            'libraries' => $results,
            'message' => count($results) . ' libraries found for "' . $libraryName . '"'
        ];
    }

    /**
     * Get documentation for a specific library
     */
    protected function getDocumentation(string $libraryId, ?string $topic, int $maxTokens): array
    {
        $docs = $this->context7Service->getLibraryDocs($libraryId, $topic, $maxTokens);
        
        return [
            'success' => true,
            'library_id' => $libraryId,
            'topic' => $topic,
            'documentation' => $docs['content'] ?? '',
            'snippets_count' => $docs['snippets_count'] ?? 0
        ];
    }

    /**
     * Search for code examples in a library
     */
    protected function searchCodeExamples(string $libraryId, string $query): array
    {
        $examples = $this->context7Service->searchCodeExamples($libraryId, $query);
        
        return [
            'success' => true,
            'library_id' => $libraryId,
            'query' => $query,
            'examples' => $examples,
            'count' => count($examples)
        ];
    }

    /**
     * Get list of libraries relevant to AskProAI project
     */
    protected function getProjectLibraries(): array
    {
        return [
            'success' => true,
            'libraries' => [
                [
                    'name' => 'Laravel',
                    'library_id' => '/context7/laravel',
                    'description' => 'Main framework - 5724 code snippets',
                    'trust_score' => 10,
                    'relevance' => 'critical'
                ],
                [
                    'name' => 'Filament',
                    'library_id' => '/filamentphp/filament',
                    'description' => 'Admin panel framework - 2337 code snippets',
                    'trust_score' => 8.3,
                    'relevance' => 'critical'
                ],
                [
                    'name' => 'Retell AI',
                    'library_id' => '/context7/docs_retellai_com',
                    'description' => 'AI phone service - 405 code snippets',
                    'trust_score' => 8,
                    'relevance' => 'critical'
                ],
                [
                    'name' => 'Cal.com',
                    'library_id' => '/calcom/cal.com',
                    'description' => 'Calendar integration - 388 code snippets',
                    'trust_score' => 9.2,
                    'relevance' => 'high'
                ],
                [
                    'name' => 'Laravel Horizon',
                    'library_id' => '/laravel/horizon',
                    'description' => 'Queue management',
                    'trust_score' => 9.5,
                    'relevance' => 'medium'
                ],
                [
                    'name' => 'Livewire',
                    'library_id' => '/livewire/livewire',
                    'description' => 'Dynamic UI components',
                    'trust_score' => 9,
                    'relevance' => 'high'
                ]
            ],
            'message' => 'These are the main libraries used in the AskProAI project'
        ];
    }
}