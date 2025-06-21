<?php

namespace App\Services\MCP;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeVersion;
use App\Models\KnowledgeAnalytic;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeMCPServer
{
    protected array $config;
    protected array $industryTemplates;
    
    public function __construct()
    {
        $this->config = config('knowledge', [
            'cache' => [
                'ttl' => 3600, // 1 hour
                'prefix' => 'mcp:knowledge'
            ],
            'search' => [
                'min_length' => 3,
                'max_results' => 50
            ],
            'ai' => [
                'max_context_documents' => 10,
                'max_context_length' => 4000
            ]
        ]);
        
        $this->initializeIndustryTemplates();
    }
    
    /**
     * Get company-specific knowledge documents
     */
    public function getCompanyKnowledge(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $categorySlug = $params['category'] ?? null;
        $status = $params['status'] ?? 'published';
        $limit = min($params['limit'] ?? 20, 100);
        $offset = $params['offset'] ?? 0;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        $cacheKey = $this->getCacheKey('company_knowledge', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId, $categorySlug, $status, $limit, $offset) {
            $query = KnowledgeDocument::where('company_id', $companyId)
                ->where('status', $status);
            
            if ($categorySlug) {
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }
            
            $total = $query->count();
            
            $documents = $query->with(['category', 'tags', 'creator'])
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'desc')
                ->skip($offset)
                ->take($limit)
                ->get();
            
            return [
                'documents' => $documents->map(function ($doc) {
                    return $this->formatDocument($doc);
                }),
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ],
                'metadata' => [
                    'company_id' => $companyId,
                    'generated_at' => now()->toIso8601String()
                ]
            ];
        });
    }
    
    /**
     * Search knowledge documents
     */
    public function searchKnowledge(array $params): array
    {
        $query = $params['query'] ?? '';
        $companyId = $params['company_id'] ?? null;
        $categories = $params['categories'] ?? [];
        $tags = $params['tags'] ?? [];
        $limit = min($params['limit'] ?? 20, $this->config['search']['max_results']);
        
        if (strlen($query) < $this->config['search']['min_length']) {
            return ['error' => "Query must be at least {$this->config['search']['min_length']} characters"];
        }
        
        $cacheKey = $this->getCacheKey('search', $params);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $companyId, $categories, $tags, $limit) {
            $searchQuery = KnowledgeDocument::query()
                ->where('status', 'published');
            
            // Company filter
            if ($companyId) {
                $searchQuery->where('company_id', $companyId);
            }
            
            // Full-text search
            $searchQuery->whereRaw('MATCH(title, content, excerpt) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);
            
            // Category filter
            if (!empty($categories)) {
                $searchQuery->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('slug', $categories);
                });
            }
            
            // Tag filter
            if (!empty($tags)) {
                $searchQuery->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('slug', $tags);
                });
            }
            
            // Add relevance score
            $searchQuery->selectRaw('*, MATCH(title, content, excerpt) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$query])
                ->orderByDesc('relevance')
                ->orderByDesc('view_count');
            
            $results = $searchQuery->with(['category', 'tags'])
                ->limit($limit)
                ->get();
            
            // Track search analytics
            $this->trackSearch($query, $results->count(), $companyId);
            
            return [
                'query' => $query,
                'results' => $results->map(function ($doc) {
                    return array_merge($this->formatDocument($doc), [
                        'relevance' => $doc->relevance,
                        'excerpt' => $this->highlightSearchTerms($doc->excerpt ?? Str::limit($doc->content, 200), $query)
                    ]);
                }),
                'count' => $results->count(),
                'metadata' => [
                    'search_time' => round(microtime(true) - LARAVEL_START, 3),
                    'generated_at' => now()->toIso8601String()
                ]
            ];
        });
    }
    
    /**
     * Update knowledge document
     */
    public function updateKnowledge(array $params): array
    {
        $documentId = $params['document_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        $userId = $params['user_id'] ?? null;
        
        if (!$documentId || !$companyId) {
            return ['error' => 'document_id and company_id are required'];
        }
        
        DB::beginTransaction();
        
        try {
            $document = KnowledgeDocument::where('id', $documentId)
                ->where('company_id', $companyId)
                ->firstOrFail();
            
            // Create version before updating
            if ($userId) {
                $this->createVersion($document, $userId);
            }
            
            // Update fields
            $updateData = [];
            foreach (['title', 'content', 'excerpt', 'status', 'category_id', 'metadata'] as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }
            
            if (!empty($updateData)) {
                if (isset($updateData['content'])) {
                    $updateData['raw_content'] = $updateData['content'];
                    $updateData['content'] = $this->processContent($updateData['content']);
                }
                
                $document->update($updateData);
            }
            
            // Update tags
            if (isset($params['tags'])) {
                $tagIds = $this->syncTags($params['tags'], $companyId);
                $document->tags()->sync($tagIds);
            }
            
            // Clear cache
            $this->clearDocumentCache($document);
            
            DB::commit();
            
            return [
                'success' => true,
                'document' => $this->formatDocument($document->fresh()),
                'updated_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Knowledge update failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to update document',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get knowledge context for AI
     */
    public function getContextForAI(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $context = $params['context'] ?? '';
        $industry = $params['industry'] ?? null;
        $maxDocuments = min($params['max_documents'] ?? 5, $this->config['ai']['max_context_documents']);
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        $documents = [];
        
        // 1. Get company-specific documents
        $companyDocs = KnowledgeDocument::where('company_id', $companyId)
            ->where('status', 'published')
            ->whereHas('tags', function ($q) {
                $q->where('slug', 'ai-context');
            })
            ->orderBy('order', 'asc')
            ->limit(ceil($maxDocuments / 2))
            ->get();
        
        $documents = array_merge($documents, $companyDocs->toArray());
        
        // 2. Get industry-specific templates if available
        if ($industry && isset($this->industryTemplates[$industry])) {
            $industryDocs = $this->industryTemplates[$industry];
            $remaining = $maxDocuments - count($documents);
            $documents = array_merge($documents, array_slice($industryDocs, 0, $remaining));
        }
        
        // 3. Search for relevant documents based on context
        if (!empty($context) && count($documents) < $maxDocuments) {
            $searchResults = $this->searchKnowledge([
                'query' => $context,
                'company_id' => $companyId,
                'limit' => $maxDocuments - count($documents)
            ]);
            
            if (isset($searchResults['results'])) {
                foreach ($searchResults['results'] as $result) {
                    $documents[] = $result;
                }
            }
        }
        
        // Format for AI consumption
        $aiContext = [
            'company_context' => $this->getCompanyContext($companyId),
            'documents' => array_map(function ($doc) {
                return [
                    'id' => $doc['id'] ?? null,
                    'title' => $doc['title'] ?? '',
                    'content' => $this->truncateContent($doc['content'] ?? '', 500),
                    'category' => $doc['category']['name'] ?? 'General',
                    'tags' => array_column($doc['tags'] ?? [], 'name')
                ];
            }, $documents),
            'metadata' => [
                'total_documents' => count($documents),
                'context_length' => array_sum(array_map(function ($doc) {
                    return strlen($doc['content'] ?? '');
                }, $documents)),
                'generated_at' => now()->toIso8601String()
            ]
        ];
        
        return $aiContext;
    }
    
    /**
     * Get knowledge by category
     */
    public function getCategoryKnowledge(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $categorySlug = $params['category_slug'] ?? null;
        $includeSubcategories = $params['include_subcategories'] ?? true;
        
        if (!$companyId || !$categorySlug) {
            return ['error' => 'company_id and category_slug are required'];
        }
        
        $cacheKey = $this->getCacheKey('category_knowledge', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId, $categorySlug, $includeSubcategories) {
            $category = KnowledgeCategory::where('slug', $categorySlug)->first();
            
            if (!$category) {
                return ['error' => 'Category not found'];
            }
            
            $categoryIds = [$category->id];
            
            // Include subcategories if requested
            if ($includeSubcategories) {
                $subcategories = KnowledgeCategory::where('parent_id', $category->id)->pluck('id');
                $categoryIds = array_merge($categoryIds, $subcategories->toArray());
            }
            
            $documents = KnowledgeDocument::where('company_id', $companyId)
                ->whereIn('category_id', $categoryIds)
                ->where('status', 'published')
                ->with(['category', 'tags'])
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description
                ],
                'documents' => $documents->map(function ($doc) {
                    return $this->formatDocument($doc);
                }),
                'statistics' => [
                    'total_documents' => $documents->count(),
                    'total_views' => $documents->sum('view_count'),
                    'avg_helpfulness' => $this->calculateHelpfulness($documents)
                ],
                'metadata' => [
                    'include_subcategories' => $includeSubcategories,
                    'generated_at' => now()->toIso8601String()
                ]
            ];
        });
    }
    
    /**
     * Create document from template
     */
    public function createFromTemplate(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $templateId = $params['template_id'] ?? null;
        $industry = $params['industry'] ?? null;
        $customData = $params['custom_data'] ?? [];
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        DB::beginTransaction();
        
        try {
            // Get template
            $template = null;
            if ($templateId) {
                $template = KnowledgeDocument::where('id', $templateId)
                    ->where('company_id', null) // System templates
                    ->first();
            } elseif ($industry && isset($this->industryTemplates[$industry])) {
                // Use industry template
                $templates = $this->industryTemplates[$industry];
                if (!empty($templates)) {
                    $template = $templates[0]; // Use first template as example
                }
            }
            
            if (!$template) {
                return ['error' => 'Template not found'];
            }
            
            // Create new document from template
            $newDocument = new KnowledgeDocument([
                'company_id' => $companyId,
                'title' => $customData['title'] ?? $template['title'],
                'content' => $this->personalizeContent($template['content'], $companyId, $customData),
                'raw_content' => $template['content'],
                'excerpt' => $customData['excerpt'] ?? $template['excerpt'] ?? null,
                'category_id' => $customData['category_id'] ?? null,
                'status' => 'draft',
                'metadata' => array_merge($template['metadata'] ?? [], [
                    'created_from_template' => true,
                    'template_id' => $templateId,
                    'industry' => $industry
                ])
            ]);
            
            $newDocument->save();
            
            // Copy tags if available
            if (isset($template['tags'])) {
                $tagIds = $this->syncTags($template['tags'], $companyId);
                $newDocument->tags()->sync($tagIds);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'document' => $this->formatDocument($newDocument),
                'created_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create document from template', [
                'company_id' => $companyId,
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to create document',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get knowledge statistics
     */
    public function getStatistics(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $period = $params['period'] ?? '30days';
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        $cacheKey = $this->getCacheKey('statistics', $params);
        
        return Cache::remember($cacheKey, 600, function () use ($companyId, $period) {
            $startDate = $this->getPeriodStartDate($period);
            
            // Get document statistics
            $documentStats = KnowledgeDocument::where('company_id', $companyId)
                ->selectRaw('
                    COUNT(*) as total_documents,
                    SUM(view_count) as total_views,
                    SUM(helpful_count) as total_helpful,
                    SUM(not_helpful_count) as total_not_helpful,
                    AVG(view_count) as avg_views_per_doc
                ')
                ->first();
            
            // Get popular documents
            $popularDocs = KnowledgeDocument::where('company_id', $companyId)
                ->where('status', 'published')
                ->orderByDesc('view_count')
                ->limit(5)
                ->get(['id', 'title', 'view_count', 'helpful_count']);
            
            // Get recent activity
            $recentActivity = KnowledgeAnalytic::whereHas('document', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    event_type,
                    COUNT(*) as count,
                    DATE(created_at) as date
                ')
                ->groupBy('event_type', 'date')
                ->get();
            
            // Get category distribution
            $categoryDist = KnowledgeDocument::where('company_id', $companyId)
                ->where('status', 'published')
                ->join('knowledge_categories', 'knowledge_documents.category_id', '=', 'knowledge_categories.id')
                ->selectRaw('
                    knowledge_categories.name as category_name,
                    COUNT(*) as document_count
                ')
                ->groupBy('knowledge_categories.id', 'knowledge_categories.name')
                ->get();
            
            return [
                'overview' => [
                    'total_documents' => $documentStats->total_documents ?? 0,
                    'total_views' => $documentStats->total_views ?? 0,
                    'helpfulness_rate' => $this->calculateHelpfulnessRate(
                        $documentStats->total_helpful ?? 0,
                        $documentStats->total_not_helpful ?? 0
                    ),
                    'avg_views_per_document' => round($documentStats->avg_views_per_doc ?? 0, 2)
                ],
                'popular_documents' => $popularDocs->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'view_count' => $doc->view_count,
                        'helpfulness_rate' => $this->calculateHelpfulnessRate(
                            $doc->helpful_count,
                            $doc->not_helpful_count
                        )
                    ];
                }),
                'activity_timeline' => $recentActivity->groupBy('date')->map(function ($activities) {
                    return $activities->pluck('count', 'event_type');
                }),
                'category_distribution' => $categoryDist->pluck('document_count', 'category_name'),
                'metadata' => [
                    'period' => $period,
                    'start_date' => $startDate->toDateString(),
                    'generated_at' => now()->toIso8601String()
                ]
            ];
        });
    }
    
    /**
     * Initialize industry-specific templates
     */
    protected function initializeIndustryTemplates(): void
    {
        $this->industryTemplates = [
            'medical' => [
                [
                    'title' => 'Terminvereinbarung per Telefon',
                    'content' => '## Wie vereinbare ich einen Termin?\n\nSie können uns ganz einfach anrufen. Unser KI-Assistent nimmt Ihren Anruf entgegen und hilft Ihnen bei der Terminvereinbarung.\n\n### Was benötige ich?\n- Ihre Versichertenkartennummer\n- Gewünschter Behandlungsgrund\n- Bevorzugte Termine\n\n### Ablauf\n1. Rufen Sie unsere Praxisnummer an\n2. Der KI-Assistent begrüßt Sie\n3. Nennen Sie Ihren Terminwunsch\n4. Der Assistent prüft die Verfügbarkeit\n5. Sie erhalten eine Bestätigung per E-Mail',
                    'excerpt' => 'Erfahren Sie, wie Sie telefonisch einen Termin in unserer Praxis vereinbaren können.',
                    'tags' => ['termine', 'anleitung', 'telefon', 'ai-context'],
                    'metadata' => ['industry' => 'medical', 'type' => 'guide']
                ],
                [
                    'title' => 'Häufig gestellte Fragen (FAQ)',
                    'content' => '## Häufige Fragen\n\n### Kann ich auch außerhalb der Öffnungszeiten anrufen?\nJa! Unser KI-Assistent ist 24/7 für Sie erreichbar.\n\n### Was passiert bei einem Notfall?\nBei medizinischen Notfällen wählen Sie bitte die 112.\n\n### Kann ich Termine auch absagen?\nJa, nennen Sie einfach Ihren Namen und Termin.\n\n### Wie lange dauert ein Anruf?\nIn der Regel nur 2-3 Minuten.',
                    'excerpt' => 'Antworten auf häufig gestellte Fragen zu unserer Praxis.',
                    'tags' => ['faq', 'hilfe', 'ai-context'],
                    'metadata' => ['industry' => 'medical', 'type' => 'faq']
                ]
            ],
            'beauty' => [
                [
                    'title' => 'Unsere Behandlungen',
                    'content' => '## Unser Angebot\n\n### Gesichtsbehandlungen\n- Klassische Gesichtsbehandlung (60 Min)\n- Anti-Aging Behandlung (90 Min)\n- Akne-Behandlung (45 Min)\n\n### Körperbehandlungen\n- Ganzkörpermassage (60 Min)\n- Hot Stone Massage (75 Min)\n- Lymphdrainage (45 Min)\n\n### Beauty Services\n- Maniküre & Pediküre\n- Wimpernverlängerung\n- Permanent Make-up',
                    'excerpt' => 'Übersicht über alle unsere Beauty-Behandlungen und Services.',
                    'tags' => ['behandlungen', 'preise', 'services', 'ai-context'],
                    'metadata' => ['industry' => 'beauty', 'type' => 'services']
                ]
            ],
            'veterinary' => [
                [
                    'title' => 'Terminbuchung für Ihr Haustier',
                    'content' => '## So buchen Sie einen Termin\n\n### Was wir benötigen:\n- Name des Tieres\n- Tierart (Hund, Katze, etc.)\n- Grund des Besuchs\n- Ihre Kontaktdaten\n\n### Notfälle\nBei Notfällen sind wir unter der Notfallnummer erreichbar.\n\n### Impftermine\nBringen Sie bitte den Impfpass mit.',
                    'excerpt' => 'Informationen zur Terminbuchung für Ihr Haustier.',
                    'tags' => ['termine', 'haustiere', 'anleitung', 'ai-context'],
                    'metadata' => ['industry' => 'veterinary', 'type' => 'guide']
                ]
            ],
            'legal' => [
                [
                    'title' => 'Erstberatung vereinbaren',
                    'content' => '## Erstberatung\n\n### Ablauf\n1. Terminvereinbarung per Telefon\n2. Kurze Schilderung Ihres Anliegens\n3. Terminbestätigung per E-Mail\n4. Vorbereitung der Unterlagen\n\n### Kosten\nDie Erstberatung kostet pauschal 250€ (inkl. MwSt.)\n\n### Dauer\nPlanen Sie etwa 60 Minuten ein.',
                    'excerpt' => 'Informationen zur Vereinbarung einer rechtlichen Erstberatung.',
                    'tags' => ['beratung', 'termine', 'kosten', 'ai-context'],
                    'metadata' => ['industry' => 'legal', 'type' => 'consultation']
                ]
            ]
        ];
    }
    
    /**
     * Format document for response
     */
    protected function formatDocument($document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'slug' => $document->slug,
            'excerpt' => $document->excerpt,
            'content' => $document->content,
            'status' => $document->status,
            'category' => $document->category ? [
                'id' => $document->category->id,
                'name' => $document->category->name,
                'slug' => $document->category->slug
            ] : null,
            'tags' => $document->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }),
            'statistics' => [
                'view_count' => $document->view_count,
                'helpful_count' => $document->helpful_count,
                'not_helpful_count' => $document->not_helpful_count,
                'helpfulness_rate' => $this->calculateHelpfulnessRate(
                    $document->helpful_count,
                    $document->not_helpful_count
                )
            ],
            'metadata' => $document->metadata,
            'created_at' => $document->created_at->toIso8601String(),
            'updated_at' => $document->updated_at->toIso8601String()
        ];
    }
    
    /**
     * Process content (convert markdown to HTML, etc.)
     */
    protected function processContent(string $content): string
    {
        // For now, just return the content as-is
        // In production, you might want to:
        // - Convert markdown to HTML
        // - Sanitize HTML
        // - Add syntax highlighting for code blocks
        return $content;
    }
    
    /**
     * Create version before updating
     */
    protected function createVersion(KnowledgeDocument $document, int $userId): void
    {
        KnowledgeVersion::create([
            'document_id' => $document->id,
            'title' => $document->title,
            'content' => $document->raw_content ?? $document->content,
            'metadata' => $document->metadata,
            'version_number' => $document->versions()->count() + 1,
            'created_by' => $userId,
            'change_summary' => 'Document updated'
        ]);
    }
    
    /**
     * Sync tags
     */
    protected function syncTags(array $tags, int $companyId): array
    {
        $tagIds = [];
        
        foreach ($tags as $tagName) {
            $slug = Str::slug($tagName);
            $tag = KnowledgeTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $tagName]
            );
            $tagIds[] = $tag->id;
        }
        
        return $tagIds;
    }
    
    /**
     * Clear document cache
     */
    protected function clearDocumentCache(KnowledgeDocument $document): void
    {
        $patterns = [
            $this->config['cache']['prefix'] . ':company_knowledge:*',
            $this->config['cache']['prefix'] . ':category_knowledge:*',
            $this->config['cache']['prefix'] . ':search:*'
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }
    
    /**
     * Track search analytics
     */
    protected function trackSearch(string $query, int $resultCount, ?int $companyId): void
    {
        try {
            DB::table('knowledge_analytics')->insert([
                'document_id' => null,
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'event_type' => 'search',
                'event_data' => json_encode([
                    'query' => $query,
                    'result_count' => $resultCount,
                    'company_id' => $companyId
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track search analytics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Highlight search terms in text
     */
    protected function highlightSearchTerms(string $text, string $query): string
    {
        $terms = explode(' ', $query);
        foreach ($terms as $term) {
            if (strlen($term) >= 3) {
                $text = preg_replace(
                    '/(' . preg_quote($term, '/') . ')/i',
                    '<mark>$1</mark>',
                    $text
                );
            }
        }
        return $text;
    }
    
    /**
     * Get company context
     */
    protected function getCompanyContext(int $companyId): array
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return [];
        }
        
        return [
            'name' => $company->name,
            'industry' => $company->industry,
            'settings' => [
                'language' => $company->language ?? 'de',
                'timezone' => $company->timezone ?? 'Europe/Berlin'
            ]
        ];
    }
    
    /**
     * Truncate content to specified length
     */
    protected function truncateContent(string $content, int $maxLength): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        return substr($content, 0, $maxLength) . '...';
    }
    
    /**
     * Calculate helpfulness from documents collection
     */
    protected function calculateHelpfulness($documents): float
    {
        $totalHelpful = $documents->sum('helpful_count');
        $totalNotHelpful = $documents->sum('not_helpful_count');
        
        return $this->calculateHelpfulnessRate($totalHelpful, $totalNotHelpful);
    }
    
    /**
     * Calculate helpfulness rate
     */
    protected function calculateHelpfulnessRate(int $helpful, int $notHelpful): float
    {
        $total = $helpful + $notHelpful;
        if ($total === 0) {
            return 0;
        }
        
        return round(($helpful / $total) * 100, 2);
    }
    
    /**
     * Get period start date
     */
    protected function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        switch ($period) {
            case '7days':
                return now()->subDays(7);
            case '30days':
                return now()->subDays(30);
            case '90days':
                return now()->subDays(90);
            case 'year':
                return now()->subYear();
            default:
                return now()->subDays(30);
        }
    }
    
    /**
     * Personalize content with company data
     */
    protected function personalizeContent(string $content, int $companyId, array $customData): string
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return $content;
        }
        
        // Replace placeholders
        $replacements = [
            '{{company_name}}' => $company->name,
            '{{company_email}}' => $company->email ?? '',
            '{{company_phone}}' => $company->phone ?? '',
            '{{company_address}}' => $company->address ?? '',
        ];
        
        // Add custom data replacements
        foreach ($customData as $key => $value) {
            if (is_string($value)) {
                $replacements['{{' . $key . '}}'] = $value;
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            ksort($params); // Ensure consistent key order
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Get document count for a company
     */
    public function getDocumentCount(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required', 'count' => 0];
        }
        
        try {
            $count = KnowledgeDocument::where('company_id', $companyId)
                ->where('status', 'published')
                ->count();
                
            return [
                'count' => $count,
                'company_id' => $companyId
            ];
        } catch (\Exception $e) {
            Log::error('MCP Knowledge getDocumentCount error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to get document count', 'count' => 0];
        }
    }
}