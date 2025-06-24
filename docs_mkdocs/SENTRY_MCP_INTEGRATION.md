# Sentry MCP Integration for Claude Code

This document explains how to use the Sentry MCP (Model Context Protocol) Server integration with Claude Code to debug and solve errors more efficiently.

## Overview

The Sentry MCP Server allows Claude Code to directly access error data from Sentry, making it easier to:
- View recent errors and exceptions
- Analyze stack traces
- Understand error context and breadcrumbs
- Search for specific error patterns
- Monitor application performance

## Setup

### 1. Configure Sentry Credentials

Add the following to your `.env` file:

```env
# Basic Sentry Configuration
SENTRY_LARAVEL_DSN=https://YOUR_DSN@sentry.io/PROJECT_ID
SENTRY_ENVIRONMENT=production

# MCP Server Configuration
SENTRY_ORGANIZATION=your-org-name
SENTRY_PROJECT=your-project-name
SENTRY_AUTH_TOKEN=your-sentry-auth-token
```

### 2. Generate Sentry Auth Token

1. Go to Sentry.io → Settings → Account → API → Auth Tokens
2. Create a new token with the following scopes:
   - `project:read`
   - `event:read`
   - `org:read`

### 3. Test the Integration

```bash
# Test MCP Server info endpoint
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  https://your-domain.com/api/mcp/sentry/info

# List recent issues
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  https://your-domain.com/api/mcp/sentry/issues
```

## Available MCP Endpoints

### 1. Server Information
```
GET /api/mcp/sentry/info
```
Returns MCP server capabilities and configuration.

### 2. List Issues
```
GET /api/mcp/sentry/issues
```
Query parameters:
- `limit` (int): Number of issues to return (1-100)
- `sort` (string): Sort order (date, priority, freq, new)
- `query` (string): Search query

### 3. Get Issue Details
```
GET /api/mcp/sentry/issues/{issueId}
```
Returns detailed information about a specific issue.

### 4. Get Latest Event
```
GET /api/mcp/sentry/issues/{issueId}/latest-event
```
Returns the latest event for an issue, including full stack trace.

### 5. Search Issues
```
POST /api/mcp/sentry/issues/search
```
Body:
```json
{
  "query": "error message or exception type",
  "limit": 25
}
```

### 6. Performance Data
```
GET /api/mcp/sentry/performance
```
Returns performance metrics for the application.

### 7. Clear Cache
```
POST /api/mcp/sentry/cache/clear
```
Clears the MCP cache to fetch fresh data.

## Using with Claude Code

When debugging errors with Claude Code, you can ask questions like:

- "What are the most recent errors in production?"
- "Show me the stack trace for error ID ABC123"
- "Search for all database connection errors"
- "What errors are happening in the booking flow?"
- "Show me performance issues in the last 24 hours"

Claude Code will use the MCP Server to fetch relevant data from Sentry and help you understand and fix the issues.

## Configuration Options

In `config/mcp-sentry.php`:

```php
'filters' => [
    // Only show errors from last N days
    'days_back' => env('MCP_SENTRY_DAYS_BACK', 7),
    
    // Minimum error level to show
    'min_level' => env('MCP_SENTRY_MIN_LEVEL', 'warning'),
    
    // Exclude certain error types
    'exclude_types' => [
        'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException',
    ],
],
```

## Security Considerations

1. The MCP endpoints are protected by Sanctum authentication
2. Sentry auth tokens should never be exposed to the frontend
3. Consider limiting MCP access to admin users only
4. Regularly rotate Sentry auth tokens

## Troubleshooting

### "Failed to fetch issues from Sentry"
- Check that `SENTRY_AUTH_TOKEN` is set correctly
- Verify the token has the required scopes
- Check `storage/logs/laravel.log` for detailed error messages

### "Cache not clearing"
- Ensure Redis is running: `redis-cli ping`
- Check cache configuration in `config/cache.php`

### "No issues returned"
- Verify `SENTRY_ORGANIZATION` and `SENTRY_PROJECT` match your Sentry setup
- Check the date filter in `MCP_SENTRY_DAYS_BACK`
- Try lowering `MCP_SENTRY_MIN_LEVEL` to include more error types

## Example Usage in Code

```php
// In your controller or service
use App\Services\MCP\SentryMCPServer;

public function debugError(SentryMCPServer $sentry)
{
    // List recent issues
    $issues = $sentry->listIssues(['limit' => 10]);
    
    // Get specific issue details
    $issue = $sentry->getIssue('1234567890');
    
    // Get stack trace
    $event = $sentry->getLatestEvent('1234567890');
    
    // Search for specific errors
    $dbErrors = $sentry->searchIssues('database connection');
}
```

## Best Practices

1. **Use descriptive error messages** in your code to make searching easier
2. **Add context** to errors using Sentry's context features
3. **Tag errors** appropriately (e.g., by feature, severity, user impact)
4. **Monitor performance** alongside errors for complete visibility
5. **Set up alerts** in Sentry for critical errors

## Integration with Filament Admin

A Sentry dashboard widget is planned for the Filament admin panel to show:
- Recent errors summary
- Error trends graph
- Quick links to Sentry issues
- Performance metrics

This will provide a unified view of application health within the admin interface.