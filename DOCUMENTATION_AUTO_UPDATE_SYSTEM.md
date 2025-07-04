# 📚 Automatisches Dokumentations-Update-System für AskProAI

## 🎯 Zielsetzung

Ein intelligentes System, das automatisch erkennt, wann Dokumentation aktualisiert werden muss und entsprechende Updates vorschlägt oder durchführt.

## 🏗️ System-Architektur

### 1. **Trigger-Punkte Identifikation**

#### A. Code-basierte Trigger
```yaml
Neue Features:
  - Controller-Methoden hinzugefügt
  - Service-Klassen erweitert
  - API-Endpoints geändert
  - Datenbank-Migrationen

API-Änderungen:
  - Route-Definitionen geändert
  - Request/Response-Strukturen modifiziert
  - Authentifizierung angepasst

MCP-Server:
  - Neue MCP-Server registriert
  - Bestehende MCP-Server erweitert
  - MCP-Konfiguration geändert

Konfiguration:
  - .env.example Updates
  - config/*.php Änderungen
  - Neue Service-Provider
```

#### B. Git-basierte Trigger
```yaml
Commit-Patterns:
  - feat: Neue Features
  - fix: Bugfixes
  - breaking: Breaking Changes
  - api: API-Änderungen
  - config: Konfigurationsänderungen
  - mcp: MCP-Server Updates
```

### 2. **Automatisierungs-Pipeline**

#### A. Git Hooks Implementation

**`.git/hooks/post-commit`**
```bash
#!/bin/bash
# Automatische Dokumentations-Prüfung nach jedem Commit

# 1. Analysiere Commit-Inhalt
COMMIT_MSG=$(git log -1 --pretty=%B)
CHANGED_FILES=$(git diff-tree --no-commit-id --name-only -r HEAD)

# 2. Trigger Dokumentations-Check
php artisan docs:check-updates \
  --commit="$COMMIT_MSG" \
  --files="$CHANGED_FILES"
```

**`.git/hooks/pre-push`**
```bash
#!/bin/bash
# Verhindere Push bei veralteter Dokumentation

# Prüfe Dokumentations-Status
STATUS=$(php artisan docs:validate --json)

if [ "$STATUS" != "valid" ]; then
  echo "❌ Dokumentation ist nicht aktuell!"
  echo "Führe 'php artisan docs:update' aus"
  exit 1
fi
```

#### B. CI/CD Integration

**`.github/workflows/docs-update.yml`**
```yaml
name: Documentation Auto-Update

on:
  push:
    branches: [main, develop]
  pull_request:
    types: [opened, synchronize]

jobs:
  analyze-changes:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Dependencies
        run: composer install --no-interaction
      
      - name: Analyze Documentation Impact
        id: analyze
        run: |
          php artisan docs:analyze-impact \
            --base=${{ github.event.before }} \
            --head=${{ github.sha }} \
            --output=json > impact.json
      
      - name: Generate Update Suggestions
        if: steps.analyze.outputs.has_impact == 'true'
        run: |
          php artisan docs:generate-updates \
            --impact=impact.json \
            --ai-assist
      
      - name: Create PR with Updates
        if: steps.analyze.outputs.has_impact == 'true'
        uses: peter-evans/create-pull-request@v5
        with:
          title: "📚 Auto-Update Documentation"
          body: |
            ## 🤖 Automatische Dokumentations-Updates
            
            Basierend auf den Änderungen in diesem Branch wurden folgende Dokumentations-Updates vorgeschlagen:
            
            ${{ steps.analyze.outputs.summary }}
          branch: docs/auto-update-${{ github.sha }}
```

### 3. **Update-Prozess Implementation**

#### A. Artisan Commands

**`app/Console/Commands/DocsCheckUpdates.php`**
```php
<?php

namespace App\Console\Commands;

use App\Services\Documentation\DocumentationAnalyzer;
use App\Services\Documentation\UpdateSuggestionGenerator;
use Illuminate\Console\Command;

class DocsCheckUpdates extends Command
{
    protected $signature = 'docs:check-updates 
                            {--commit= : Commit message}
                            {--files= : Changed files}
                            {--auto-fix : Automatisch Updates anwenden}';
    
    protected $description = 'Prüft ob Dokumentation Updates benötigt';
    
    public function handle(
        DocumentationAnalyzer $analyzer,
        UpdateSuggestionGenerator $generator
    ): int {
        $this->info('🔍 Analysiere Änderungen...');
        
        // 1. Analysiere Änderungen
        $changes = $analyzer->analyzeChanges(
            $this->option('commit'),
            explode("\n", $this->option('files'))
        );
        
        if ($changes->isEmpty()) {
            $this->info('✅ Keine Dokumentations-Updates erforderlich');
            return 0;
        }
        
        // 2. Generiere Update-Vorschläge
        $suggestions = $generator->generateSuggestions($changes);
        
        // 3. Zeige Vorschläge oder wende sie an
        if ($this->option('auto-fix')) {
            $this->applyUpdates($suggestions);
        } else {
            $this->displaySuggestions($suggestions);
        }
        
        return 0;
    }
}
```

#### B. Dokumentations-Analyzer Service

**`app/Services/Documentation/DocumentationAnalyzer.php`**
```php
<?php

namespace App\Services\Documentation;

class DocumentationAnalyzer
{
    private array $documentMapping = [
        // API-Dokumentation
        'app/Http/Controllers/Api' => [
            'docs/api/endpoints.md',
            'public/docs/api/swagger/openapi.json'
        ],
        
        // MCP-Server Dokumentation
        'app/Services/MCP' => [
            'docs/MCP_COMPLETE_OVERVIEW.md',
            'CLAUDE.md' // MCP-Server Übersicht
        ],
        
        // Konfiguration
        'config/' => [
            'docs/DEPLOYMENT_GUIDE.md',
            '.env.example'
        ],
        
        // Datenbank
        'database/migrations' => [
            'docs/architecture/database-schema.md',
            'docs/DATABASE_SCHEMA.md'
        ],
        
        // Features
        'app/Filament/Admin' => [
            'docs/ADMIN_INTERFACE_GUIDE.md',
            'CLAUDE.md' // UI/UX Section
        ]
    ];
    
    public function analyzeChanges(string $commitMsg, array $files): ChangeSet
    {
        $changeSet = new ChangeSet();
        
        // 1. Analysiere Commit-Message
        $changeSet->addFromCommitMessage($commitMsg);
        
        // 2. Analysiere geänderte Dateien
        foreach ($files as $file) {
            $affectedDocs = $this->findAffectedDocuments($file);
            $changeSet->addAffectedDocuments($file, $affectedDocs);
        }
        
        // 3. Erkenne Breaking Changes
        if ($this->isBreakingChange($commitMsg, $files)) {
            $changeSet->markAsBreaking();
        }
        
        return $changeSet;
    }
    
    private function findAffectedDocuments(string $file): array
    {
        $affected = [];
        
        foreach ($this->documentMapping as $pattern => $docs) {
            if (str_starts_with($file, $pattern)) {
                $affected = array_merge($affected, $docs);
            }
        }
        
        // Spezielle Fälle
        if (str_ends_with($file, 'Resource.php')) {
            $affected[] = 'docs/FILAMENT_RESOURCES.md';
        }
        
        if (str_contains($file, 'routes/')) {
            $affected[] = 'docs/api/routes-generated.md';
        }
        
        return array_unique($affected);
    }
}
```

#### C. AI-gestützte Update-Generierung

**`app/Services/Documentation/AIDocumentationUpdater.php`**
```php
<?php

namespace App\Services\Documentation;

use App\Services\AI\ClaudeService;

class AIDocumentationUpdater
{
    public function __construct(
        private ClaudeService $claude,
        private DocumentationParser $parser
    ) {}
    
    public function generateUpdate(
        string $documentPath,
        array $codeChanges
    ): DocumentUpdate {
        // 1. Lade aktuelle Dokumentation
        $currentDoc = file_get_contents($documentPath);
        
        // 2. Erstelle Kontext für AI
        $context = $this->buildContext($codeChanges);
        
        // 3. Generiere Update-Vorschlag
        $prompt = $this->buildPrompt($currentDoc, $context);
        $suggestion = $this->claude->complete($prompt);
        
        // 4. Parse und validiere Vorschlag
        return $this->parser->parseUpdate($suggestion);
    }
    
    private function buildPrompt(string $doc, array $context): string
    {
        return <<<PROMPT
        Analysiere die folgenden Code-Änderungen und aktualisiere die Dokumentation entsprechend:
        
        AKTUELLE DOKUMENTATION:
        ```markdown
        {$doc}
        ```
        
        CODE-ÄNDERUNGEN:
        ```
        {$this->formatChanges($context)}
        ```
        
        AUFGABE:
        1. Identifiziere welche Teile der Dokumentation aktualisiert werden müssen
        2. Generiere präzise Updates die die Änderungen reflektieren
        3. Behalte den existierenden Stil und Ton bei
        4. Füge neue Sections hinzu wenn nötig
        5. Markiere veraltete Informationen
        
        FORMAT:
        Gib die aktualisierten Sections im Markdown-Format zurück.
        Nutze Kommentare (<!-- -->) um Änderungen zu erklären.
        PROMPT;
    }
}
```

### 4. **Monitoring & Validation**

#### A. Dokumentations-Freshness Monitor

**`app/Console/Commands/DocsMonitorFreshness.php`**
```php
<?php

namespace App\Console\Commands;

class DocsMonitorFreshness extends Command
{
    protected $signature = 'docs:monitor-freshness 
                            {--slack : Sende Alerts an Slack}
                            {--email= : Sende Report an Email}';
    
    public function handle(): int
    {
        $report = [];
        
        // 1. Prüfe alle Dokumentations-Dateien
        $documents = $this->findAllDocuments();
        
        foreach ($documents as $doc) {
            $freshness = $this->calculateFreshness($doc);
            
            if ($freshness['score'] < 0.7) {
                $report[] = [
                    'file' => $doc,
                    'score' => $freshness['score'],
                    'last_update' => $freshness['last_update'],
                    'related_changes' => $freshness['related_changes']
                ];
            }
        }
        
        // 2. Generiere Report
        $this->generateReport($report);
        
        // 3. Sende Notifications
        if ($this->option('slack')) {
            $this->sendSlackAlert($report);
        }
        
        if ($email = $this->option('email')) {
            $this->sendEmailReport($email, $report);
        }
        
        return 0;
    }
    
    private function calculateFreshness(string $docPath): array
    {
        // Analysiere:
        // - Letzte Änderung der Datei
        // - Änderungen in verwandten Code-Files
        // - Erwähnte Features/APIs die sich geändert haben
        // - Tote Links
        // - Veraltete Code-Beispiele
        
        $lastModified = filemtime($docPath);
        $relatedFiles = $this->findRelatedFiles($docPath);
        $codeChanges = $this->getRecentCodeChanges($relatedFiles);
        
        // Berechne Freshness-Score (0-1)
        $daysSinceUpdate = (time() - $lastModified) / 86400;
        $changeImpact = count($codeChanges) * 0.1;
        
        $score = max(0, 1 - ($daysSinceUpdate / 30) - $changeImpact);
        
        return [
            'score' => $score,
            'last_update' => date('Y-m-d', $lastModified),
            'related_changes' => $codeChanges
        ];
    }
}
```

#### B. Link & Reference Validator

**`app/Services/Documentation/LinkValidator.php`**
```php
<?php

namespace App\Services\Documentation;

class LinkValidator
{
    public function validateDocument(string $path): ValidationResult
    {
        $content = file_get_contents($path);
        $result = new ValidationResult($path);
        
        // 1. Prüfe interne Links
        preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches);
        foreach ($matches[2] as $link) {
            if ($this->isInternalLink($link)) {
                if (!$this->linkExists($link, $path)) {
                    $result->addBrokenLink($link);
                }
            }
        }
        
        // 2. Prüfe Code-Referenzen
        preg_match_all('/`([A-Z]\w+(::\w+)?)`/', $content, $classes);
        foreach ($classes[1] as $class) {
            if (!$this->classExists($class)) {
                $result->addBrokenReference($class);
            }
        }
        
        // 3. Prüfe Konfigurationsreferenzen
        preg_match_all('/config\([\'"]([^\'"]+)[\'"]\)/', $content, $configs);
        foreach ($configs[1] as $config) {
            if (!config()->has($config)) {
                $result->addMissingConfig($config);
            }
        }
        
        return $result;
    }
}
```

### 5. **Integration mit bestehenden Tools**

#### A. VS Code Extension

**`.vscode/askproai-docs.code-snippets`**
```json
{
  "Document New Feature": {
    "prefix": "docfeat",
    "body": [
      "## ${1:Feature Name}",
      "",
      "<!-- Last Updated: ${CURRENT_YEAR}-${CURRENT_MONTH}-${CURRENT_DATE} -->",
      "<!-- Related Files: ${2:files} -->",
      "",
      "### Übersicht",
      "${3:description}",
      "",
      "### Verwendung",
      "```php",
      "${4:code}",
      "```",
      "",
      "### Konfiguration",
      "${5:config}",
      "",
      "### API Reference",
      "${6:api}"
    ]
  }
}
```

#### B. Pre-commit Hook Integration

**`.pre-commit-config.yaml`**
```yaml
repos:
  - repo: local
    hooks:
      - id: check-docs
        name: Check Documentation Updates
        entry: php artisan docs:check-updates
        language: system
        pass_filenames: false
        always_run: true
      
      - id: validate-links
        name: Validate Documentation Links
        entry: php artisan docs:validate-links
        language: system
        files: '\.(md|mdx)$'
```

### 6. **Dashboard & Reporting**

#### A. Filament Dashboard Widget

**`app/Filament/Widgets/DocumentationHealthWidget.php`**
```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DocumentationHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.documentation-health';
    
    protected function getViewData(): array
    {
        $analyzer = app(DocumentationAnalyzer::class);
        
        return [
            'totalDocs' => $analyzer->getTotalDocuments(),
            'outdatedDocs' => $analyzer->getOutdatedDocuments(),
            'brokenLinks' => $analyzer->getBrokenLinksCount(),
            'lastUpdate' => $analyzer->getLastUpdateTime(),
            'healthScore' => $analyzer->calculateHealthScore(),
            'recentChanges' => $analyzer->getRecentUndocumentedChanges()
        ];
    }
}
```

### 7. **Automatisierte Changelog-Generierung**

**`app/Console/Commands/GenerateChangelog.php`**
```php
<?php

namespace App\Console\Commands;

class GenerateChangelog extends Command
{
    protected $signature = 'changelog:generate 
                            {--from= : Von Version/Tag}
                            {--to=HEAD : Bis Version/Tag}
                            {--format=markdown : Output Format}';
    
    public function handle(): int
    {
        // 1. Sammle Commits
        $commits = $this->getCommits(
            $this->option('from'),
            $this->option('to')
        );
        
        // 2. Kategorisiere nach Conventional Commits
        $categorized = $this->categorizeCommits($commits);
        
        // 3. Generiere Changelog
        $changelog = $this->generateChangelog($categorized);
        
        // 4. Update CHANGELOG.md
        $this->updateChangelogFile($changelog);
        
        // 5. Update relevante Dokumentation
        $this->updateRelatedDocs($categorized);
        
        return 0;
    }
}
```

## 🚀 Implementierungs-Roadmap

### Phase 1: Basis-Infrastruktur (2 Tage)
- [ ] Git Hooks Setup
- [ ] Basis Artisan Commands
- [ ] Document Mapping Konfiguration
- [ ] Simple Change Detection

### Phase 2: Analyse & Validierung (3 Tage)
- [ ] Documentation Analyzer Service
- [ ] Link & Reference Validator
- [ ] Freshness Monitor
- [ ] Validation Reports

### Phase 3: Automatisierung (3 Tage)
- [ ] CI/CD Integration
- [ ] Auto-Update Generator
- [ ] AI-Integration für Suggestions
- [ ] PR Auto-Creation

### Phase 4: Monitoring & UI (2 Tage)
- [ ] Filament Dashboard Widget
- [ ] Slack/Email Notifications
- [ ] VS Code Integration
- [ ] Metriken & Reports

### Phase 5: Erweiterte Features (2 Tage)
- [ ] Multi-Language Support
- [ ] Version-specific Docs
- [ ] API Doc Generation
- [ ] Interactive Tutorials

## 📊 Erfolgs-Metriken

1. **Documentation Freshness Score**: > 85%
2. **Broken Links**: < 5
3. **Auto-Update Success Rate**: > 90%
4. **Time to Documentation Update**: < 24h nach Code-Change
5. **Developer Satisfaction**: > 4.5/5

## 🔧 Konfiguration

**`config/documentation.php`**
```php
return [
    'auto_update' => [
        'enabled' => env('DOCS_AUTO_UPDATE', true),
        'ai_assist' => env('DOCS_AI_ASSIST', true),
        'create_prs' => env('DOCS_CREATE_PRS', true),
    ],
    
    'monitoring' => [
        'freshness_threshold_days' => 30,
        'slack_webhook' => env('DOCS_SLACK_WEBHOOK'),
        'email_recipients' => env('DOCS_EMAIL_RECIPIENTS'),
    ],
    
    'validation' => [
        'check_links' => true,
        'check_code_refs' => true,
        'check_config_refs' => true,
    ],
    
    'paths' => [
        'docs' => base_path('docs'),
        'api_docs' => public_path('docs/api'),
        'mkdocs' => base_path('docs_mkdocs'),
    ]
];
```

## 🎯 Erwartete Vorteile

1. **Immer aktuelle Dokumentation**: Automatische Updates bei Code-Änderungen
2. **Weniger manueller Aufwand**: AI-gestützte Vorschläge
3. **Bessere Code-Qualität**: Entwickler müssen über Dokumentation nachdenken
4. **Schnelleres Onboarding**: Neue Entwickler finden aktuelle Infos
5. **Compliance**: Automatische Audit-Trails für Änderungen

## 🚨 Wichtige Hinweise

1. **Review Required**: Alle automatischen Updates sollten reviewed werden
2. **AI Limitations**: AI-Vorschläge sind Hilfestellungen, keine finalen Texte
3. **Performance**: Dokumentations-Checks sollten asynchron laufen
4. **Security**: Keine sensitiven Daten in öffentliche Dokumentation
5. **Backup**: Immer Backups vor automatischen Updates erstellen