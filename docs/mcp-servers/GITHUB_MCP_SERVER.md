# GitHub MCP Server Documentation

## Overview

The GitHub MCP Server provides integration with GitHub's API, allowing you to perform repository management, issue tracking, pull request operations, and code access directly from within the AskProAI system.

## Installation Status

✅ **INSTALLED** - The GitHub MCP Server has been successfully installed and configured.

## Configuration

### 1. Environment Variables

Add the following to your `.env` file:

```bash
# GitHub MCP Server
MCP_GITHUB_ENABLED=true
GITHUB_TOKEN=your_github_personal_access_token_here
```

### 2. Generate GitHub Token

1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Select the following scopes:
   - `repo` - Full control of private repositories
   - `read:org` - Read org and team membership
   - `write:issues` - Write access to issues

## Available Tools

### 1. Repository Operations

#### search_repositories
Search for GitHub repositories.
```php
$result = $github->executeTool('search_repositories', [
    'query' => 'laravel authentication',
    'sort' => 'stars', // stars, forks, updated
    'per_page' => 10
]);
```

#### get_repository
Get details about a specific repository.
```php
$result = $github->executeTool('get_repository', [
    'owner' => 'laravel',
    'repo' => 'framework'
]);
```

### 2. Issue Management

#### list_issues
List issues for a repository.
```php
$result = $github->executeTool('list_issues', [
    'owner' => 'your-org',
    'repo' => 'your-repo',
    'state' => 'open', // open, closed, all
    'labels' => 'bug,enhancement' // optional
]);
```

#### create_issue
Create a new issue.
```php
$result = $github->executeTool('create_issue', [
    'owner' => 'your-org',
    'repo' => 'your-repo',
    'title' => 'Bug: Something is broken',
    'body' => 'Detailed description...',
    'labels' => ['bug', 'priority-high']
]);
```

### 3. Pull Requests

#### list_pull_requests
List pull requests for a repository.
```php
$result = $github->executeTool('list_pull_requests', [
    'owner' => 'your-org',
    'repo' => 'your-repo',
    'state' => 'open' // open, closed, all
]);
```

### 4. Code Access

#### get_file_contents
Get contents of a file in a repository.
```php
$result = $github->executeTool('get_file_contents', [
    'owner' => 'laravel',
    'repo' => 'framework',
    'path' => 'composer.json',
    'ref' => 'main' // branch, tag, or commit
]);
```

#### list_branches
List all branches in a repository.
```php
$result = $github->executeTool('list_branches', [
    'owner' => 'your-org',
    'repo' => 'your-repo'
]);
```

#### get_commit
Get details about a specific commit.
```php
$result = $github->executeTool('get_commit', [
    'owner' => 'your-org',
    'repo' => 'your-repo',
    'ref' => 'abc123...' // commit SHA
]);
```

## Usage Examples

### Example 1: Search and Analyze Popular Laravel Packages

```php
use App\Services\MCP\GitHubMCPServer;

$github = app(GitHubMCPServer::class);

// Search for popular Laravel packages
$searchResult = $github->executeTool('search_repositories', [
    'query' => 'laravel package',
    'sort' => 'stars',
    'per_page' => 5
]);

if ($searchResult->isSuccess()) {
    $repos = $searchResult->getData()['items'];
    
    foreach ($repos as $repo) {
        echo "Repository: {$repo['full_name']}\n";
        echo "Stars: {$repo['stargazers_count']}\n";
        echo "Description: {$repo['description']}\n\n";
        
        // Get more details about the repository
        $detailsResult = $github->executeTool('get_repository', [
            'owner' => $repo['owner']['login'],
            'repo' => $repo['name']
        ]);
        
        if ($detailsResult->isSuccess()) {
            $details = $detailsResult->getData();
            echo "Language: {$details['language']}\n";
            echo "Open Issues: {$details['open_issues_count']}\n";
            echo "Last Updated: {$details['updated_at']}\n\n";
        }
    }
}
```

### Example 2: Create Issue from Customer Feedback

```php
// When receiving customer feedback, automatically create GitHub issue
$feedback = "The appointment booking flow is confusing on mobile devices";

$issueResult = $github->executeTool('create_issue', [
    'owner' => 'askproai',
    'repo' => 'api-gateway',
    'title' => 'UX: Mobile booking flow needs improvement',
    'body' => "Customer Feedback:\n\n{$feedback}\n\n---\nReported via: AskProAI Feedback System\nDate: " . now(),
    'labels' => ['enhancement', 'ux', 'mobile']
]);

if ($issueResult->isSuccess()) {
    $issue = $issueResult->getData();
    echo "Issue created: #{$issue['number']} - {$issue['title']}\n";
    echo "URL: {$issue['html_url']}\n";
}
```

### Example 3: Monitor Repository Activity

```php
// Check recent pull requests and issues
$owner = 'askproai';
$repo = 'api-gateway';

// Get open pull requests
$prsResult = $github->executeTool('list_pull_requests', [
    'owner' => $owner,
    'repo' => $repo,
    'state' => 'open'
]);

// Get open issues
$issuesResult = $github->executeTool('list_issues', [
    'owner' => $owner,
    'repo' => $repo,
    'state' => 'open',
    'labels' => 'bug'
]);

echo "Repository Status:\n";
echo "Open PRs: " . count($prsResult->getData()) . "\n";
echo "Open Bug Issues: " . count($issuesResult->getData()) . "\n";
```

## Integration with Other MCP Servers

The GitHub MCP Server can be combined with other MCP servers for powerful workflows:

### Example: Automatic Issue Creation on Error

```php
use App\Services\MCP\SentryMCPServer;
use App\Services\MCP\GitHubMCPServer;

// When Sentry detects a critical error
$sentry = app(SentryMCPServer::class);
$github = app(GitHubMCPServer::class);

$criticalErrors = $sentry->executeTool('get_critical_errors', [
    'period' => '1h'
]);

foreach ($criticalErrors->getData() as $error) {
    // Create GitHub issue for critical error
    $github->executeTool('create_issue', [
        'owner' => 'askproai',
        'repo' => 'api-gateway',
        'title' => "Critical Error: {$error['title']}",
        'body' => "Error detected by Sentry:\n\n" . 
                  "**Message**: {$error['message']}\n" .
                  "**Count**: {$error['count']} occurrences\n" .
                  "**First Seen**: {$error['first_seen']}\n" .
                  "**Sentry Link**: {$error['permalink']}\n",
        'labels' => ['bug', 'critical', 'sentry']
    ]);
}
```

## Testing

Run the test script to verify the GitHub MCP Server is working:

```bash
php test-github-mcp.php
```

## Troubleshooting

### Token Not Configured
If you see "GitHub token not configured", ensure:
1. You've added `GITHUB_TOKEN` to your `.env` file
2. The token has the correct permissions
3. Run `php artisan config:clear` to clear cached config

### API Rate Limits
GitHub has rate limits:
- Authenticated requests: 5,000 per hour
- Search API: 30 requests per minute

The MCP server handles rate limiting automatically and will retry with backoff.

### Connection Issues
If the external Node.js server fails to start:
1. Check Node.js is installed: `node --version`
2. Install dependencies: `cd mcp-external/github-mcp && npm install`
3. Check logs: `tail -f storage/logs/mcp-external.log`

## Security Considerations

1. **Token Security**: Never commit your GitHub token to version control
2. **Permissions**: Only grant the minimum required permissions
3. **Audit**: Regularly review GitHub token usage and audit logs
4. **Rotation**: Rotate tokens periodically

## Future Enhancements

- Webhook support for real-time GitHub events
- Automated PR review integration
- GitHub Actions workflow management
- Repository statistics and analytics
- Code search functionality
- Team and organization management