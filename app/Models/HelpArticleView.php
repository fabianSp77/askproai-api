<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleView extends Model
{
    protected $fillable = [
        'category',
        'topic',
        'title',
        'ip_address',
        'user_agent',
        'portal_user_id',
        'session_id',
        'referrer'
    ];

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    /**
     * Get view count for a specific article
     */
    public static function getViewCount($category, $topic): int
    {
        return self::where('category', $category)
            ->where('topic', $topic)
            ->count();
    }

    /**
     * Get unique view count for a specific article
     */
    public static function getUniqueViewCount($category, $topic): int
    {
        return self::where('category', $category)
            ->where('topic', $topic)
            ->distinct('session_id')
            ->count('session_id');
    }

    /**
     * Get popular articles
     */
    public static function getPopularArticles($limit = 10, $days = 30)
    {
        return self::select('category', 'topic', 'title')
            ->selectRaw('COUNT(*) as view_count')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_view_count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('category', 'topic', 'title')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get views by time period
     */
    public static function getViewsByPeriod($category = null, $topic = null, $days = 30)
    {
        $query = self::query();

        if ($category) {
            $query->where('category', $category);
        }

        if ($topic) {
            $query->where('topic', $topic);
        }

        return $query->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
