# 🔄 Auto-Update Mechanism für CLAUDE.md

## 🎯 Übersicht
Automatisches System zur Aktualisierung dynamischer Inhalte in CLAUDE.md ohne manuelle Eingriffe.

## 📊 Dynamische Inhalte identifiziert

### Muss automatisch aktualisiert werden:
1. **Aktuelle Blocker** - Aus GitHub Issues
2. **System Status** - Aus Health Checks
3. **API Versions** - Aus Composer/NPM
4. **Performance Metrics** - Aus Monitoring
5. **Deployment Status** - Aus CI/CD
6. **Command Index** - Aus Codebase
7. **Error Patterns** - Aus Logs

## 🏗️ Implementierungsplan

### 1. **Laravel Command für Updates**
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\GitHubService;
use App\Services\SystemHealthService;

class UpdateClaudeMd extends Command
{
    protected $signature = 'claude:update-md {--section=all}';
    protected $description = 'Update dynamic sections in CLAUDE.md';

    public function handle()
    {
        $this->info('Updating CLAUDE.md dynamic content...');
        
        $updates = [
            'blockers' => $this->updateBlockers(),
            'status' => $this->updateSystemStatus(),
            'metrics' => $this->updateMetrics(),
            'commands' => $this->updateCommandIndex(),
        ];
        
        $this->updateMarkdownFile($updates);
        
        $this->info('✅ CLAUDE.md updated successfully!');
    }
    
    private function updateBlockers()
    {
        // GitHub API Integration
        $issues = app(GitHubService::class)->getOpenIssues([
            'labels' => ['blocker', 'critical'],
            'state' => 'open'
        ]);
        
        return $this->formatBlockers($issues);
    }
    
    private function updateSystemStatus()
    {
        $health = app(SystemHealthService::class)->check();
        
        return view('claude.status', compact('health'))->render();
    }
    
    private function updateMarkdownFile($updates)
    {
        $content = File::get(base_path('CLAUDE.md'));
        
        foreach ($updates as $section => $newContent) {
            $pattern = "/<!-- DYNAMIC:$section:START -->.*<!-- DYNAMIC:$section:END -->/s";
            $replacement = "<!-- DYNAMIC:$section:START -->\n$newContent\n<!-- DYNAMIC:$section:END -->";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        File::put(base_path('CLAUDE.md'), $content);
    }
}
```

### 2. **Markdown Template mit Platzhaltern**
```markdown
# CLAUDE.md

<!-- DYNAMIC:status:START -->
<!-- Auto-generated content - do not edit manually -->
## 📊 System Status
Last Update: 2025-07-03 10:30:00

✅ All Systems Operational
<!-- DYNAMIC:status:END -->

<!-- DYNAMIC:blockers:START -->
## 🚨 Current Blockers
<!-- Auto-generated from GitHub Issues -->
<!-- DYNAMIC:blockers:END -->

<!-- DYNAMIC:metrics:START -->
## 📈 Performance Metrics
<!-- Auto-generated from monitoring -->
<!-- DYNAMIC:metrics:END -->
```

### 3. **GitHub Actions Workflow**
```yaml
name: Update CLAUDE.md

on:
  schedule:
    - cron: '*/30 * * * *'  # Every 30 minutes
  push:
    branches: [main]
  issues:
    types: [opened, closed, reopened, labeled]
  workflow_dispatch:

jobs:
  update-claude-md:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
        with:
          token: ${{ secrets.CLAUDE_UPDATE_TOKEN }}
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Dependencies
        run: composer install --no-dev
      
      - name: Update CLAUDE.md
        run: |
          php artisan claude:update-md
          php artisan claude:validate-md
      
      - name: Commit Changes
        run: |
          git config --local user.email "claude-bot@askproai.de"
          git config --local user.name "Claude Update Bot"
          git add CLAUDE.md
          git diff --staged --quiet || git commit -m "🔄 Auto-update CLAUDE.md [skip ci]"
          git push
```

### 4. **Blade Templates für Sections**
```php
// resources/views/claude/blockers.blade.php
@if($blockers->isEmpty())
### ✅ Keine aktuellen Blocker!
@else
### 🚨 Aktuelle Blocker ({{ $blockers->count() }})

@foreach($blockers as $blocker)
{{ $loop->iteration }}. **{{ $blocker->title }}** [#{{ $blocker->number }}]({{ $blocker->url }})
   - Status: {{ $blocker->state }}
   - Assignee: {{ $blocker->assignee ?? 'Nicht zugewiesen' }}
   - Labels: {{ implode(', ', $blocker->labels) }}
   - Erstellt: {{ $blocker->created_at->diffForHumans() }}
@endforeach
@endif

_Letzte Aktualisierung: {{ now()->format('Y-m-d H:i:s') }}_
```

### 5. **Service für GitHub Integration**
```php
<?php

namespace App\Services;

use Github\Client;
use Illuminate\Support\Collection;

class GitHubService
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->client->authenticate(
            config('services.github.token'), 
            null, 
            Client::AUTH_ACCESS_TOKEN
        );
    }
    
    public function getOpenIssues(array $filters = []): Collection
    {
        $issues = $this->client->api('issue')->all(
            'askproai',
            'api-gateway',
            $filters
        );
        
        return collect($issues)->map(function ($issue) {
            return (object) [
                'number' => $issue['number'],
                'title' => $issue['title'],
                'state' => $issue['state'],
                'url' => $issue['html_url'],
                'labels' => collect($issue['labels'])->pluck('name'),
                'assignee' => $issue['assignee']['login'] ?? null,
                'created_at' => now()->parse($issue['created_at']),
            ];
        });
    }
}
```

### 6. **Cron Job für regelmäßige Updates**
```bash
# In crontab
*/15 * * * * cd /var/www/api-gateway && php artisan claude:update-md >> storage/logs/claude-updates.log 2>&1
```

### 7. **Webhook für Echtzeit-Updates**
```php
// routes/api.php
Route::post('/webhooks/github', [ClaudeUpdateController::class, 'handleGitHubWebhook']);

// App\Http\Controllers\ClaudeUpdateController.php
public function handleGitHubWebhook(Request $request)
{
    $event = $request->header('X-GitHub-Event');
    
    if (in_array($event, ['issues', 'pull_request'])) {
        dispatch(new UpdateClaudeMdJob('blockers'));
    }
    
    return response()->json(['status' => 'accepted']);
}
```

### 8. **Command Index Generator**
```php
private function updateCommandIndex()
{
    $commands = [];
    
    // Artisan Commands
    $artisanCommands = Artisan::all();
    foreach ($artisanCommands as $name => $command) {
        if (str_starts_with($name, 'askproai:') || str_starts_with($name, 'retell:')) {
            $commands['artisan'][] = [
                'name' => $name,
                'description' => $command->getDescription(),
                'usage' => $command->getSynopsis(),
            ];
        }
    }
    
    // PHP Scripts
    $scripts = glob(base_path('*.php'));
    foreach ($scripts as $script) {
        if (str_contains($script, 'retell') || str_contains($script, 'check')) {
            $commands['scripts'][] = [
                'name' => basename($script),
                'path' => $script,
            ];
        }
    }
    
    return view('claude.commands', compact('commands'))->render();
}
```

### 9. **Monitoring Dashboard Integration**
```javascript
// resources/js/claude-monitor.js
class ClaudeMonitor {
    constructor() {
        this.checkInterval = 60000; // 1 minute
        this.init();
    }
    
    init() {
        this.checkForUpdates();
        setInterval(() => this.checkForUpdates(), this.checkInterval);
    }
    
    async checkForUpdates() {
        const response = await fetch('/api/claude/status');
        const data = await response.json();
        
        if (data.needsUpdate) {
            this.notifyUser('CLAUDE.md needs update!');
            this.triggerUpdate();
        }
    }
    
    async triggerUpdate() {
        await fetch('/api/claude/update', { method: 'POST' });
    }
}
```

### 10. **Validation & Quality Checks**
```php
// app/Console/Commands/ValidateClaudeMd.php
class ValidateClaudeMd extends Command
{
    protected $signature = 'claude:validate-md';
    
    public function handle()
    {
        $content = File::get(base_path('CLAUDE.md'));
        
        // Check for broken links
        $this->checkLinks($content);
        
        // Validate dynamic sections
        $this->validateDynamicSections($content);
        
        // Check formatting
        $this->checkFormatting($content);
        
        // Verify all commands exist
        $this->verifyCommands($content);
    }
}
```

## 🎯 Implementierungs-Reihenfolge

### Phase 1: Basic Auto-Update (1 Tag)
1. Laravel Command erstellen
2. Dynamic Section Markers hinzufügen
3. Cron Job einrichten

### Phase 2: GitHub Integration (1-2 Tage)
1. GitHub Service implementieren
2. Issue-basierte Updates
3. Webhook Handler

### Phase 3: Advanced Features (2-3 Tage)
1. Real-time Updates via WebSocket
2. Monitoring Dashboard
3. Validation & QA

### Phase 4: Optimization (1 Tag)
1. Caching Strategie
2. Performance Tuning
3. Error Handling

## 📋 Configuration

```env
# .env additions
CLAUDE_UPDATE_ENABLED=true
CLAUDE_UPDATE_INTERVAL=30
GITHUB_TOKEN=your-token-here
CLAUDE_WEBHOOK_SECRET=your-secret
```

```php
// config/claude.php
return [
    'auto_update' => env('CLAUDE_UPDATE_ENABLED', true),
    'update_interval' => env('CLAUDE_UPDATE_INTERVAL', 30),
    'sections' => [
        'blockers' => true,
        'status' => true,
        'metrics' => true,
        'commands' => true,
    ],
    'github' => [
        'owner' => 'askproai',
        'repo' => 'api-gateway',
        'token' => env('GITHUB_TOKEN'),
    ],
];
```

## 🚀 Quick Start

```bash
# 1. Add configuration
php artisan vendor:publish --tag=claude-config

# 2. Run initial update
php artisan claude:update-md

# 3. Setup cron job
crontab -e
# Add: */15 * * * * cd /path/to/project && php artisan claude:update-md

# 4. Verify
php artisan claude:validate-md
```

## 📊 Expected Benefits

1. **Immer aktuelle Blocker** ohne manuelles Update
2. **Real-time System Status** direkt in der Doku
3. **Automatischer Command Index** bei neuen Commands
4. **Konsistente Formatierung** durch Templates
5. **Weniger Maintenance** durch Automation
6. **Bessere Entwickler-Experience** durch aktuelle Infos

Dieses System stellt sicher, dass CLAUDE.md immer auf dem neuesten Stand ist!