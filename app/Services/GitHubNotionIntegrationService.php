<?php

namespace App\Services;

use App\Services\MCP\GitHubMCPServer;
use App\Services\MCP\NotionMCPServer;
use App\Services\MemoryBankAutomationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class GitHubNotionIntegrationService
{
    protected GitHubMCPServer $github;
    protected NotionMCPServer $notion;
    protected MemoryBankAutomationService $memory;
    
    protected array $config;
    protected array $syncMappings = [];
    
    public function __construct(
        GitHubMCPServer $github,
        NotionMCPServer $notion,
        MemoryBankAutomationService $memory
    ) {
        $this->github = $github;
        $this->notion = $notion;
        $this->memory = $memory;
        
        $this->config = [
            'sync_interval' => 300, // 5 minutes
            'batch_size' => 50,
            'enabled_syncs' => [
                'issues_to_tasks' => true,
                'prs_to_reviews' => true,
                'commits_to_changelog' => true,
                'releases_to_docs' => true,
            ]
        ];
    }
    
    /**
     * Configure mapping between GitHub and Notion
     */
    public function configureMappings(array $mappings): void
    {
        $this->syncMappings = array_merge($this->syncMappings, $mappings);
        
        // Store in Memory Bank for persistence
        $this->memory->rememberContext('github_notion_mappings', [
            'mappings' => $this->syncMappings,
            'configured_at' => now()->toDateTimeString()
        ], ['integration', 'configuration']);
    }
    
    /**
     * Sync GitHub issues to Notion tasks
     */
    public function syncIssuesToTasks(string $owner, string $repo, string $notionDatabaseId): array
    {
        Log::info('Starting GitHub to Notion sync', [
            'repo' => "$owner/$repo",
            'database' => $notionDatabaseId
        ]);
        
        try {
            // Get issues from GitHub
            $issuesResult = $this->github->executeTool('list_issues', [
                'owner' => $owner,
                'repo' => $repo,
                'state' => 'open',
                'per_page' => $this->config['batch_size']
            ]);
            
            if (!$issuesResult['success']) {
                throw new \Exception('Failed to fetch GitHub issues: ' . $issuesResult['error']);
            }
            
            $issues = $issuesResult['data']['issues'] ?? [];
            $synced = 0;
            $errors = [];
            
            foreach ($issues as $issue) {
                try {
                    // Check if already synced
                    $syncKey = "github_issue_{$owner}_{$repo}_{$issue['number']}";
                    $existingSync = Cache::get($syncKey);
                    
                    if ($existingSync && !$this->hasIssueChanged($issue, $existingSync)) {
                        continue;
                    }
                    
                    // Create or update Notion task
                    $taskData = $this->mapIssueToTask($issue, $owner, $repo);
                    
                    if ($existingSync && isset($existingSync['notion_page_id'])) {
                        // Update existing page
                        $result = $this->notion->executeTool('update_page', [
                            'page_id' => $existingSync['notion_page_id'],
                            'properties' => $taskData['properties']
                        ]);
                    } else {
                        // Create new page
                        $result = $this->notion->executeTool('create_page', [
                            'database_id' => $notionDatabaseId,
                            'properties' => $taskData['properties'],
                            'children' => $taskData['children'] ?? []
                        ]);
                    }
                    
                    if ($result['success']) {
                        // Cache sync info
                        Cache::put($syncKey, [
                            'issue' => $issue,
                            'notion_page_id' => $result['data']['page_id'] ?? $result['data']['id'],
                            'synced_at' => now()->toDateTimeString()
                        ], 86400); // 24 hours
                        
                        $synced++;
                    } else {
                        $errors[] = "Issue #{$issue['number']}: " . $result['error'];
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Issue #{$issue['number']}: " . $e->getMessage();
                }
            }
            
            // Record sync in Memory Bank
            $this->memory->rememberContext('github_notion_sync', [
                'type' => 'issues_to_tasks',
                'repo' => "$owner/$repo",
                'database' => $notionDatabaseId,
                'synced' => $synced,
                'total' => count($issues),
                'errors' => $errors
            ], ['sync', 'github', 'notion']);
            
            return [
                'success' => true,
                'synced' => $synced,
                'total' => count($issues),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            Log::error('GitHub to Notion sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0
            ];
        }
    }
    
    /**
     * Sync GitHub PRs to Notion review tasks
     */
    public function syncPRsToReviews(string $owner, string $repo, string $notionDatabaseId): array
    {
        try {
            // Get PRs from GitHub
            $prsResult = $this->github->executeTool('list_pull_requests', [
                'owner' => $owner,
                'repo' => $repo,
                'state' => 'open',
                'per_page' => $this->config['batch_size']
            ]);
            
            if (!$prsResult['success']) {
                throw new \Exception('Failed to fetch GitHub PRs: ' . $prsResult['error']);
            }
            
            $prs = $prsResult['data']['pull_requests'] ?? [];
            $synced = 0;
            $errors = [];
            
            foreach ($prs as $pr) {
                try {
                    $reviewData = $this->mapPRToReview($pr, $owner, $repo);
                    
                    $result = $this->notion->executeTool('create_page', [
                        'database_id' => $notionDatabaseId,
                        'properties' => $reviewData['properties'],
                        'children' => $reviewData['children'] ?? []
                    ]);
                    
                    if ($result['success']) {
                        $synced++;
                        
                        // Add review comments if any
                        if (!empty($pr['review_comments'])) {
                            $this->syncPRComments($pr, $result['data']['id']);
                        }
                    } else {
                        $errors[] = "PR #{$pr['number']}: " . $result['error'];
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "PR #{$pr['number']}: " . $e->getMessage();
                }
            }
            
            return [
                'success' => true,
                'synced' => $synced,
                'total' => count($prs),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0
            ];
        }
    }
    
    /**
     * Sync GitHub releases to Notion documentation
     */
    public function syncReleasesToDocs(string $owner, string $repo, string $notionParentPageId): array
    {
        try {
            // Get releases from GitHub
            $releasesResult = $this->github->executeTool('list_releases', [
                'owner' => $owner,
                'repo' => $repo,
                'per_page' => 10
            ]);
            
            if (!$releasesResult['success']) {
                throw new \Exception('Failed to fetch releases: ' . $releasesResult['error']);
            }
            
            $releases = $releasesResult['data']['releases'] ?? [];
            $synced = 0;
            
            foreach ($releases as $release) {
                // Create release documentation page
                $docData = $this->mapReleaseToDoc($release, $owner, $repo);
                
                $result = $this->notion->executeTool('create_page', [
                    'parent_id' => $notionParentPageId,
                    'properties' => $docData['properties'],
                    'children' => $docData['children']
                ]);
                
                if ($result['success']) {
                    $synced++;
                }
            }
            
            return [
                'success' => true,
                'synced' => $synced,
                'total' => count($releases)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create automated sync workflow
     */
    public function createSyncWorkflow(array $config): array
    {
        $workflowId = 'github_notion_' . uniqid();
        
        // Store workflow configuration
        $this->memory->rememberContext('sync_workflow', [
            'id' => $workflowId,
            'config' => $config,
            'created_at' => now()->toDateTimeString(),
            'status' => 'active'
        ], ['workflow', 'automation']);
        
        // Schedule sync jobs
        foreach ($config['syncs'] as $sync) {
            if ($sync['enabled']) {
                Cache::put("workflow_{$workflowId}_sync_{$sync['type']}", $sync, 86400);
            }
        }
        
        return [
            'success' => true,
            'workflow_id' => $workflowId,
            'scheduled_syncs' => count(array_filter($config['syncs'], fn($s) => $s['enabled']))
        ];
    }
    
    /**
     * Get sync status and history
     */
    public function getSyncStatus(?string $workflowId = null): array
    {
        $searchParams = [
            'query' => $workflowId ?? 'github_notion_sync',
            'context' => 'work_context',
            'tags' => ['sync'],
            'limit' => 50
        ];
        
        $result = $this->memory->search(
            $searchParams['query'],
            $searchParams['context'],
            $searchParams['tags']
        );
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to retrieve sync history'];
        }
        
        $syncs = collect($result['data']['results'] ?? [])
            ->map(function ($item) {
                return $item['value']['data'] ?? $item['value'];
            })
            ->sortByDesc('synced_at')
            ->values()
            ->toArray();
        
        return [
            'success' => true,
            'syncs' => $syncs,
            'total' => count($syncs)
        ];
    }
    
    // Helper methods
    
    protected function mapIssueToTask(array $issue, string $owner, string $repo): array
    {
        $properties = [
            'title' => [
                'title' => [
                    ['text' => ['content' => $issue['title']]]
                ]
            ],
            'Status' => [
                'select' => ['name' => $this->mapIssueStatus($issue['state'])]
            ],
            'Priority' => [
                'select' => ['name' => $this->detectPriority($issue)]
            ],
            'GitHub Issue' => [
                'url' => $issue['html_url']
            ],
            'Repository' => [
                'select' => ['name' => "$owner/$repo"]
            ],
            'Labels' => [
                'multi_select' => array_map(function($label) {
                    return ['name' => $label['name']];
                }, $issue['labels'] ?? [])
            ],
            'Assignees' => [
                'people' => array_map(function($assignee) {
                    return ['email' => $assignee['email'] ?? $assignee['login'] . '@github.com'];
                }, $issue['assignees'] ?? [])
            ]
        ];
        
        $children = [];
        
        // Add issue body as content
        if (!empty($issue['body'])) {
            $children[] = [
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [
                        ['text' => ['content' => $issue['body']]]
                    ]
                ]
            ];
        }
        
        // Add metadata
        $children[] = [
            'type' => 'divider',
            'divider' => new \stdClass()
        ];
        
        $children[] = [
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    ['text' => ['content' => "Created: {$issue['created_at']}\n"]]
                ]
            ]
        ];
        
        return [
            'properties' => $properties,
            'children' => $children
        ];
    }
    
    protected function mapPRToReview(array $pr, string $owner, string $repo): array
    {
        $properties = [
            'title' => [
                'title' => [
                    ['text' => ['content' => "[PR] {$pr['title']}"]]
                ]
            ],
            'Status' => [
                'select' => ['name' => $this->mapPRStatus($pr)]
            ],
            'Type' => [
                'select' => ['name' => 'Code Review']
            ],
            'GitHub PR' => [
                'url' => $pr['html_url']
            ],
            'Author' => [
                'people' => [
                    ['email' => $pr['user']['email'] ?? $pr['user']['login'] . '@github.com']
                ]
            ],
            'Reviewers' => [
                'people' => array_map(function($reviewer) {
                    return ['email' => $reviewer['email'] ?? $reviewer['login'] . '@github.com'];
                }, $pr['requested_reviewers'] ?? [])
            ]
        ];
        
        $children = [
            [
                'type' => 'heading_2',
                'heading_2' => [
                    'rich_text' => [['text' => ['content' => 'Pull Request Details']]]
                ]
            ],
            [
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [
                        ['text' => ['content' => $pr['body'] ?? 'No description provided']]
                    ]
                ]
            ]
        ];
        
        // Add PR stats
        if (isset($pr['additions']) || isset($pr['deletions'])) {
            $children[] = [
                'type' => 'callout',
                'callout' => [
                    'icon' => ['emoji' => '📊'],
                    'rich_text' => [
                        ['text' => ['content' => sprintf(
                            "Changes: +%d / -%d files: %d",
                            $pr['additions'] ?? 0,
                            $pr['deletions'] ?? 0,
                            $pr['changed_files'] ?? 0
                        )]]
                    ]
                ]
            ];
        }
        
        return [
            'properties' => $properties,
            'children' => $children
        ];
    }
    
    protected function mapReleaseToDoc(array $release, string $owner, string $repo): array
    {
        $properties = [
            'title' => [
                'title' => [
                    ['text' => ['content' => "Release {$release['tag_name']}: {$release['name']}"]]
                ]
            ],
            'Type' => [
                'select' => ['name' => 'Release Notes']
            ],
            'Version' => [
                'text' => ['content' => $release['tag_name']]
            ],
            'Published' => [
                'date' => ['start' => date('Y-m-d', strtotime($release['published_at']))]
            ]
        ];
        
        $children = [
            [
                'type' => 'heading_1',
                'heading_1' => [
                    'rich_text' => [['text' => ['content' => $release['name']]]]
                ]
            ],
            [
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [
                        ['text' => ['content' => "Version: {$release['tag_name']}"]]
                    ]
                ]
            ],
            [
                'type' => 'divider',
                'divider' => new \stdClass()
            ]
        ];
        
        // Parse and format release body
        if (!empty($release['body'])) {
            $sections = $this->parseReleaseNotes($release['body']);
            foreach ($sections as $section) {
                $children = array_merge($children, $section);
            }
        }
        
        // Add download links
        if (!empty($release['assets'])) {
            $children[] = [
                'type' => 'heading_2',
                'heading_2' => [
                    'rich_text' => [['text' => ['content' => 'Downloads']]]
                ]
            ];
            
            foreach ($release['assets'] as $asset) {
                $children[] = [
                    'type' => 'bulleted_list_item',
                    'bulleted_list_item' => [
                        'rich_text' => [
                            [
                                'text' => ['content' => $asset['name'] . ' - '],
                            ],
                            [
                                'text' => [
                                    'content' => 'Download',
                                    'link' => ['url' => $asset['browser_download_url']]
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }
        
        return [
            'properties' => $properties,
            'children' => $children
        ];
    }
    
    protected function hasIssueChanged(array $newIssue, array $cachedData): bool
    {
        $oldIssue = $cachedData['issue'];
        
        return $oldIssue['updated_at'] !== $newIssue['updated_at'] ||
               $oldIssue['state'] !== $newIssue['state'] ||
               $oldIssue['title'] !== $newIssue['title'];
    }
    
    protected function mapIssueStatus(string $state): string
    {
        return match($state) {
            'open' => 'To Do',
            'closed' => 'Done',
            default => 'In Progress'
        };
    }
    
    protected function mapPRStatus(array $pr): string
    {
        if ($pr['merged']) {
            return 'Merged';
        }
        
        if ($pr['draft']) {
            return 'Draft';
        }
        
        return match($pr['state']) {
            'open' => 'In Review',
            'closed' => 'Closed',
            default => 'Pending'
        };
    }
    
    protected function detectPriority(array $issue): string
    {
        $labels = collect($issue['labels'] ?? [])->pluck('name')->map('strtolower');
        
        if ($labels->contains('critical') || $labels->contains('urgent')) {
            return 'High';
        }
        
        if ($labels->contains('bug') || $labels->contains('important')) {
            return 'Medium';
        }
        
        return 'Low';
    }
    
    protected function parseReleaseNotes(string $body): array
    {
        $sections = [];
        $lines = explode("\n", $body);
        $currentSection = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^#{1,3}\s+(.+)$/', $line, $matches)) {
                // New section
                if (!empty($currentSection)) {
                    $sections[] = $currentSection;
                }
                
                $level = substr_count($line, '#');
                $currentSection = [
                    [
                        'type' => "heading_{$level}",
                        "heading_{$level}" => [
                            'rich_text' => [['text' => ['content' => trim($matches[1])]]]
                        ]
                    ]
                ];
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                // Bullet point
                $currentSection[] = [
                    'type' => 'bulleted_list_item',
                    'bulleted_list_item' => [
                        'rich_text' => [['text' => ['content' => trim($matches[1])]]]
                    ]
                ];
            } elseif (!empty(trim($line))) {
                // Regular paragraph
                $currentSection[] = [
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [['text' => ['content' => trim($line)]]]
                    ]
                ];
            }
        }
        
        if (!empty($currentSection)) {
            $sections[] = $currentSection;
        }
        
        return $sections;
    }
    
    protected function syncPRComments(array $pr, string $notionPageId): void
    {
        // This would sync PR review comments to Notion page comments
        // Implementation depends on Notion API support for comments
        Log::info('PR comments sync not yet implemented', [
            'pr' => $pr['number'],
            'page' => $notionPageId
        ]);
    }
}