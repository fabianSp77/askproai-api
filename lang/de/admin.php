<?php

return [
    // Navigation Groups
    'navigation' => [
        'daily_operations' => 'Täglicher Betrieb',
        'customer_management' => 'Kundenverwaltung',
        'company_structure' => 'Unternehmensstruktur',
        'integrations' => 'Integrationen',
        'finance_billing' => 'Finanzen & Abrechnung',
        'settings' => 'Einstellungen',
        'system_monitoring' => 'System & Monitoring',
        'development' => 'Entwicklung',
        // Legacy - für Rückwärtskompatibilität
        'operations_center' => 'Operations Center',
        'system' => 'System',
        'analytics' => 'Analytics & Reports',
        'financial' => 'Finanzen',
        'automation' => 'Automatisierung',
        'ai_services' => 'AI Services',
        'communications' => 'Kommunikation',
        'workflows' => 'Workflows',
        'developer' => 'Entwickler',
    ],
    
    // Resource Labels
    'resources' => [
        'calls' => 'Anrufe',
        'appointments' => 'Termine',
        'customers' => 'Kunden',
        'companies' => 'Firmen',
        'branches' => 'Filialen',
        'staff' => 'Mitarbeiter',
        'services' => 'Dienstleistungen',
        'integrations' => 'Integrationen',
        'users' => 'Benutzer',
        'invoices' => 'Rechnungen',
        'campaigns' => 'Kampagnen',
        'webhooks' => 'Webhooks',
        'api_logs' => 'API-Logs',
        'custom_functions' => 'Custom Functions',
        'notification_templates' => 'Benachrichtigungsvorlagen',
        'workflow_rules' => 'Workflow-Regeln',
        'billing_periods' => 'Abrechnungszeiträume',
        'retell_agents' => 'Retell Agenten',
        'knowledge_entries' => 'Wissensdatenbank',
    ],
    
    // Dashboard & Pages
    'dashboards' => [
        'main' => 'Hauptdashboard',
        'simple' => 'Einfaches Dashboard',
        'operations' => 'Operations Center',
        'ai_call_center' => 'AI Call Center',
        'system_monitoring' => 'System-Überwachung',
        'webhook_analysis' => 'Webhook-Analyse',
        'intelligent_sync' => 'Intelligente Synchronisation',
        'ml_training' => 'ML Training Dashboard',
        'retell_agent_editor' => 'Retell Agent Editor',
    ],
    
    // Quick Actions
    'quick_actions' => [
        'title' => 'Schnellzugriff',
        'calls' => 'Anrufliste',
        'appointments' => 'Termine verwalten',
        'customers' => 'Kundenverwaltung',
        'new_appointment' => 'Neuer Termin',
        'main_dashboard' => 'Hauptdashboard',
        'system_status' => 'Systemstatus',
    ],
    
    // Common Actions
    'actions' => [
        'create' => 'Erstellen',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'view' => 'Anzeigen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'refresh' => 'Aktualisieren',
        'search' => 'Suchen',
        'filter' => 'Filtern',
        'export' => 'Exportieren',
        'import' => 'Importieren',
        'download' => 'Herunterladen',
        'calculate' => 'Berechnen',
        'login' => 'Anmelden',
        'logout' => 'Abmelden',
        'mark_as_non_billable' => 'Als nicht abrechenbar markieren',
        'create_credit_note' => 'Gutschrift erstellen',
        'preflight_check' => 'Preflight Check',
        'duplicate' => 'Duplizieren',
        'restore' => 'Wiederherstellen',
        'archive' => 'Archivieren',
    ],
    
    // Time Periods
    'time_periods' => [
        'last_7_days' => 'Letzte 7 Tage',
        'last_30_days' => 'Letzte 30 Tage',
        'last_90_days' => 'Letzte 90 Tage',
        'this_month' => 'Dieser Monat',
        'last_month' => 'Letzter Monat',
        'this_year' => 'Dieses Jahr',
        'custom_range' => 'Benutzerdefiniert',
        'today' => 'Heute',
        'yesterday' => 'Gestern',
        'this_week' => 'Diese Woche',
        'last_week' => 'Letzte Woche',
    ],
    
    // Status Labels
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'pending' => 'Ausstehend',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Abgesagt',
        'scheduled' => 'Geplant',
        'confirmed' => 'Bestätigt',
        'no_show' => 'Nicht erschienen',
        'in_progress' => 'In Bearbeitung',
        'failed' => 'Fehlgeschlagen',
        'success' => 'Erfolgreich',
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'archived' => 'Archiviert',
    ],
    
    // Table Headers
    'table' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'company' => 'Firma',
        'branch' => 'Filiale',
        'date' => 'Datum',
        'time' => 'Zeit',
        'duration' => 'Dauer',
        'status' => 'Status',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
        'actions' => 'Aktionen',
        'customer' => 'Kunde',
        'service' => 'Dienstleistung',
        'staff' => 'Mitarbeiter',
        'price' => 'Preis',
        'total' => 'Gesamt',
        'notes' => 'Notizen',
        'type' => 'Typ',
        'priority' => 'Priorität',
        'assigned_to' => 'Zugewiesen an',
        'tags' => 'Tags',
        'category' => 'Kategorie',
        'description' => 'Beschreibung',
    ],
    
    // Tooltips
    'tooltips' => [
        // Billing & Financial Actions
        'mark_non_billable' => 'Markiert diesen Eintrag als nicht abrechenbar. Bereits abgerechnete Einträge erhalten automatisch eine Gutschrift.',
        'create_credit_note' => 'Erstellt eine Gutschrift für diesen Kunden. Die Gutschrift wird automatisch mit zukünftigen Rechnungen verrechnet.',
        'finalize_invoice' => 'Finalisiert die Rechnung. Nach der Finalisierung kann die Rechnung nicht mehr bearbeitet werden.',
        'preview_invoice' => 'Zeigt eine Vorschau der Rechnung im PDF-Format',
        'download_pdf' => 'Lädt die Rechnung als PDF herunter',
        
        // System Actions
        'preflight_check' => 'Führt eine vollständige Systemprüfung durch: API-Verbindungen, Webhooks, Berechtigungen und Konfiguration',
        'health_check' => 'Prüft den aktuellen Systemstatus und zeigt mögliche Probleme an',
        'sync_data' => 'Synchronisiert Daten mit externen Systemen (Cal.com, Retell.ai, Stripe)',
        
        // Data Actions
        'refresh_data' => 'Aktualisiert die Daten aus der Datenbank. Lädt alle Änderungen neu.',
        'refresh_call_data' => 'Aktualisiert Anrufdaten von Retell.ai inkl. Transkription und Analyse',
        'refresh_appointment' => 'Synchronisiert Termindaten mit dem Kalendersystem',
        'refresh_customer' => 'Aktualisiert Kundendaten und berechnet Statistiken neu',
        
        // Export/Import Actions
        'export_csv' => 'Exportiert die gefilterten Daten als CSV-Datei zum Download',
        'export_excel' => 'Exportiert die Daten als Excel-Datei mit formatierter Tabelle',
        'export_pdf' => 'Erstellt einen PDF-Report der aktuellen Ansicht',
        'import_data' => 'Importiert Daten aus einer CSV- oder Excel-Datei',
        'download_template' => 'Lädt eine Vorlage für den Datenimport herunter',
        
        // View/Edit Actions
        'view_details' => 'Zeigt alle Details und zugehörige Informationen an',
        'quick_view' => 'Schnellansicht ohne die Seite zu verlassen',
        'edit_entry' => 'Öffnet das Bearbeitungsformular für diesen Eintrag',
        'edit_inline' => 'Bearbeitung direkt in der Tabelle ohne Seitenwechsel',
        'view_history' => 'Zeigt die Änderungshistorie dieses Eintrags',
        
        // Delete/Archive Actions
        'delete_entry' => 'Löscht diesen Eintrag dauerhaft. Diese Aktion kann nicht rückgängig gemacht werden!',
        'soft_delete' => 'Verschiebt den Eintrag in den Papierkorb. Kann wiederhergestellt werden.',
        'archive_entry' => 'Archiviert den Eintrag. Archivierte Einträge sind weiterhin verfügbar.',
        'restore_entry' => 'Stellt einen gelöschten oder archivierten Eintrag wieder her',
        
        // Bulk Actions
        'select_all' => 'Wählt alle Einträge auf der aktuellen Seite aus',
        'select_all_pages' => 'Wählt alle Einträge über alle Seiten hinweg aus',
        'bulk_edit' => 'Bearbeitet mehrere Einträge gleichzeitig',
        'bulk_delete' => 'Löscht alle ausgewählten Einträge',
        'bulk_export' => 'Exportiert nur die ausgewählten Einträge',
        
        // Filter & Search
        'filter_options' => 'Öffnet erweiterte Filteroptionen für präzise Suche',
        'clear_filters' => 'Setzt alle Filter zurück und zeigt alle Einträge',
        'save_filter' => 'Speichert die aktuelle Filtereinstellung für späteren Zugriff',
        'search_global' => 'Durchsucht alle Felder nach dem Suchbegriff',
        
        // Communication Actions
        'send_email' => 'Sendet eine E-Mail an den Kontakt',
        'send_sms' => 'Sendet eine SMS an die hinterlegte Mobilnummer',
        'call_now' => 'Startet einen Anruf über das AI-Telefonsystem',
        'send_notification' => 'Sendet eine Push-Benachrichtigung',
        
        // AI & Automation
        'ai_analyze' => 'Führt eine KI-gestützte Analyse durch',
        'generate_summary' => 'Erstellt eine KI-generierte Zusammenfassung',
        'suggest_actions' => 'Lässt die KI Aktionsvorschläge generieren',
        'automate_task' => 'Automatisiert wiederkehrende Aufgaben',
        
        // Calendar & Scheduling
        'schedule_appointment' => 'Öffnet die Terminplanungsansicht',
        'reschedule' => 'Verschiebt den Termin auf einen anderen Zeitpunkt',
        'cancel_appointment' => 'Sagt den Termin ab und benachrichtigt alle Teilnehmer',
        'confirm_appointment' => 'Bestätigt den Termin und sendet Bestätigungen',
        
        // Settings & Configuration
        'configure' => 'Öffnet die Konfigurationseinstellungen',
        'test_connection' => 'Testet die Verbindung zu externen Diensten',
        'view_logs' => 'Zeigt die System- und Aktivitätsprotokolle',
        'clear_cache' => 'Leert den Cache für diese Ressource',
        
        // Help & Info
        'show_help' => 'Zeigt Hilfe und Dokumentation an',
        'show_shortcuts' => 'Zeigt verfügbare Tastenkombinationen',
        'view_tutorial' => 'Startet das interaktive Tutorial',
        'report_issue' => 'Meldet ein Problem oder Bug',
        
        // Status Actions
        'toggle_status' => 'Aktiviert/Deaktiviert diesen Eintrag',
        'mark_complete' => 'Markiert als abgeschlossen',
        'mark_pending' => 'Setzt auf "Ausstehend" zurück',
        'approve' => 'Genehmigt diesen Eintrag',
        'reject' => 'Lehnt diesen Eintrag ab',
        
        // Navigation
        'next_page' => 'Zur nächsten Seite (Tastenkürzel: →)',
        'previous_page' => 'Zur vorherigen Seite (Tastenkürzel: ←)',
        'first_page' => 'Zur ersten Seite',
        'last_page' => 'Zur letzten Seite',
        'show_more' => 'Zeigt weitere Einträge oder Optionen an',
        
        // Special Actions
        'duplicate' => 'Erstellt eine Kopie dieses Eintrags',
        'merge_duplicates' => 'Führt doppelte Einträge zusammen',
        'split_entry' => 'Teilt diesen Eintrag in mehrere auf',
        'convert_type' => 'Konvertiert in einen anderen Typ',
        'generate_report' => 'Erstellt einen detaillierten Bericht',
    ],
    
    // Messages
    'messages' => [
        'saved' => 'Erfolgreich gespeichert',
        'deleted' => 'Erfolgreich gelöscht',
        'updated' => 'Erfolgreich aktualisiert',
        'created' => 'Erfolgreich erstellt',
        'error' => 'Ein Fehler ist aufgetreten',
        'loading' => 'Wird geladen...',
        'no_results' => 'Keine Ergebnisse gefunden',
        'confirm_delete' => 'Möchten Sie diesen Eintrag wirklich löschen?',
        'confirm_action' => 'Sind Sie sicher?',
        'welcome' => 'Willkommen im Admin-Portal',
        'session_expired' => 'Ihre Sitzung ist abgelaufen',
        'unauthorized' => 'Keine Berechtigung für diese Aktion',
        'validation_error' => 'Bitte überprüfen Sie Ihre Eingaben',
        'processing' => 'Wird verarbeitet...',
        'please_wait' => 'Bitte warten...',
    ],
    
    // Forms
    'forms' => [
        'select_company' => 'Firma auswählen',
        'select_branch' => 'Filiale auswählen',
        'select_date' => 'Datum auswählen',
        'select_time' => 'Zeit auswählen',
        'select_service' => 'Dienstleistung auswählen',
        'select_staff' => 'Mitarbeiter auswählen',
        'enter_name' => 'Name eingeben',
        'enter_email' => 'E-Mail eingeben',
        'enter_phone' => 'Telefonnummer eingeben',
        'enter_notes' => 'Notizen hinzufügen',
        'required_field' => 'Pflichtfeld',
        'optional_field' => 'Optional',
        'save_changes' => 'Änderungen speichern',
        'discard_changes' => 'Änderungen verwerfen',
    ],
    
    // Widgets
    'widgets' => [
        'call_stats' => 'Anrufstatistik',
        'appointment_overview' => 'Terminübersicht',
        'revenue_chart' => 'Umsatzentwicklung',
        'customer_insights' => 'Kunden-Intelligence',
        'system_health' => 'Systemzustand',
        'recent_activity' => 'Letzte Aktivitäten',
        'performance_metrics' => 'Leistungskennzahlen',
        'quick_stats' => 'Schnellstatistik',
        'branch_performance' => 'Filialleistung',
        'integration_status' => 'Integrationsstatus',
        'financial_intelligence' => 'Finanz-Intelligence',
        'live_activity' => 'Live-Aktivitäten',
    ],
    
    // Settings
    'settings' => [
        'general' => 'Allgemein',
        'notifications' => 'Benachrichtigungen',
        'security' => 'Sicherheit',
        'integrations' => 'Integrationen',
        'api' => 'API-Einstellungen',
        'billing' => 'Abrechnung',
        'appearance' => 'Erscheinungsbild',
        'language' => 'Sprache',
        'timezone' => 'Zeitzone',
        'date_format' => 'Datumsformat',
        'currency' => 'Währung',
        'company_settings' => 'Firmeneinstellungen',
        'branch_settings' => 'Filialeinstellungen',
        'user_preferences' => 'Benutzereinstellungen',
    ],
];