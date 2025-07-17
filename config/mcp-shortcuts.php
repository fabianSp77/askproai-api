<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Command Shortcuts Configuration
    |--------------------------------------------------------------------------
    |
    | Define custom shortcuts and aliases for common MCP operations.
    | These shortcuts make it easier to execute frequent tasks.
    |
    */

    'shortcuts' => [
        // Appointment Management
        'book' => [
            'server' => 'appointment',
            'tool' => 'create_appointment',
            'description' => 'Schnell einen Termin buchen',
            'prompts' => [
                'customer_phone' => 'Telefonnummer des Kunden',
                'service' => 'Service/Dienstleistung',
                'date' => 'Datum (YYYY-MM-DD)',
                'time' => 'Uhrzeit (HH:MM)',
            ],
        ],
        
        'cancel' => [
            'server' => 'appointment',
            'tool' => 'cancel_appointment',
            'description' => 'Termin stornieren',
            'prompts' => [
                'appointment_id' => 'Termin-ID',
                'reason' => 'Stornierungsgrund (optional)',
            ],
        ],
        
        // Call Management
        'import-calls' => [
            'server' => 'retell',
            'tool' => 'fetch_calls',
            'description' => 'Anrufe von Retell.ai importieren',
            'defaults' => [
                'limit' => 50,
                'order' => 'desc',
            ],
        ],
        
        'call-stats' => [
            'server' => 'retell',
            'tool' => 'get_call_statistics',
            'description' => 'Anrufstatistiken abrufen',
        ],
        
        // Customer Management
        'find-customer' => [
            'server' => 'customer',
            'tool' => 'search_customers',
            'description' => 'Kunden suchen',
            'prompts' => [
                'query' => 'Suchbegriff (Name oder Telefon)',
            ],
        ],
        
        'customer-history' => [
            'server' => 'customer',
            'tool' => 'get_customer_history',
            'description' => 'Kundenhistorie anzeigen',
            'prompts' => [
                'customer_id' => 'Kunden-ID oder Telefonnummer',
            ],
        ],
        
        // Synchronization
        'sync-calcom' => [
            'command' => 'calcom:sync',
            'description' => 'Cal.com Kalender synchronisieren',
        ],
        
        'sync-github' => [
            'command' => 'github:notion sync-issues',
            'description' => 'GitHub Issues mit Notion synchronisieren',
        ],
        
        // Memory Bank
        'remember-task' => [
            'server' => 'memory_bank',
            'tool' => 'create_memory',
            'description' => 'Aufgabe in Memory Bank speichern',
            'defaults' => [
                'memory_type' => 'task',
            ],
            'prompts' => [
                'key' => 'Aufgaben-Titel',
                'value' => 'Aufgabenbeschreibung',
                'tags' => 'Tags (kommagetrennt)',
            ],
        ],
        
        'search-memory' => [
            'server' => 'memory_bank',
            'tool' => 'search_memories',
            'description' => 'Memory Bank durchsuchen',
            'prompts' => [
                'query' => 'Suchbegriff',
            ],
        ],
        
        // Quick Reports
        'daily-report' => [
            'server' => 'database',
            'tool' => 'execute_query',
            'description' => 'Tagesbericht generieren',
            'query' => "
                SELECT 
                    COUNT(DISTINCT c.id) as total_calls,
                    COUNT(DISTINCT a.id) as total_appointments,
                    COUNT(DISTINCT cu.id) as unique_customers
                FROM calls c
                LEFT JOIN appointments a ON DATE(a.scheduled_at) = CURDATE()
                LEFT JOIN customers cu ON cu.phone = c.from_number
                WHERE DATE(c.created_at) = CURDATE()
            ",
        ],
        
        // Health Checks
        'check-integrations' => [
            'multi' => [
                ['server' => 'calcom', 'tool' => 'health_check'],
                ['server' => 'retell', 'tool' => 'health_check'],
                ['server' => 'stripe', 'tool' => 'health_check'],
            ],
            'description' => 'Alle Integrationen prüfen',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Alias Definitions
    |--------------------------------------------------------------------------
    |
    | Map short aliases to full shortcut names for convenience.
    |
    */
    
    'aliases' => [
        'b' => 'book',
        'c' => 'cancel',
        'f' => 'find-customer',
        'i' => 'import-calls',
        'r' => 'remember-task',
        's' => 'sync-calcom',
        'h' => 'check-integrations',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Parameters
    |--------------------------------------------------------------------------
    |
    | Global default parameters that apply to all shortcuts unless overridden.
    |
    */
    
    'defaults' => [
        'branch_id' => env('DEFAULT_BRANCH_ID'),
        'source' => 'mcp_shortcut',
        'created_by' => 'mcp_cli',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Quick Action Groups
    |--------------------------------------------------------------------------
    |
    | Group shortcuts for display in the dashboard widget.
    |
    */
    
    'groups' => [
        'appointments' => [
            'label' => 'Terminverwaltung',
            'icon' => 'heroicon-o-calendar',
            'shortcuts' => ['book', 'cancel', 'daily-report'],
        ],
        
        'customers' => [
            'label' => 'Kundenverwaltung',
            'icon' => 'heroicon-o-users',
            'shortcuts' => ['find-customer', 'customer-history'],
        ],
        
        'operations' => [
            'label' => 'Betrieb',
            'icon' => 'heroicon-o-cog',
            'shortcuts' => ['import-calls', 'sync-calcom', 'check-integrations'],
        ],
        
        'memory' => [
            'label' => 'Memory Bank',
            'icon' => 'heroicon-o-cpu-chip',
            'shortcuts' => ['remember-task', 'search-memory'],
        ],
    ],
];