<?php

return [
    // Hauptlabels
    'title' => 'Dienstleistungen',
    'singular' => 'Dienstleistung',
    'plural' => 'Dienstleistungen',
    'navigation_label' => 'Dienstleistungen',
    'navigation_group' => 'Geschäftsverwaltung',

    // Felder
    'name' => 'Name',
    'description' => 'Beschreibung',
    'category' => 'Kategorie',
    'price' => 'Preis',
    'duration' => 'Dauer (Minuten)',
    'buffer_time' => 'Pufferzeit (Minuten)',
    'is_active' => 'Aktiv',
    'online_booking' => 'Online-Buchung verfügbar',
    'company' => 'Unternehmen',
    'branch' => 'Filiale',
    'assigned_by' => 'Zugewiesen von',
    'created_at' => 'Erstellt am',
    'updated_at' => 'Aktualisiert am',

    // Composite Service Felder
    'composite_heading' => 'Zusammengesetzte Dienstleistung',
    'composite_description' => 'Konfigurieren Sie Dienstleistungen mit mehreren Segmenten und Lücken',
    'enable_composite' => 'Zusammengesetzte Dienstleistung aktivieren',
    'composite_helper' => 'Ermöglicht dieser Dienstleistung mehrere Segmente mit Lücken dazwischen',
    'service_segments' => 'Dienstleistungssegmente',
    'segment_key' => 'Segment-Schlüssel',
    'segment_name' => 'Segment-Name',
    'segment_duration' => 'Dauer (Minuten)',
    'gap_after' => 'Lücke danach (Minuten)',
    'gap_helper' => 'Lückendauer vor dem nächsten Segment',
    'segments_helper' => 'Mindestens 2 Segmente erforderlich für zusammengesetzte Dienstleistungen. Maximal 10 Segmente erlaubt.',
    'gap_policy' => 'Lückenbuchungsrichtlinie',
    'gap_policy_helper' => 'Definiert die Verfügbarkeit des Personals während der Lücken zwischen den Segmenten',
    'total_duration' => 'Gesamtdauer',

    // Staff Assignment
    'staff_assignment' => 'Personalzuweisung',
    'staff_description' => 'Personal dieser Dienstleistung zuweisen',
    'staff_member' => 'Mitarbeiter',
    'primary_staff' => 'Primär',
    'primary_helper' => 'Primäres Personal für diese Dienstleistung',
    'can_book' => 'Kann buchen',
    'allowed_segments' => 'Erlaubte Segmente',
    'segments_helper' => 'Welche Segmente dieses Personal bearbeiten kann',
    'skill_level' => 'Qualifikationsstufe',
    'weight' => 'Gewicht',
    'weight_helper' => 'Präferenzgewicht (0-9.99)',
    'custom_duration' => 'Benutzerdefinierte Dauer (Min)',
    'custom_duration_placeholder' => 'Standard verwenden',
    'custom_duration_helper' => 'Standarddauer überschreiben',

    // Cal.com Integration
    'calcom_integration' => 'Cal.com Integration',
    'calcom_event_type_id' => 'Cal.com Event-Typ ID',
    'sync_status' => 'Synchronisierungsstatus',
    'last_sync' => 'Letzte Synchronisierung',
    'sync_error' => 'Synchronisierungsfehler',

    // Status-Labels
    'status_active' => 'Aktiv',
    'status_inactive' => 'Inaktiv',
    'status_pending' => 'Ausstehend',
    'status_synced' => 'Synchronisiert',
    'status_error' => 'Fehler',

    // Aktionen
    'actions' => [
        'view' => 'Anzeigen',
        'create' => 'Neue Dienstleistung',
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'sync' => 'Mit Cal.com synchronisieren',
        'unsync' => 'Synchronisierung aufheben',
        'assign_company' => 'Unternehmen zuweisen',
        'auto_assign' => 'Automatisch zuweisen',
        'bulk_sync' => 'Massenhafte Synchronisierung',
        'bulk_activate' => 'Massenhafte Aktivierung',
        'bulk_deactivate' => 'Massenhafte Deaktivierung',
        'bulk_edit' => 'Massenhafte Bearbeitung',
        'bulk_auto_assign' => 'Massenhafte automatische Zuweisung',
        'export' => 'Exportieren',
        'import' => 'Importieren',
    ],

    // Modal-Überschriften
    'modals' => [
        'sync_heading' => 'Mit Cal.com synchronisieren',
        'assign_company_heading' => 'Unternehmen zuweisen',
        'bulk_sync_heading' => 'Massenhafte Synchronisierung mit Cal.com',
        'bulk_edit_heading' => 'Dienstleistungen massenweise bearbeiten',
        'delete_heading' => 'Dienstleistung löschen',
        'delete_subheading' => 'Sind Sie sicher, dass Sie diese Dienstleistung löschen möchten?',
    ],

    // Benachrichtigungen
    'notifications' => [
        'created' => 'Dienstleistung erfolgreich erstellt',
        'updated' => 'Dienstleistung erfolgreich aktualisiert',
        'deleted' => 'Dienstleistung erfolgreich gelöscht',
        'synced' => 'Erfolgreich mit Cal.com synchronisiert',
        'sync_failed' => 'Synchronisierung fehlgeschlagen',
        'assigned' => 'Dienstleistung wurde zugewiesen an :company',
        'auto_assigned' => 'Zugewiesen an :company (:confidence%)',
        'confidence_low' => 'Vertrauen zu niedrig',
        'bulk_updated' => 'Aktualisiert: :updated Dienstleistungen',
        'bulk_errors' => 'Aktualisiert: :updated, Fehler: :errors',
    ],

    // Filter
    'filters' => [
        'active' => 'Nur aktive',
        'inactive' => 'Nur inaktive',
        'synced' => 'Synchronisiert',
        'not_synced' => 'Nicht synchronisiert',
        'with_errors' => 'Mit Fehlern',
        'company' => 'Nach Unternehmen',
        'branch' => 'Nach Filiale',
        'category' => 'Nach Kategorie',
        'price_range' => 'Preisspanne',
        'duration_range' => 'Dauerspanne',
    ],

    // Platzhalter
    'placeholders' => [
        'search' => 'Suche nach Name oder Beschreibung...',
        'select_company' => 'Unternehmen auswählen',
        'select_branch' => 'Filiale auswählen',
        'select_category' => 'Kategorie auswählen',
        'price' => 'z.B. 50.00',
        'duration' => 'z.B. 60',
        'segment_name' => 'z.B. Phase 1',
    ],

    // Tooltips
    'tooltips' => [
        'sync' => 'Diese Dienstleistung mit Cal.com synchronisieren',
        'view_details' => 'Details anzeigen',
        'edit_service' => 'Dienstleistung bearbeiten',
        'delete_service' => 'Dienstleistung löschen',
        'confidence' => 'Vertrauenswert der automatischen Zuweisung',
    ],

    // Validierungen
    'validation' => [
        'name_required' => 'Name ist erforderlich',
        'price_required' => 'Preis ist erforderlich',
        'duration_required' => 'Dauer ist erforderlich',
        'duration_min' => 'Dauer muss mindestens :min Minuten betragen',
        'duration_max' => 'Dauer darf maximal :max Minuten betragen',
        'segments_min' => 'Mindestens 2 Segmente erforderlich',
        'segments_max' => 'Maximal 10 Segmente erlaubt',
        'gap_max' => 'Lücke darf maximal 120 Minuten betragen',
    ],
];