<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpSearchQuery extends Model
{
    protected $fillable = [
        'query',
        'results_count',
        'clicked_result',
        'ip_address',
        'portal_user_id',
        'session_id'
    ];

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    /**
     * Get popular search queries
     */
    public static function getPopularQueries($limit = 20, $days = 30)
    {
        return self::select('query')
            ->selectRaw('COUNT(*) as search_count')
            ->selectRaw('AVG(results_count) as avg_results')
            ->selectRaw('COUNT(clicked_result) as click_count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('query')
            ->orderBy('search_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get queries with no results
     */
    public static function getNoResultQueries($limit = 20, $days = 30)
    {
        return self::select('query')
            ->selectRaw('COUNT(*) as search_count')
            ->where('results_count', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('query')
            ->orderBy('search_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get search trends
     */
    public static function getSearchTrends($days = 30)
    {
        return self::selectRaw('DATE(created_at) as date, COUNT(*) as searches')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get conversion rate (searches that led to clicks)
     */
    public static function getConversionRate($days = 30): float
    {
        $total = self::where('created_at', '>=', now()->subDays($days))->count();
        $withClicks = self::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('clicked_result')
            ->count();

        return $total > 0 ? ($withClicks / $total) * 100 : 0;
    }
}
