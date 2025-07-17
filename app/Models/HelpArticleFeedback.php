<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleFeedback extends Model
{
    protected $table = 'help_article_feedback';

    protected $fillable = [
        'category',
        'topic',
        'helpful',
        'comment',
        'ip_address',
        'portal_user_id',
        'session_id'
    ];

    protected $casts = [
        'helpful' => 'boolean'
    ];

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    /**
     * Get feedback statistics for an article
     */
    public static function getFeedbackStats($category, $topic)
    {
        $total = self::where('category', $category)
            ->where('topic', $topic)
            ->count();

        $helpful = self::where('category', $category)
            ->where('topic', $topic)
            ->where('helpful', true)
            ->count();

        $notHelpful = self::where('category', $category)
            ->where('topic', $topic)
            ->where('helpful', false)
            ->count();

        return [
            'total' => $total,
            'helpful' => $helpful,
            'not_helpful' => $notHelpful,
            'helpful_percentage' => $total > 0 ? ($helpful / $total) * 100 : 0
        ];
    }

    /**
     * Get articles with lowest helpfulness scores
     */
    public static function getLeastHelpfulArticles($limit = 10, $minFeedback = 5)
    {
        return self::select('category', 'topic')
            ->selectRaw('COUNT(*) as feedback_count')
            ->selectRaw('SUM(CASE WHEN helpful = 1 THEN 1 ELSE 0 END) as helpful_count')
            ->selectRaw('(SUM(CASE WHEN helpful = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as helpful_percentage')
            ->groupBy('category', 'topic')
            ->having('feedback_count', '>=', $minFeedback)
            ->orderBy('helpful_percentage')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent comments
     */
    public static function getRecentComments($limit = 20)
    {
        return self::whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
