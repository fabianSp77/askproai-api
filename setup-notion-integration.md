# 🚀 Notion Integration Setup Guide

## 1. Notion API Token erstellen

1. Gehen Sie zu https://www.notion.so/my-integrations
2. Klicken Sie auf "New integration"
3. Name: "AskProAI Documentation Sync"
4. Workspace auswählen
5. Capabilities: Read, Write, Update
6. Submit
7. Kopieren Sie den "Internal Integration Token"

## 2. Parent Page ID finden

1. Öffnen Sie die Seite in Notion wo die Docs hin sollen
2. Klicken Sie auf "..." → "Copy link"
3. Der Link sieht so aus: https://notion.so/workspace/PageName-205aba1176e28052a79dc0feb2093cad
4. Die ID ist: 205aba1176e28052a79dc0feb2093cad

## 3. Environment Variables setzen

```bash
# In /var/www/api-gateway/.env hinzufügen:
NOTION_API_KEY=secret_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
NOTION_WORKSPACE_ID=xxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
NOTION_PARENT_PAGE_ID=205aba1176e28052a79dc0feb2093cad
```

## 4. Notion Integration aktivieren

```bash
# Configuration Cache erneuern
php artisan config:clear
php artisan config:cache

# Notion MCP Server aktivieren (in config/mcp-servers.php)
'notion' => [
    'enabled' => env('MCP_NOTION_ENABLED', true),
    'class' => \App\Services\MCP\NotionMCPServer::class,
    // ...
]
```

## 5. Import-Script erstellen

```php
<?php
// create-all-notion-docs.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Services\MCP\NotionMCPServer;

$notion = new NotionMCPServer();

// 1. Hauptstruktur erstellen
$structure = [
    'AskProAI Documentation' => [
        'Technical Documentation' => [
            'API Reference',
            'MCP Servers',
            'Error Patterns',
            'Integration Guides'
        ],
        'Customer Help Center' => [
            'Getting Started',
            'Account Management',
            'Appointments',
            'Troubleshooting',
            'FAQ'
        ],
        'Developer Resources' => [
            'Setup & Installation',
            'Best Practices',
            'Troubleshooting',
            'Contributing'
        ],
        'Business Documentation' => [
            'Onboarding',
            'Feature Overview',
            'Pricing & Billing',
            'Support'
        ]
    ]
];

// 2. Alle lokalen Docs sammeln
$docsToImport = [
    // Help Center
    '/resources/docs/help-center/**/*.md',
    // Technical Docs
    '/docs_mkdocs/**/*.md',
    // Root Docs
    '/*.md',
    // Error Patterns
    '/ERROR_PATTERNS.md',
    '/TROUBLESHOOTING_DECISION_TREE.md'
];

// 3. Import durchführen
foreach ($docsToImport as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        echo "Importing: $file\n";
        $notion->importDocument($file);
    }
}
```

## 6. Verifizierung

Nach dem Import:
1. Öffnen Sie Notion
2. Navigieren Sie zur Parent Page
3. Prüfen Sie die importierten Seiten
4. Struktur sollte sein:
   ```
   📄 AskProAI Documentation
   ├── 📁 Technical Documentation
   ├── 📁 Customer Help Center
   ├── 📁 Developer Resources
   └── 📁 Business Documentation
   ```

## 7. Automatische Synchronisation

```bash
# Cron Job für tägliche Sync
0 2 * * * /usr/bin/php /var/www/api-gateway/artisan notion:sync
```

## Troubleshooting

**Problem: "Invalid API Token"**
- Prüfen Sie ob der Token korrekt kopiert wurde
- Stellen Sie sicher dass die Integration Zugriff auf den Workspace hat

**Problem: "Page not found"**
- Teilen Sie die Parent Page mit der Integration
- Klicken Sie auf "Share" → "Invite" → Ihre Integration auswählen

**Problem: "Import failed"**
- Prüfen Sie die Logs: `tail -f storage/logs/laravel.log`
- Verifizieren Sie die Dateipfade