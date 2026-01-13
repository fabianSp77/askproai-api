<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * KnowledgeBaseArticle Model
 *
 * ServiceNow-style knowledge base articles for self-service and agent reference.
 * Supports rich text content, categorization, and analytics tracking.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $content
 * @property int|null $category_id
 * @property array|null $keywords
 * @property string $article_type how_to|faq|reference|troubleshooting|policy
 * @property bool $is_published
 * @property bool $is_featured
 * @property bool $is_internal
 * @property int|null $author_id
 * @property int|null $last_reviewed_by
 * @property \Illuminate\Support\Carbon|null $last_reviewed_at
 * @property int $view_count
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class KnowledgeBaseArticle extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * Article type enumeration
     */
    public const TYPE_HOW_TO = 'how_to';
    public const TYPE_FAQ = 'faq';
    public const TYPE_REFERENCE = 'reference';
    public const TYPE_TROUBLESHOOTING = 'troubleshooting';
    public const TYPE_POLICY = 'policy';

    public const ARTICLE_TYPES = [
        self::TYPE_HOW_TO,
        self::TYPE_FAQ,
        self::TYPE_REFERENCE,
        self::TYPE_TROUBLESHOOTING,
        self::TYPE_POLICY,
    ];

    /**
     * Human-readable article type labels
     */
    public const ARTICLE_TYPE_LABELS = [
        self::TYPE_HOW_TO => 'Anleitung',
        self::TYPE_FAQ => 'FAQ',
        self::TYPE_REFERENCE => 'Referenz',
        self::TYPE_TROUBLESHOOTING => 'Fehlerbehebung',
        self::TYPE_POLICY => 'Richtlinie',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'summary',
        'content',
        'category_id',
        'keywords',
        'article_type',
        'is_published',
        'is_featured',
        'is_internal',
        'author_id',
        'last_reviewed_by',
        'last_reviewed_at',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'keywords' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_internal' => 'boolean',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'sort_order' => 'integer',
        'last_reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the article.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category this article belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCaseCategory::class, 'category_id');
    }

    /**
     * Get the author (staff member) who created the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'author_id');
    }

    /**
     * Get the staff member who last reviewed the article.
     */
    public function lastReviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'last_reviewed_by');
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include featured articles.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include public articles (not internal).
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope a query to order by view count (most popular first).
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('view_count');
    }

    /**
     * Scope a query to filter by article type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('article_type', $type);
    }

    /**
     * Scope a query to search by keyword.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('summary', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhereJsonContains('keywords', $search);
        });
    }

    /**
     * Increment view count.
     */
    public function recordView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Record helpful feedback.
     */
    public function recordHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Record not helpful feedback.
     */
    public function recordNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Get the helpfulness percentage.
     */
    public function getHelpfulnessPercentageAttribute(): ?float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return null;
        }
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Get human-readable article type label.
     */
    public function getTypeLabel(): string
    {
        return self::ARTICLE_TYPE_LABELS[$this->article_type] ?? $this->article_type;
    }

    /**
     * Boot the model - Auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-generate slug from title if not provided
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }

            // Validate article_type
            if (!in_array($model->article_type, self::ARTICLE_TYPES)) {
                throw new \InvalidArgumentException("Invalid article type: {$model->article_type}");
            }
        });
    }
}
