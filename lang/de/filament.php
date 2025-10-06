<?php

/**
 * Filament Admin Panel - German Translations
 *
 * This file contains all German translations for the Filament admin interface.
 * Usage: __('filament.labels.created_by')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'created_by' => 'Erstellt von',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
        'deleted_at' => 'Gelöscht am',
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'status' => 'Status',
        'status_code' => 'Statuscode',
        'details' => 'Details',
        'settings' => 'Einstellungen',
        'actions' => 'Aktionen',
        'name' => 'Name',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'address' => 'Adresse',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Labels
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'create' => 'Erstellen',
        'edit' => 'Bearbeiten',
        'view' => 'Ansehen',
        'delete' => 'Löschen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'confirm' => 'Bestätigen',
        'back' => 'Zurück',
        'next' => 'Weiter',
        'finish' => 'Abschließen',
        'export' => 'Exportieren',
        'import' => 'Importieren',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'success' => 'Erfolg',
        'error' => 'Fehler',
        'warning' => 'Warnung',
        'info' => 'Information',
        'created' => 'Erfolgreich erstellt',
        'updated' => 'Erfolgreich aktualisiert',
        'deleted' => 'Erfolgreich gelöscht',
        'saved' => 'Erfolgreich gespeichert',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Labels
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'all' => 'Alle',
        'active_only' => 'Nur Aktive',
        'inactive_only' => 'Nur Inaktive',
        'active_label' => 'Aktive',
        'inactive_label' => 'Inaktive',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy System
    |--------------------------------------------------------------------------
    */
    'policy' => [
        'onboarding' => [
            'title' => 'Policy Onboarding Wizard',
            'welcome' => 'Willkommen zum Policy Setup Wizard!',
            'step_welcome' => 'Willkommen',
            'step_entity' => 'Entität auswählen',
            'step_rules' => 'Regeln konfigurieren',
            'step_complete' => 'Abschluss',
            'entity_type' => 'Entitätstyp',
            'policy_type' => 'Policy-Typ',
            'hours_before' => 'Vorlauf (Stunden)',
            'fee_type' => 'Gebührentyp',
            'fee_amount' => 'Gebühr',
            'enable_quota' => 'Monatliches Limit aktivieren',
            'max_per_month' => 'Maximale Anzahl pro Monat',
        ],
        'types' => [
            'cancellation' => 'Stornierung',
            'reschedule' => 'Umbuchung',
        ],
        'entities' => [
            'company' => 'Company',
            'branch' => 'Branch',
            'service' => 'Service',
            'staff' => 'Staff',
        ],
        'fee_types' => [
            'none' => 'Keine Gebühr',
            'percentage' => 'Prozentual',
            'fixed' => 'Festbetrag',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'event_types' => [
            'appointment_created' => 'Termin erstellt',
            'appointment_updated' => 'Termin aktualisiert',
            'appointment_cancelled' => 'Termin storniert',
            'appointment_reminder' => 'Terminerinnerung',
        ],
        'channels' => [
            'email' => 'E-Mail',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'push' => 'Push-Benachrichtigung',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callbacks
    |--------------------------------------------------------------------------
    */
    'callbacks' => [
        'status' => [
            'pending' => 'Ausstehend',
            'assigned' => 'Zugewiesen',
            'contacted' => 'Kontaktiert',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
        ],
        'priority' => [
            'low' => 'Niedrig',
            'normal' => 'Normal',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Appointments
    |--------------------------------------------------------------------------
    */
    'appointments' => [
        'status' => [
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
            'no_show' => 'Nicht erschienen',
        ],
    ],
];
