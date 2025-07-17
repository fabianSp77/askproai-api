<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GitHubMCPServer implements ExternalMCPProvider
{
    protected string $name = 'github';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'repository_management',
        'issue_tracking',
        'pull_requests',
        'code_access',
        'branch_management',
        'commit_history'
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get tool definitions for GitHub operations
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'search_repositories',
                'description' => 'Search GitHub repositories',
                'category' => 'repository',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query'
                        ],
                        'sort' => [
                            'type' => 'string',
                            'enum' => ['stars', 'forks', 'updated'],
                            'description' => 'Sort by criteria'
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'description' => 'Results per page (max 100)',
                            'default' => 10
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'get_repository',
                'description' => 'Get details about a specific repository',
                'category' => 'repository',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ]
                    ],
                    'required' => ['owner', 'repo']
                ]
            ],
            [
                'name' => 'list_issues',
                'description' => 'List issues for a repository',
                'category' => 'issues',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'state' => [
                            'type' => 'string',
                            'enum' => ['open', 'closed', 'all'],
                            'default' => 'open'
                        ],
                        'labels' => [
                            'type' => 'string',
                            'description' => 'Comma-separated list of labels'
                        ]
                    ],
                    'required' => ['owner', 'repo']
                ]
            ],
            [
                'name' => 'create_issue',
                'description' => 'Create a new issue',
                'category' => 'issues',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'Issue title'
                        ],
                        'body' => [
                            'type' => 'string',
                            'description' => 'Issue body'
                        ],
                        'labels' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Issue labels'
                        ]
                    ],
                    'required' => ['owner', 'repo', 'title']
                ]
            ],
            [
                'name' => 'list_pull_requests',
                'description' => 'List pull requests for a repository',
                'category' => 'pull_requests',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'state' => [
                            'type' => 'string',
                            'enum' => ['open', 'closed', 'all'],
                            'default' => 'open'
                        ]
                    ],
                    'required' => ['owner', 'repo']
                ]
            ],
            [
                'name' => 'get_file_contents',
                'description' => 'Get contents of a file in a repository',
                'category' => 'code',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'File path'
                        ],
                        'ref' => [
                            'type' => 'string',
                            'description' => 'Branch, tag, or commit',
                            'default' => 'main'
                        ]
                    ],
                    'required' => ['owner', 'repo', 'path']
                ]
            ],
            [
                'name' => 'list_branches',
                'description' => 'List branches in a repository',
                'category' => 'branches',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ]
                    ],
                    'required' => ['owner', 'repo']
                ]
            ],
            [
                'name' => 'get_commit',
                'description' => 'Get details about a specific commit',
                'category' => 'commits',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'ref' => [
                            'type' => 'string',
                            'description' => 'Commit SHA'
                        ]
                    ],
                    'required' => ['owner', 'repo', 'ref']
                ]
            ],
            [
                'name' => 'list_releases',
                'description' => 'List releases for a repository',
                'category' => 'releases',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'Repository owner'
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'Repository name'
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'description' => 'Results per page',
                            'default' => 10
                        ]
                    ],
                    'required' => ['owner', 'repo']
                ]
            ]
        ];
    }

    /**
     * Execute a GitHub operation
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing GitHub tool: {$tool}", $arguments);

        // Validate GitHub token
        if (!config('services.github.token')) {
            return [
                'success' => false,
                'error' => 'GitHub token not configured. Please set GITHUB_TOKEN in .env',
                'data' => null
            ];
        }

        // Map to external server method
        return $this->callExternalServer($tool, $arguments);
    }

    /**
     * Create result object
     */
    public function result($data = null, ?string $error = null): object
    {
        return (object) [
            'isSuccess' => is_null($error),
            'getData' => fn() => $data,
            'getError' => fn() => $error
        ];
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        $result = Process::run('pgrep -f "github-mcp/index.js"');
        return $result->successful() && !empty(trim($result->output()));
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        $config = config('mcp-external.external_servers.github');
        
        if (!$config || !$config['enabled']) {
            return false;
        }

        $env = array_merge($_ENV, $config['env'] ?? []);
        
        $result = Process::env($env)
            ->path(dirname($config['args'][0]))
            ->run($config['command'] . ' ' . implode(' ', $config['args']) . ' > /dev/null 2>&1 &');

        return $result->successful();
    }

    /**
     * Call the external Node.js server
     */
    protected function callExternalServer(string $tool, array $arguments): array
    {
        try {
            // For now, use direct GitHub API calls
            return $this->directGitHubApiCall($tool, $arguments);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'GitHub operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Direct GitHub API calls
     */
    protected function directGitHubApiCall(string $tool, array $arguments): array
    {
        $token = config('services.github.token');
        $baseUrl = 'https://api.github.com';

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'AskProAI-MCP'
        ];

        try {
            switch ($tool) {
                case 'search_repositories':
                    $response = Http::withHeaders($headers)->get("{$baseUrl}/search/repositories", [
                        'q' => $arguments['query'],
                        'sort' => $arguments['sort'] ?? 'stars',
                        'per_page' => $arguments['per_page'] ?? 10
                    ]);
                    break;

                case 'get_repository':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}"
                    );
                    break;

                case 'list_issues':
                    $query = [
                        'state' => $arguments['state'] ?? 'open'
                    ];
                    if (isset($arguments['labels'])) {
                        $query['labels'] = $arguments['labels'];
                    }
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/issues",
                        $query
                    );
                    break;

                case 'create_issue':
                    $response = Http::withHeaders($headers)->post(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/issues",
                        [
                            'title' => $arguments['title'],
                            'body' => $arguments['body'] ?? '',
                            'labels' => $arguments['labels'] ?? []
                        ]
                    );
                    break;

                case 'list_pull_requests':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/pulls",
                        ['state' => $arguments['state'] ?? 'open']
                    );
                    break;

                case 'get_file_contents':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/contents/{$arguments['path']}",
                        ['ref' => $arguments['ref'] ?? 'main']
                    );
                    
                    $data = $response->json();
                    
                    // Decode base64 content if it's a file
                    if (isset($data['content']) && $data['encoding'] === 'base64') {
                        $data['decoded_content'] = base64_decode($data['content']);
                    }
                    
                    return [
                        'success' => true,
                        'error' => null,
                        'data' => $data
                    ];

                case 'list_branches':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/branches"
                    );
                    break;

                case 'get_commit':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/commits/{$arguments['ref']}"
                    );
                    break;

                case 'list_releases':
                    $response = Http::withHeaders($headers)->get(
                        "{$baseUrl}/repos/{$arguments['owner']}/{$arguments['repo']}/releases",
                        ['per_page' => $arguments['per_page'] ?? 10]
                    );
                    break;

                default:
                    return [
                        'success' => false,
                        'error' => "Unknown GitHub tool: {$tool}",
                        'data' => null
                    ];
            }

            if ($response->successful()) {
                $data = $response->json();
                
                // Format response based on tool
                $formattedData = match($tool) {
                    'list_issues' => ['issues' => $data],
                    'list_pull_requests' => ['pull_requests' => $data],
                    'list_releases' => ['releases' => $data],
                    'search_repositories' => ['repositories' => $data['items'] ?? []],
                    default => $data
                };
                
                return [
                    'success' => true,
                    'error' => null,
                    'data' => $formattedData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "GitHub API returned {$response->status()}: {$response->body()}",
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'GitHub API error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'token_configured' => !empty(config('services.github.token')),
            'external_server' => config('mcp-external.external_servers.github'),
            'is_running' => $this->isExternalServerRunning()
        ];
    }
}