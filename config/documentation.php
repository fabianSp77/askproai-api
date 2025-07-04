<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Auto-Update Configuration
    |--------------------------------------------------------------------------
    |
    | Konfiguration für das automatische Dokumentations-Update-System
    |
    */
    
    'auto_update' => [
        // Automatische Updates aktivieren
        'enabled' => env('DOCS_AUTO_UPDATE', true),
        
        // AI-Unterstützung für Update-Vorschläge
        'ai_assist' => env('DOCS_AI_ASSIST', false),
        
        // Automatisch Pull Requests erstellen
        'create_prs' => env('DOCS_CREATE_PRS', true),
        
        // Branch-Prefix für Auto-Update PRs
        'pr_branch_prefix' => 'docs/auto-update-',
        
        // Labels für Auto-Update PRs
        'pr_labels' => ['documentation', 'automated'],
    ],
    
    'monitoring' => [
        // Schwellenwert für veraltete Dokumente (in Tagen)
        'freshness_threshold_days' => env('DOCS_FRESHNESS_THRESHOLD', 30),
        
        // Minimum Health Score für Push-Blockierung
        'min_health_score' => env('DOCS_MIN_HEALTH_SCORE', 50),
        
        // Slack Webhook für Notifications
        'slack_webhook' => env('DOCS_SLACK_WEBHOOK'),
        
        // Email-Empfänger für Reports (komma-getrennt)
        'email_recipients' => env('DOCS_EMAIL_RECIPIENTS'),
        
        // Notification-Schwellenwerte
        'notify_health_below' => env('DOCS_NOTIFY_HEALTH_BELOW', 60),
        'notify_outdated_above' => env('DOCS_NOTIFY_OUTDATED_ABOVE', 10),
    ],
    
    'validation' => [
        // Interne Links prüfen
        'check_links' => true,
        
        // Code-Referenzen prüfen
        'check_code_refs' => true,
        
        // Config-Referenzen prüfen
        'check_config_refs' => true,
        
        // API-Endpoints prüfen
        'check_api_endpoints' => true,
        
        // Markdown-Syntax validieren
        'validate_markdown' => true,
    ],
    
    'paths' => [
        // Haupt-Dokumentationsverzeichnis
        'docs' => base_path('docs'),
        
        // API-Dokumentation
        'api_docs' => public_path('docs/api'),
        
        // MkDocs Quellen
        'mkdocs' => base_path('docs_mkdocs'),
        
        // Zusätzliche Markdown-Dateien im Root
        'root_docs' => [
            'README.md',
            'CHANGELOG.md',
            'CONTRIBUTING.md',
            'CLAUDE.md',
        ],
        
        // Ausgeschlossene Pfade
        'exclude' => [
            'vendor/',
            'node_modules/',
            'storage/',
            '.git/',
        ],
    ],
    
    'mappings' => [
        /*
        |--------------------------------------------------------------------------
        | Code zu Dokumentations-Mapping
        |--------------------------------------------------------------------------
        |
        | Definiert welche Dokumentations-Dateien von Code-Änderungen 
        | betroffen sind
        |
        */
        
        'code_to_docs' => [
            'app/Http/Controllers/Api' => [
                'docs/api/endpoints.md',
                'public/docs/api/swagger/openapi.json',
            ],
            
            'app/Services/MCP' => [
                'docs/MCP_COMPLETE_OVERVIEW.md',
                'docs/MCP_INTEGRATION_GUIDE.md',
                'CLAUDE.md',
            ],
            
            'app/Filament' => [
                'docs/ADMIN_INTERFACE_GUIDE.md',
                'docs/FILAMENT_RESOURCES.md',
            ],
            
            'database/migrations' => [
                'docs/architecture/database-schema.md',
                'docs/DATABASE_SCHEMA.md',
            ],
            
            'config/' => [
                'docs/DEPLOYMENT_GUIDE.md',
                '.env.example',
            ],
            
            'routes/' => [
                'docs/api/routes-generated.md',
                'docs/api/endpoints.md',
            ],
        ],
    ],
    
    'git_hooks' => [
        // Git Hooks aktivieren
        'enabled' => env('DOCS_GIT_HOOKS', true),
        
        // Post-Commit Hook
        'post_commit' => [
            'enabled' => true,
            'skip_on_ci' => true,
        ],
        
        // Pre-Push Hook
        'pre_push' => [
            'enabled' => true,
            'block_on_low_health' => true,
            'protected_branches' => ['main', 'master', 'develop'],
        ],
        
        // Commit-Msg Hook
        'commit_msg' => [
            'enabled' => true,
            'enforce_conventional' => true,
            'add_doc_reminder' => true,
        ],
    ],
    
    'changelog' => [
        // Automatische Changelog-Generierung
        'auto_generate' => true,
        
        // Conventional Commit Types die im Changelog erscheinen
        'included_types' => ['feat', 'fix', 'breaking', 'perf', 'security'],
        
        // Changelog-Datei
        'file' => base_path('CHANGELOG.md'),
        
        // Versionierungs-Schema
        'versioning' => 'semver', // semver, date, custom
    ],
    
    'templates' => [
        // Templates für neue Dokumentations-Dateien
        'stubs' => [
            'api' => resource_path('stubs/docs/api.md.stub'),
            'feature' => resource_path('stubs/docs/feature.md.stub'),
            'guide' => resource_path('stubs/docs/guide.md.stub'),
            'mcp' => resource_path('stubs/docs/mcp.md.stub'),
        ],
    ],
    
    'ai' => [
        // AI-Service für Dokumentations-Updates
        'provider' => env('DOCS_AI_PROVIDER', 'claude'), // claude, openai, local
        
        // AI Model
        'model' => env('DOCS_AI_MODEL', 'claude-3-opus-20240229'),
        
        // Max Tokens für AI-Generierung
        'max_tokens' => env('DOCS_AI_MAX_TOKENS', 4000),
        
        // Temperatur für AI-Generierung (0-1)
        'temperature' => env('DOCS_AI_TEMPERATURE', 0.3),
    ],
    
    'dashboard' => [
        // Dashboard-Widget aktivieren
        'widget_enabled' => true,
        
        // Widget nur für bestimmte Rollen
        'widget_roles' => ['super_admin'],
        
        // Cache-Dauer für Widget-Daten (Sekunden)
        'widget_cache_ttl' => 300,
        
        // Live-Polling Interval
        'polling_interval' => '30s',
    ],
    
    'commands' => [
        // Scheduling für automatische Checks
        'schedule' => [
            // Täglicher Freshness-Check
            'freshness_check' => [
                'enabled' => true,
                'cron' => '0 9 * * *', // Täglich um 9 Uhr
                'notify_on_issues' => true,
            ],
            
            // Wöchentlicher Deep-Scan
            'deep_scan' => [
                'enabled' => true,
                'cron' => '0 10 * * 1', // Montags um 10 Uhr
                'check_all_links' => true,
                'validate_code_examples' => true,
            ],
        ],
    ],
];