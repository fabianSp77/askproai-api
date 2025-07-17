# Help Center Analytics Implementation

## Overview
This implementation adds comprehensive analytics and tracking capabilities to the help center, including article views, search queries, and user feedback.

## Features Implemented

### 1. Article View Tracking
- Tracks every article view with metadata (IP, user agent, session ID, etc.)
- Shows view count on each article
- Identifies popular articles based on views
- Tracks unique visitors using session IDs

### 2. Search Analytics
- Records all search queries with result counts
- Tracks which search results users click on
- Identifies queries with no results (content gaps)
- Calculates search conversion rates

### 3. Article Feedback System
- "Was this helpful?" functionality on each article
- Optional comment field for detailed feedback
- Prevents duplicate feedback from same session
- Shows helpfulness percentage on articles

### 4. Analytics Dashboard
- Located at `/hilfe/analytics` (admin only)
- Key metrics: total views, unique visitors, searches, conversion rate
- Visual charts for trends over time
- Lists of popular articles, search queries, and problem areas
- Recent user comments for qualitative feedback

## Database Tables Created

1. **help_article_views**
   - Stores individual page views
   - Links to portal users when logged in
   - Indexes for performance

2. **help_search_queries**
   - Records search queries and results
   - Tracks clicked results
   - Used for search optimization

3. **help_article_feedback**
   - Stores helpfulness votes
   - Optional comments from users
   - One vote per session per article

## Implementation Files

### Backend
- `/app/Http/Controllers/HelpCenterController.php` - Updated with analytics methods
- `/app/Models/HelpArticleView.php` - Model for article views
- `/app/Models/HelpSearchQuery.php` - Model for search queries
- `/app/Models/HelpArticleFeedback.php` - Model for feedback
- `/routes/help-center.php` - Added API endpoints

### Frontend
- `/resources/views/help-center/dashboard.blade.php` - Analytics dashboard
- `/resources/views/help-center/article.blade.php` - Updated with feedback UI
- `/resources/views/help-center/search.blade.php` - Updated with click tracking
- `/resources/js/help-center-analytics.js` - JavaScript for interactions

### Database Migrations
- `2025_07_10_181117_create_help_article_views_table.php`
- `2025_07_10_181136_create_help_search_queries_table.php`
- `2025_07_10_181154_create_help_article_feedback_table.php`

## API Endpoints

- `POST /hilfe/api/track-search-click` - Track search result clicks
- `POST /hilfe/api/feedback` - Submit article feedback
- `GET /hilfe/analytics` - View analytics dashboard (admin only)

## Usage

### For Administrators
1. Access the analytics dashboard at `/hilfe/analytics`
2. Select time period (7, 30, or 90 days)
3. Review metrics and identify areas for improvement
4. Use feedback comments to improve content

### For Content Managers
1. Monitor popular articles to understand user needs
2. Review "no results" searches to identify missing content
3. Check articles with low helpfulness scores
4. Read user comments for specific improvement suggestions

## Future Enhancements
- Export analytics data to CSV
- Email alerts for low-performing articles
- A/B testing for article versions
- Integration with customer support tickets
- Heatmap visualization of user interactions